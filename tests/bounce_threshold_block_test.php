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

/**
 * Test case for suppression list functionality.
 *
 * @package    tool_emailutils
 * @copyright  2024 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class bounce_threshold_block_test extends \advanced_testcase {

    /**
     * Test that emails over the bounce threshold are blocked.
     */
    public function test_bounce_threshold_block_test() {
        global $CFG;

        if (!class_exists('\core\hook\email\before_email_to_user')) {
            $this->markTestSkipped('Email hook not available.');
        }

        $this->resetAfterTest();
        $CFG->minbounces = 2;
        $CFG->bounceratio = 0.01;
        set_config('block_bouncethreshold', 1, 'tool_emailutils');

        // Create 2 users for use in bounce threshold testing.
        $user1 = $this->getDataGenerator()->create_user(['email' => '1@example.com']);
        $user2 = $this->getDataGenerator()->create_user(['email' => '2@example.com']);

        // User 1 should be blocked, User 2 should not.
        set_user_preference('email_send_count', 100, $user1);
        set_user_preference('email_bounce_count', 99, $user1);
        set_user_preference('email_send_count', 100, $user2);
        set_user_preference('email_bounce_count', 0, $user2);

        $email = new \core\email(
            $user1,
            get_admin(),
            'subject',
            'messagetext',
            'messagehtml',
            'attachment',
            'attachname',
            true,
            'replyto',
            'replytoname',
            80
        );

        $hook = new \core\hook\email\before_email_to_user($email);
        \core\di::get(\core\hook\manager::class)->dispatch($hook);
        $this->assertTrue($hook->email->is_blocked());

        $email2 = new \core\email(
            $user2,
            get_admin(),
            'subject',
            'messagetext',
            'messagehtml',
            'attachment',
            'attachname',
            true,
            'replyto',
            'replytoname',
            80
        );
        $hook2 = new \core\hook\email\before_email_to_user($email2);
        \core\di::get(\core\hook\manager::class)->dispatch($hook2);
        $this->assertFalse($hook2->email->is_blocked());
    }
}
