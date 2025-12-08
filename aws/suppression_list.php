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
 * Email suppression list report page.
 *
 * @package    tool_emailutils
 * @copyright  2024 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Waleed ul hassan <waleed.hassan@catalyst-eu.net>
 */

use core_reportbuilder\system_report_factory;
use tool_emailutils\reportbuilder\local\systemreports\suppression_list;

require(__DIR__ . '/../../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('tool_emailutils_bounces', '', [], '', ['pagelayout' => 'report']);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('aws_suppressionlist', 'tool_emailutils'));

// Add warnings if the suppression list hasn't been set up or run recently.
if (empty(get_config('tool_emailutils', 'enable_suppression_list'))) {
    echo $OUTPUT->notification(get_string('aws_suppressionlist_disabled', 'tool_emailutils'), 'warning');
} else {
    // Check if the update task has run recently.
    $task = \core\task\manager::get_scheduled_task('\tool_emailutils\task\update_suppression_list');
    if ($task && $task->is_enabled()) {
        $lastrun = $task->get_last_run_time();
        if (empty($lastrun)) {
            echo $OUTPUT->notification(get_string('aws_suppressionlist_tasknever', 'tool_emailutils'), 'warning');
        } else if ($lastrun < (time() - DAYSECS)) {
            echo $OUTPUT->notification(
                get_string('aws_suppressionlist_taskupdated', 'tool_emailutils', format_time(time() - $lastrun)),
                'warning'
            );
        } else {
            // Always show the last update time as info.
            echo $OUTPUT->notification(
                get_string('aws_suppressionlist_taskupdated', 'tool_emailutils', format_time(time() - $lastrun)),
                'info'
            );
        }
    } else {
        echo $OUTPUT->notification(get_string('aws_suppressionlist_taskdisabled', 'tool_emailutils'), 'warning');
    }
}

$report = system_report_factory::create(suppression_list::class, context_system::instance());

echo $report->output();

echo $OUTPUT->footer();
