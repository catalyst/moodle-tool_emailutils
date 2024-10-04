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
 * @copyright  2019 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Waleed ul hassan <waleed.hassan@catalyst-eu.net>
 */

namespace tool_emailutils\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Scheduled task class for updating the email suppression list.
 *
 * This task fetches the latest suppression list from AWS SES and updates
 * the local database with this information.
 */
class update_suppression_list extends \core\task\scheduled_task {
    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string The name of the task.
     */
    public function get_name() {
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
    public function execute() {
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
    protected function fetch_aws_ses_suppression_list() {
        global $CFG;
        require_once($CFG->dirroot . '/local/aws/sdk/aws-autoloader.php');
        require_once($CFG->dirroot . '/local/aws/sdk/Aws/SesV2/SesV2Client.php');

        $awsregion = get_config('tool_emailutils', 'aws_region');
        $awskey = get_config('tool_emailutils', 'aws_key');
        $awssecret = get_config('tool_emailutils', 'aws_secret');

        if (empty($awsregion) || empty($awskey) || empty($awssecret)) {
            $this->log_error('AWS credentials are not configured. Please set them in the plugin settings.');
            return [];
        }

        try {
            $sesv2 = new \Aws\SesV2\SesV2Client([
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

            $suppressionlist = [];
            $params = ['MaxItems' => 100]; // Reduced from 1000 to 100 to lower the chance of rate limiting.

            do {
                $retries = 0;
                $maxretries = 5;
                $delay = 1;

                while ($retries < $maxretries) {
                    try {
                        $result = $sesv2->listSuppressedDestinations($params);
                        break; // If successful, exit the retry loop.
                    } catch (\Aws\Exception\AwsException $e) {
                        if ($e->getAwsErrorCode() === 'TooManyRequestsException') {
                            $retries++;
                            if ($retries >= $maxretries) {
                                $this->log_error('Max retries reached for AWS SES API call: ' . $e->getMessage());
                                return []; // Return empty array after max retries.
                            }
                            $this->log_error("Rate limit hit, retrying in {$delay} seconds...");
                            sleep($delay); // Wait before retrying.
                            $delay *= 2; // Exponential backoff.
                        } else {
                            $this->log_error('AWS SES Error: ' . $e->getMessage());
                            return []; // Return empty array for other AWS exceptions.
                        }
                    }
                }
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
            $this->log_error('Unexpected error: ' . $e->getMessage());
            return [];
        }
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
    private function log_error($message) {
        if (CLI_SCRIPT) {
            mtrace($message);
        }
        debugging($message);
    }
}