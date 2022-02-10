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
 * @package    tool_emailses
 * @copyright  2019 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Garth Williamson <garth@catalyst-eu.net>
 */

namespace tool_emailses;

use stdClass;

use moodle_url;
use renderable;
use renderer_base;
use templatable;


/**
 * The complaints list class is a widget that displays a list of complaints.
 *
 */
class complaints_list extends \table_sql implements renderable  {
    /**
     * Sets up the complaints_list table parameters.
     *
     * @param string $uniqueid unique id of form.
     * @param \moodle_url $url url where this table is displayed.
     */
    public function __construct($uniqueid, \moodle_url $url, $perpage = 100) {
        parent::__construct($uniqueid);

        $columns = [
            'fullname',
            'email',
            'bouncecount',
        ];

        $headers = [
            get_string('fullname'),
            get_string('email'),
            get_string('bouncecount', 'tool_emailses'),
        ];

        $this->set_attribute('class', 'toolemailses generaltable generalbox');
        $this->define_columns($columns);
        $this->define_headers($headers);

        $this->pagesize = $perpage;
        $this->collapsible(false);
        $this->sortable(true);
        $this->pageable(true);
        $this->is_downloadable(false);
        $this->define_baseurl($url);

        $fields = 'u.id, u.email, up.name, up.value AS bouncecount, ' . get_all_user_name_fields(true, 'u');
        $from = '{user} u LEFT JOIN {user_preferences} up ON u.id = up.userid';
        $where = "up.name = 'email_bounce_count'";
        $params = [];
        $this->set_sql($fields, $from, $where, $params);
    }
}
