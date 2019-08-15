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
 * Class complaint_list
 * @package    local_sescomplaints
 * @copyright  2019 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Garth Williamson <garth@catalyst-eu.net>
 */

namespace local_sescomplaints;

use stdClass;

use moodle_url;
use renderable;
use renderer_base;
use templatable;

defined('MOODLE_INTERNAL') or die;

/**
 * The complaints list class is a widget that displays a list of complaints.
 *
 */
class complaints_list implements renderable, templatable {
    public function export_for_template(renderer_base $output) {
        $list = new stdClass();
        $list->search_url = new moodle_url('/admin/user.php');
        return $list;
    }
}
