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
 * @copyright  2019 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Waleed ul hassan <waleed.hassan@catalyst-eu.net>
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('toolemailutilssuppressionlist');

$action = optional_param('action', '', PARAM_ALPHA);

if ($action === 'download') {
    // Generate and download CSV file.
    generate_suppression_list_csv();
} else {
    // Display the download page.
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

/**
 * Generate and download the suppression list CSV file.
 */
function generate_suppression_list_csv() {
    global $CFG, $DB;
    require_once($CFG->libdir . '/csvlib.class.php');

    $filename = 'email_suppression_list_' . date('Y-m-d') . '.csv';
    $csvexport = new csv_export_writer();
    $csvexport->set_filename($filename);

    // Add CSV headers.
    $csvexport->add_data(['Email', 'Reason', 'Created At']);

    // Fetch suppression list from database.
    $suppressionlist = $DB->get_records('tool_emailutils_suppression');

    // Add suppression list data to CSV.
    foreach ($suppressionlist as $item) {
        $csvexport->add_data([
            $item->email,
            $item->reason,
            $item->created_at,
        ]);
    }

    $csvexport->download_file();
    exit;
}

