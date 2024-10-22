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
     * @throws \dml_exception
     */
    public function execute(): void {
        if (!$this->is_feature_enabled()) {
            return;
        }

        if (!$this->check_connection()) {
            debugging('Unable to connect to AWS SES. Suppression list update skipped.');
            return;
        }

        $suppressionlist = $this->fetch_aws_ses_suppression_list();
        $this->update_local_suppression_list($suppressionlist);
    }

    /**
     * Check if the email suppression list feature is enabled.
     *
     * @return bool True if the feature is enabled, false otherwise.
     * @throws \dml_exception
     */
    protected function is_feature_enabled(): bool {
        return (bool)get_config('tool_emailutils', 'enable_suppression_list');
    }

    /**
     * Check the connection to AWS SES.
     *
     * @return bool True if the connection is successful, false otherwise.
     */
    protected function check_connection(): bool {
        try {
            $client = $this->get_ses_client();
            $client->listSuppressedDestinations(['MaxItems' => 1]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Fetch the suppression list from AWS SES.
     *
     * @return array The fetched suppression list.
     */
    protected function fetch_aws_ses_suppression_list(): array {
        try {
            $suppressionlist = [];
            $params = ['MaxItems' => 100];

            do {
                $result = $this->get_ses_client()->listSuppressedDestinations($params);
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
            debugging('Error fetching suppression list: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Update the local suppression list with the fetched data.
     *
     * @param array $suppressionlist The fetched suppression list.
     * @throws \dml_exception
     */
    protected function update_local_suppression_list(array $suppressionlist): void {
        global $DB;

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

        mtrace('Suppression list updated successfully.');
    }

    /**
     * Get or create the SES client.
     *
     * @return \Aws\SesV2\SesV2Client|null
     * @throws \dml_exception
     */
    protected function get_ses_client(): ?\Aws\SesV2\SesV2Client {
        if (!$this->sesclient) {
            $awsregion = get_config('tool_emailutils', 'aws_region');
            $awskey = get_config('tool_emailutils', 'aws_key');
            $awssecret = get_config('tool_emailutils', 'aws_secret');

            if (empty($awsregion) || empty($awskey) || empty($awssecret)) {
                debugging('AWS credentials are not configured.');
                return null;
            }

            $this->sesclient = new \Aws\SesV2\SesV2Client([
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

        return $this->sesclient;
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
