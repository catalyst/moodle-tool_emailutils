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

namespace tool_emailutils\reportbuilder\local\systemreports;

use context_system;
use tool_emailutils\reportbuilder\local\entities\suppressed_email;
use core_reportbuilder\system_report;
use core_reportbuilder\local\entities\user;

/**
 * SES account level suppression list class implementation.
 *
 * @package    tool_emailutils
 * @author     Benjamin Walker <benjaminwalker@catalyst-au.net>
 * @copyright  Catalyst IT 2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
class suppression_list extends system_report {
    /**
     * Initialise report, we need to set the main table, load our entities and set columns/filters
     */
    protected function initialise(): void {
        // Our main entity, it contains all of the column definitions that we need.
        $entitymain = new suppressed_email();
        $entitymainalias = $entitymain->get_table_alias('tool_emailutils_suppression');

        $this->set_main_table('tool_emailutils_suppression', $entitymainalias);
        $this->add_entity($entitymain);

        // We can join the "user" entity to our "main" entity using standard SQL JOIN.
        $entityuser = new user();
        $entityuseralias = $entityuser->get_table_alias('user');
        $this->add_entity($entityuser
            ->add_join("LEFT JOIN {user} {$entityuseralias} ON {$entityuseralias}.email = {$entitymainalias}.email"));

        // Now we can call our helper methods to add the content we want to include in the report.
        $this->add_columns();
        $this->add_filters();

        // Set if report can be downloaded.
        $this->set_downloadable(true, 'email_suppression_list_' . date('Y-m-d'));
    }

    /**
     * Validates access to view this report
     *
     * @return bool
     */
    protected function can_view(): bool {
        return has_capability('moodle/site:config', context_system::instance());
    }

    /**
     * Adds the columns we want to display in the report
     *
     * They are all provided by the entities we previously added in the {@see initialise} method, referencing each by their
     * unique identifier
     */
    protected function add_columns(): void {
        $columns = [
            'suppressed_email:email',
            'suppressed_email:reason',
            'suppressed_email:created',
            'user:fullnamewithlink',
        ];

        $this->add_columns_from_entities($columns);

        // Default sorting.
        $this->set_initial_sort_column('suppressed_email:created', SORT_DESC);
    }

    /**
     * Adds the filters we want to display in the report
     *
     * They are all provided by the entities we previously added in the {@see initialise} method, referencing each by their
     * unique identifier
     */
    protected function add_filters(): void {
        $filters = [
            'suppressed_email:email',
            'suppressed_email:reason',
            'suppressed_email:created',
            'user:fullname',
            'user:username',
        ];

        $this->add_filters_from_entities($filters);
    }
}
