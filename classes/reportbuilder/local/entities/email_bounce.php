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

namespace tool_emailutils\reportbuilder\local\entities;

use lang_string;
use core_reportbuilder\local\entities\base;
use core_reportbuilder\local\filters\number;
use core_reportbuilder\local\report\column;
use core_reportbuilder\local\report\filter;
use tool_emailutils\helper;

/**
 * Email bounce entity class class implementation.
 *
 * Defines all the columns and filters that can be added to reports that use this entity.
 *
 * @package    tool_emailutils
 * @author     Benjamin Walker <benjaminwalker@catalyst-au.net>
 * @copyright  Catalyst IT 2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
class email_bounce extends base {
    /**
     * Database tables that this entity uses
     *
     * @return string[]
     */
    protected function get_default_tables(): array {
        return [
            'user_preferences',
            'user_preferences_send',
        ];
    }

    /**
     * The default title for this entity
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('bouncecount', 'tool_emailutils');
    }

    /**
     * Initialize the entity
     *
     * @return base
     */
    public function initialise(): base {
        $entitymainalias = $this->get_table_alias('user_preferences');
        $entitysendalias = $this->get_table_alias('user_preferences_send');
        $this->add_join("LEFT JOIN {user_preferences} {$entitysendalias}
                                ON {$entitysendalias}.userid = {$entitymainalias}.userid
                               AND {$entitysendalias}.name = 'email_send_count'");

        $columns = $this->get_all_columns();
        foreach ($columns as $column) {
            $this->add_column($column);
        }

        $filters = $this->get_all_filters();
        foreach ($filters as $filter) {
            $this->add_filter($filter);
            $this->add_condition($filter);
        }

        return $this;
    }

    /**
     * Returns list of all available columns
     *
     * @return column[]
     */
    protected function get_all_columns(): array {
        global $DB;

        $tablealias = $this->get_table_alias('user_preferences');
        $sendalias = $this->get_table_alias('user_preferences_send');

        // Bounces column.
        $columns[] = (new column(
            'bounces',
            new lang_string('bouncecount', 'tool_emailutils'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_field($DB->sql_cast_char2int("{$tablealias}.value"), 'bounces')
            ->set_is_sortable(true)
            ->add_callback(function (int $value): string {
                if ($value >= helper::get_min_bounces()) {
                    return \html_writer::span($value, 'alert alert-danger p-2');
                }
                return $value;
            });

        // Emails send column.
        $columns[] = (new column(
            'send',
            new lang_string('sendcount', 'tool_emailutils'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_INTEGER)
            ->add_field($DB->sql_cast_char2int("{$sendalias}.value"), 'send')
            ->set_is_sortable(true);

        // Bounce ratio column.
        $bouncesql = $DB->sql_cast_char2real("{$tablealias}.value");
        $sendsql = $DB->sql_cast_char2real("{$sendalias}.value");
        $columns[] = (new column(
            'ratio',
            new lang_string('bounceratio', 'tool_emailutils'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_FLOAT)
            ->add_field("CASE WHEN $sendsql = 0 THEN NULL ELSE $bouncesql / $sendsql END", 'ratio')
            ->set_is_sortable(true)
            ->set_is_available(helper::use_bounce_ratio())
            ->add_callback(function (?float $value): string {
                $float = format_float($value, 2);
                if ($value > helper::get_bounce_ratio()) {
                    return \html_writer::span($float, 'alert alert-danger p-2');
                }
                return $float;
            });

        return $columns;
    }

    /**
     * Return list of all available filters
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {
        global $DB;

        $tablealias = $this->get_table_alias('user_preferences');
        $sendalias = $this->get_table_alias('user_preferences_send');

        // Bounces filter.
        $filters[] = (new filter(
            number::class,
            'bounces',
            new lang_string('bouncecount', 'tool_emailutils'),
            $this->get_entity_name(),
            $DB->sql_cast_char2int("{$tablealias}.value")
        ))
            ->add_joins($this->get_joins());

        // Send emails filter.
        $filters[] = (new filter(
            number::class,
            'send',
            new lang_string('sendcount', 'tool_emailutils'),
            $this->get_entity_name(),
            $DB->sql_cast_char2int("{$sendalias}.value")
        ))
            ->add_joins($this->get_joins());

        return $filters;
    }
}
