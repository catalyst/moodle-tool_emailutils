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
 * Email suppression list download page.
 *
 * @package    tool_emailutils
 * @copyright  2024 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Waleed ul hassan <waleed.hassan@catalyst-eu.net>
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

// Ensure the user is logged in and has the necessary permissions.
require_login();
require_capability('moodle/site:config', context_system::instance());

$action = optional_param('action', '', PARAM_ALPHA);

if ($action === 'download') {
    $content = \tool_emailutils\suppression_list::generate_csv();
    $filename = 'email_suppression_list_' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($content));
    echo $content;
    exit;
} else {
    // Display the download page.
    admin_externalpage_setup('toolemailutilssuppressionlist');
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('suppressionlist', 'tool_emailutils'));

    echo html_writer::start_tag('div', ['class' => 'suppressionlist-download']);
    echo html_writer::tag('p', get_string('suppressionlistdesc', 'tool_emailutils'));
    echo html_writer::link(
        new moodle_url('/admin/tool/emailutils/suppression_list.php', ['action' => 'download']),
        get_string('downloadsuppressionlist', 'tool_emailutils'),
        ['class' => 'btn btn-primary']
    );
    echo html_writer::end_tag('div');

    echo $OUTPUT->footer();
}
