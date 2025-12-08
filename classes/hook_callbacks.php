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

/**
 * Hook callbacks for tool_emailutils.
 *
 * @package   tool_emailutils
 * @author    Benjamin Walker (benjaminwalker@catalyst-au.net)
 * @copyright 2024 Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {

    /**
     * This adds a new bulk user action to reset a persons bounce count.
     *
     * @param \core_user\hook\extend_bulk_user_actions $hook
     */
    public static function extend_bulk_user_actions(\core_user\hook\extend_bulk_user_actions $hook): void {
        if (has_capability('moodle/site:config', \context_system::instance())) {
            $hook->add_action('tool_emailutils_reset_bounces', new \action_link(
                new \moodle_url('/admin/tool/emailutils/reset_bounces.php'),
                get_string('resetbounces', 'tool_emailutils')
            ));
        }
    }

    /**
     * Handle emails being sent to users.
     *
     * This is used for bounce count blocking of emails.
     *
     * @param \core\hook\email\before_email_to_user $hook
     */
    public static function before_email_to_user(\core\hook\email\before_email_to_user $hook): void {
        $user = $hook->email->user;
        if (self::over_bounce_threshold($user)) {
            $hook->email->add_block_reason('blockbouncethreshold', 'tool_emailutils');
        }
    }

    /**
     * Check whether the user has exceeded the bounce threshold
     * This is a copy of the function in moodlelib.php,
     * with the check for $CFG->handlebounces removed.
     *
     * @param stdClass $user A {@link $USER} object
     * @return bool true => User has exceeded bounce threshold
     */
    private static function over_bounce_threshold($user) {
        global $CFG, $DB;

        if (empty($user->id)) {
            // No real (DB) user, nothing to do here.
            return false;
        }

        // Set sensible defaults.
        if (empty($CFG->minbounces)) {
            $CFG->minbounces = 10;
        }
        if (empty($CFG->bounceratio)) {
            $CFG->bounceratio = .20;
        }
        $bouncecount = 0;
        $sendcount = 0;
        if ($bounce = $DB->get_record('user_preferences', array ('userid' => $user->id, 'name' => 'email_bounce_count'))) {
            $bouncecount = $bounce->value;
        }
        if ($send = $DB->get_record('user_preferences', array('userid' => $user->id, 'name' => 'email_send_count'))) {
            $sendcount = $send->value;
        }
        return ($bouncecount >= $CFG->minbounces && $bouncecount/$sendcount >= $CFG->bounceratio);
    }
}
