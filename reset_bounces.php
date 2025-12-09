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
 * Page to bulk reset the email bounce count for users.
 *
 * @package     tool_emailutils
 * @author      Nicholas Hoobin <nicholashoobin@catalyst-au.net>
 * @copyright   Catalyst IT
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('tool_emailutils_bounces');
require_capability('moodle/user:update', context_system::instance());

$confirm = optional_param('confirm', 0, PARAM_BOOL);
$userid = optional_param('id', null, PARAM_INT);
$returnurl = optional_param('returnurl', null, PARAM_LOCALURL);

$users = empty($userid) ? $SESSION->bulk_users : [$userid];
$return = new moodle_url($returnurl ?? '/admin/tool/emailutils/bounces.php');

if (empty($users)) {
    redirect($return);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('resetbounces', 'tool_emailutils'));

if ($confirm && confirm_sesskey()) {
    [$in, $params] = $DB->get_in_or_equal($users);
    $rs = $DB->get_recordset_select('user', "id $in", $params, '', 'id, ' . \tool_emailutils\helper::get_username_fields());
    foreach ($rs as $user) {
        \tool_emailutils\helper::reset_bounce_count($user);
    }
    $rs->close();
    echo $OUTPUT->box_start('generalbox', 'notice');
    echo $OUTPUT->notification(get_string('bouncesreset', 'tool_emailutils'), 'notifysuccess');

    echo html_writer::link($return, get_string('continue'), ['class' => 'btn btn-primary']);
    echo $OUTPUT->box_end();
} else {
    [$in, $params] = $DB->get_in_or_equal($users);
    $userlist = $DB->get_records_select_menu('user', "id $in", $params, 'fullname', 'id,' . $DB->sql_fullname() . ' AS fullname');

    if (empty($userlist)) {
        echo $OUTPUT->notification(get_string('invaliduserid', 'error'));
        echo html_writer::link($return, get_string('continue'), ['class' => 'btn btn-primary']);
    } else {
        if (\tool_emailutils\helper::use_bounce_ratio()) {
            echo $OUTPUT->notification(get_string('resetbounceratio', 'tool_emailutils'), 'warning');
        }
        $usernames = implode(', ', $userlist);
        $params = array_filter([
            'confirm' => 1,
            'id' => $userid,
            'returnurl' => $returnurl,
        ]);
        $formcontinue = new single_button(new moodle_url('reset_bounces.php', $params), get_string('yes'));
        $formcancel = new single_button($return, get_string('no'));
        echo $OUTPUT->confirm(get_string('bouncecheckfull', 'tool_emailutils', $usernames), $formcontinue, $formcancel);
    }
}
echo $OUTPUT->footer();
