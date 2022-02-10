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
 * @package    tool_emailses
 * @copyright  2019 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Garth Williamson <garth@catalyst-eu.net>
 */

use tool_emailses\complaints_list;

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

// Initialise page and check permissions.
$baseurl = new moodle_url('/admin/tool/emailses/index.php');
$PAGE->set_url($baseurl);
admin_externalpage_setup('tool_emailses_list');

echo $OUTPUT->header();

if (empty($CFG->handlebounces)) {
    echo $OUTPUT->notification(get_string('configmissing', 'tool_emailses'));
}

$complaintslist = new complaints_list('tool_emailses', $baseurl, 100);
$complaintslist->out(100, false);

echo $OUTPUT->footer();
