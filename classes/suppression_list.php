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

require_once($CFG->libdir . '/csvlib.class.php');

/**
 * Class for handling suppression list operations.
 *
 * @package    tool_emailutils
 * @copyright  2024 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class suppression_list {

    /**
     * Generate CSV content for the suppression list.
     *
     * @return string The CSV content.
     * @throws \dml_exception
     */
    public static function generate_csv(): string {
        global $DB;

        $csvexport = new \csv_export_writer('comma');

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

        return $csvexport->print_csv_data(true);
    }
}
