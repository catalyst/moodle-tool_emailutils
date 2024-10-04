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
 * Unit tests for suppression list functionality.
 *
 * @package    tool_emailutils
 * @copyright  2019 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Waleed ul hassan <waleed.hassan@catalyst-eu.net>
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/admin/tool/emailutils/classes/task/update_suppression_list.php');

/**
 * Test cases for suppression list functionality.
 */
class suppressionlist_test extends advanced_testcase {

    /**
     * Test the creation and retrieval of suppression list entries.
     *
     * This test covers the basic CRUD (Create, Read, Update, Delete) operations
     * for the suppression list database table.
     *
     */
    public function test_suppression_list_crud() {
        global $DB;

        $this->resetAfterTest(true);

        // Create some test data.
        $testdata = [
            ['email' => 'test1@example.com', 'reason' => 'Bounce', 'created_at' => '2024-03-01 10:00:00'],
            ['email' => 'test2@example.com', 'reason' => 'Complaint', 'created_at' => '2024-03-02 11:00:00'],
        ];

        // Insert test data.
        foreach ($testdata as $data) {
            $record = new stdClass();
            $record->email = $data['email'];
            $record->reason = $data['reason'];
            $record->created_at = $data['created_at'];
            $record->timecreated = time();
            $record->timemodified = time();

            $DB->insert_record('tool_emailutils_suppression', $record);
        }

        // Test retrieval.
        $records = $DB->get_records('tool_emailutils_suppression');
        $this->assertCount(2, $records);

        // Test specific record retrieval.
        $record = $DB->get_record('tool_emailutils_suppression', ['email' => 'test1@example.com']);
        $this->assertEquals('Bounce', $record->reason);

        // Test update.
        $record->reason = 'Updated Reason';
        $DB->update_record('tool_emailutils_suppression', $record);
        $updatedrecord = $DB->get_record('tool_emailutils_suppression', ['email' => 'test1@example.com']);
        $this->assertEquals('Updated Reason', $updatedrecord->reason);

        // Test delete.
        $DB->delete_records('tool_emailutils_suppression', ['email' => 'test2@example.com']);
        $remainingrecords = $DB->get_records('tool_emailutils_suppression');
        $this->assertCount(1, $remainingrecords);
    }

    /**
     * Test the scheduled task for updating the suppression list.
     *
     * This test creates a mock task that overrides the AWS SES client
     * to return predefined test data. It then executes the task and
     * verifies that the suppression list in the database is updated correctly.
     *
     * @return void
     * @covers \tool_emailutils\task\update_suppression_list::execute
     * @covers \tool_emailutils\task\update_suppression_list::fetch_aws_ses_suppression_list
     * @throws dml_exception
     */
    public function test_update_suppression_list_task(): void {
        global $DB;
        $this->resetAfterTest(true);

        // Set up mock AWS credentials (these won't be used, but let's set them anyway).
        set_config('aws_region', 'eu-west-2', 'tool_emailutils');
        set_config('aws_key', 'testkey', 'tool_emailutils');
        set_config('aws_secret', 'testsecret', 'tool_emailutils');

        $mocktask = new class extends \tool_emailutils\task\update_suppression_list {
            /**
             * Override the execute method to use our mocked data.
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
             * Override the fetch method to return mock data.
             *
             * @return array Mock suppression list data.
             */
            protected function fetch_aws_ses_suppression_list(): array {
                return [
                    [
                        'email' => 'suppressed@example.com',
                        'reason' => 'BOUNCE',
                        'created_at' => '2024-03-03 12:00:00',
                    ],
                ];
            }
        };

        // Execute the mock task.
        $mocktask->execute();

        // Check if the suppression list was updated in the database.
        $record = $DB->get_record('tool_emailutils_suppression', ['email' => 'suppressed@example.com']);
        $this->assertNotFalse($record);
        $this->assertEquals('BOUNCE', $record->reason);
        $this->assertEquals('2024-03-03 12:00:00', $record->created_at);
    }

    /**
     * Test AWS credentials configuration.
     *
     * This test verifies that AWS credentials can be correctly set and retrieved
     * from the Moodle configuration.
     *
     */
    public function test_aws_credentials_config() {
        $this->resetAfterTest(true);

        // Set test configuration.
        set_config('aws_region', 'eu-west-2', 'tool_emailutils');
        set_config('aws_key', 'testkey', 'tool_emailutils');
        set_config('aws_secret', 'testsecret', 'tool_emailutils');

        // Verify the configuration.
        $this->assertEquals('eu-west-2', get_config('tool_emailutils', 'aws_region'));
        $this->assertEquals('testkey', get_config('tool_emailutils', 'aws_key'));
        $this->assertEquals('testsecret', get_config('tool_emailutils', 'aws_secret'));
    }
}
