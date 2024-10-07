<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Scheduled task for updating the email suppression list.
 *
 * @package    tool_emailutils
 * @copyright  2024 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Waleed ul hassan <waleed.hassan@catalyst-eu.net>
 */

namespace tool_emailutils\task;

defined('MOODLE_INTERNAL') || die();

if (!class_exists('\Aws\SesV2\SesV2Client')) {
    if (file_exists($CFG->dirroot . '/local/aws/sdk/aws-autoloader.php')) {
        require_once($CFG->dirroot . '/local/aws/sdk/aws-autoloader.php');
    } else {
        throw new \Exception('AWS SDK not found.');
    }
}

/**
 * Scheduled task class for updating the email suppression list.
 *
 * This task fetches the latest suppression list from AWS SES and updates
 * the local database with this information.
 */
class update_suppression_list extends \core\task\scheduled_task {
    /** @var \Aws\SesV2\SesV2Client|null */
    protected $sesclient = null;

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string The name of the task.
     */
    public function get_name(): string {
        return get_string('task_update_suppression_list', 'tool_emailutils');
    }

    /**
     * Execute the task.
     *
     * This method fetches the suppression list from AWS SES and updates
     * the local database with the fetched information.
     *
     * @return void
     */
    public function execute(): void {
        global $DB;

        $suppressionlist = $this->fetch_aws_ses_suppression_list();

        $DB->delete_records('tool_emailutils_suppression');

        foreach ($suppressionlist as $item) {
            $record = new \stdClass();
            $record->email = $item['email'];
            $record->reason = $item['reason'];
            $record->created_at = $item['created_at'];
            $record->timecreated = time();
            $record->timemodified = time();

            $DB->insert_record('tool_emailutils_suppression', $record);
        }
    }

    /**
     * Fetch the suppression list from AWS SES.
     *
     * This method connects to AWS SES, retrieves the suppression list,
     * and formats it for local storage. It includes error handling and
     * retries for rate limiting.
     *
     * @return array The fetched suppression list.
     */
    protected function fetch_aws_ses_suppression_list(): array {
        if (!$this->sesclient) {
            $this->sesclient = $this->create_ses_client();
        }

        try {
            $suppressionlist = [];
            $params = ['MaxItems' => 100];

            do {
                $result = $this->sesclient->listSuppressedDestinations($params);
                foreach ($result['SuppressedDestinationSummaries'] as $item) {
                    $suppressionlist[] = [
                        'email' => $item['EmailAddress'],
                        'reason' => $item['Reason'],
                        'created_at' => $item['LastUpdateTime']->format('Y-m-d H:i:s'),
                    ];
                }
                $params['NextToken'] = $result['NextToken'] ?? null;
            } while ($params['NextToken']);

            return $suppressionlist;
        } catch (\Exception $e) {
            $this->log_error('Error fetching suppression list: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Create an SES client instance.
     *
     * @return \Aws\SesV2\SesV2Client
     */
    protected function create_ses_client(): \Aws\SesV2\SesV2Client {
        global $CFG;

        $awsregion = get_config('tool_emailutils', 'aws_region');
        $awskey = get_config('tool_emailutils', 'aws_key');
        $awssecret = get_config('tool_emailutils', 'aws_secret');

        if (empty($awsregion) || empty($awskey) || empty($awssecret)) {
            throw new \Exception('AWS credentials are not configured.');
        }

        return new \Aws\SesV2\SesV2Client([
            'version' => 'latest',
            'region'  => $awsregion,
            'credentials' => [
                'key'    => $awskey,
                'secret' => $awssecret,
            ],
            'retries' => [
                'mode' => 'adaptive',
                'max_attempts' => 10,
            ],
        ]);
    }

    /**
     * Log an error message, printing to console if running via CLI.
     *
     * This method logs error messages, ensuring they are visible both in
     * the Moodle error log and on the console when run via CLI.
     *
     * @param string $message The error message to log.
     * @return void
     */
    private function log_error(string $message): void {
        if (CLI_SCRIPT) {
            mtrace($message);
        }
        debugging($message);
    }

    /**
     * Set the SES client (for testing purposes).
     *
     * @param \Aws\SesV2\SesV2Client $client
     */
    public function set_ses_client(\Aws\SesV2\SesV2Client $client): void {
        $this->sesclient = $client;
    }
}
