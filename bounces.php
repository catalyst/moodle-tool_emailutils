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
 * Email bounces report.
 *
 * @package    tool_emailutils
 * @author     Benjamin Walker <benjaminwalker@catalyst-au.net>
 * @copyright  Catalyst IT 2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

use core_reportbuilder\system_report_factory;
use tool_emailutils\helper;
use tool_emailutils\reportbuilder\local\systemreports\email_bounces;

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/' . $CFG->admin . '/user/lib.php');
require_once($CFG->dirroot . '/' . $CFG->admin . '/user/user_bulk_forms.php');

admin_externalpage_setup('tool_emailutils_bounces', '', [], '', ['pagelayout' => 'report']);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('reportbounces', 'tool_emailutils'));

// Render config used for calculating bounce threshold.
[$corehandlebounces, $minbounces, $bounceratio, $blockbounces] = helper::get_bounce_config();
// If the hook exists to instrument sending, we handle bounce blocking that way.
if (empty($corehandlebounces) && !$blockbounces) {
    echo $OUTPUT->notification(get_string('configmissing', 'tool_emailutils'));
} else {
    echo $OUTPUT->notification(get_string('bounceconfig', 'tool_emailutils', [
        'minbounces' => $minbounces,
        'bounceratio' => $bounceratio,
    ]), 'info');
}

echo html_writer::start_div('', ['data-region' => 'report-user-list-wrapper']);

// Exclude all actions besides reset bounces.
$actionames = array_keys((new user_bulk_action_form())->get_actions());
$excludeactions = array_diff($actionames, ['tool_emailutils_reset_bounces']);

$bulkactions = new user_bulk_action_form(
    new moodle_url('/admin/user/user_bulk.php'),
    ['excludeactions' => $excludeactions, 'passuserids' => true, 'hidesubmit' => true],
    'post',
    '',
    ['id' => 'user-bulk-action-form']
);
$bulkactions->set_data(['returnurl' => $PAGE->url->out_as_local_url(false)]);

$report = system_report_factory::create(
    email_bounces::class,
    context_system::instance(),
    parameters: ['withcheckboxes' => $bulkactions->has_bulk_actions()]
);

echo $report->output();

if ($bulkactions->has_bulk_actions()) {
    $PAGE->requires->js_call_amd('core_admin/bulk_user_actions', 'init');
    $bulkactions->display();
}

echo html_writer::end_div();

echo $OUTPUT->footer();
