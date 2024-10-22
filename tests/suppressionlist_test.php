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

namespace tool_emailutils;
defined('MOODLE_INTERNAL') || die();

global $CFG;

if (!class_exists('\Aws\SesV2\SesV2Client')) {
    if (file_exists($CFG->dirroot . '/local/aws/sdk/aws-autoloader.php')) {
        require_once($CFG->dirroot . '/local/aws/sdk/aws-autoloader.php');
    }
}

/**
 * Test case for suppression list functionality.
 *
 * @package    tool_emailutils
 * @copyright  2024 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class suppressionlist_test extends \advanced_testcase {

    /**
     * Set up the test environment and return a configured task.
     *
     * @param bool $enablefeature Whether to enable the suppression list feature.
     * @return \tool_emailutils\task\update_suppression_list
     */
    protected function setup_test_environment(bool $enablefeature): \tool_emailutils\task\update_suppression_list {
        $this->resetAfterTest(true);

        // Set the suppression list feature configuration.
        set_config('enable_suppression_list', $enablefeature ? 1 : 0, 'tool_emailutils');

        // Set up a user with necessary permissions.
        $this->setAdminUser();

        // Create a mock command result.
        $mockresult = new \Aws\Result([
            'SuppressedDestinationSummaries' => [
                [
                    'EmailAddress' => 'test1@example.com',
                    'Reason' => 'BOUNCE',
                    'LastUpdateTime' => new \DateTime('2024-03-01 10:00:00'),
                ],
                [
                    'EmailAddress' => 'test2@example.com',
                    'Reason' => 'COMPLAINT',
                    'LastUpdateTime' => new \DateTime('2024-03-02 11:00:00'),
                ],
            ],
            'NextToken' => null,
        ]);

        // Create a mock SES client.
        $mockclient = $this->createMock(\Aws\SesV2\SesV2Client::class);
        $mockclient->method('__call')
            ->with($this->equalTo('listSuppressedDestinations'), $this->anything())
            ->willReturn($mockresult);

        // Create the task and set the mock client.
        $task = new \tool_emailutils\task\update_suppression_list();
        $task->set_ses_client($mockclient);

        return $task;
    }

    /**
     * Test the update of the suppression list and the generation of the CSV file.
     *
     * This test checks the following:
     * 1. The suppression list is properly updated in the database from the mock AWS SES response.
     * 2. The correct number of records (2 in this case) is added to the suppression table.
     * 3. Each record has the correct email and reason as per the mock data.
     * 4. A CSV file is generated with the correct headers and content matching the database.
     *
     * @covers \tool_emailutils\task\update_suppression_list::execute
     * @covers \tool_emailutils\suppression_list::generate_csv
     *
     * @return void
     * @throws \dml_exception
     */
    public function test_suppression_list_update_and_export(): void {
        global $DB;

        $task = $this->setup_test_environment(true);

        // Capture the output.
        ob_start();
        $task->execute();
        $output = ob_get_clean();

        // Assert that the expected string is in the output.
        $this->assertStringContainsString('Suppression list updated successfully.', $output);

        // Verify that the suppression list was updated in the database.
        $records = $DB->get_records('tool_emailutils_suppression');
        $this->assertCount(2, $records);

        // Check if the records exist and have the correct data.
        $foundtest1 = false;
        $foundtest2 = false;
        foreach ($records as $record) {
            if ($record->email === 'test1@example.com') {
                $this->assertEquals('BOUNCE', $record->reason);
                $foundtest1 = true;
            } else if ($record->email === 'test2@example.com') {
                $this->assertEquals('COMPLAINT', $record->reason);
                $foundtest2 = true;
            }
        }
        $this->assertTrue($foundtest1, 'test1@example.com not found in the database');
        $this->assertTrue($foundtest2, 'test2@example.com not found in the database');

        // Now test the CSV file generation.
        $csvcontent = \tool_emailutils\suppression_list::generate_csv();

        // Verify the CSV content.
        $lines = explode("\n", trim($csvcontent));
        $this->assertEquals('Email,Reason,"Created At"', $lines[0]);
        $this->assertStringContainsString('test1@example.com', $lines[1]);
        $this->assertStringContainsString('BOUNCE', $lines[1]);
        $this->assertStringContainsString('test2@example.com', $lines[2]);
        $this->assertStringContainsString('COMPLAINT', $lines[2]);
    }

    /**
     * Test the update of the suppression list when the feature is disabled.
     *
     * This test checks the following:
     * 1. The suppression list is not updated in the database.
     * 2. The suppression list table is empty.
     *
     * @return void
     * @throws \dml_exception
     */
    public function test_suppression_list_update_when_disabled(): void {
        global $DB;

        $task = $this->setup_test_environment(false);

        // Capture the output.
        ob_start();
        $task->execute();
        $output = ob_get_clean();

        // Assert that there is no output.
        $this->assertEmpty($output);

        // Verify that the suppression list table is empty.
        $records = $DB->get_records('tool_emailutils_suppression');
        $this->assertEmpty($records);
    }
}
