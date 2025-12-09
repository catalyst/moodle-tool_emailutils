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
use core_reportbuilder\local\filters\date;
use core_reportbuilder\local\filters\text;
use core_reportbuilder\local\helpers\format;
use core_reportbuilder\local\report\column;
use core_reportbuilder\local\report\filter;
use tool_emailutils\sns_notification;

/**
 * Notification log list entity class class implementation.
 *
 * Defines all the columns and filters that can be added to reports that use this entity.
 *
 * @package    tool_emailutils
 * @author     Benjamin Walker <benjaminwalker@catalyst-au.net>
 * @copyright  Catalyst IT 2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
class notification_log extends base {
    /**
     * Database tables that this entity uses
     *
     * @return string[]
     */
    protected function get_default_tables(): array {
        return [
            'tool_emailutils_log',
        ];
    }

    /**
     * The default title for this entity
     *
     * @return lang_string
     */
    protected function get_default_entity_title(): lang_string {
        return new lang_string('notificationlog', 'tool_emailutils');
    }

    /**
     * Initialize the entity
     *
     * @return base
     */
    public function initialise(): base {
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
        $tablealias = $this->get_table_alias('tool_emailutils_log');

        // Email column.
        $columns[] = (new column(
            'email',
            new lang_string('email'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$tablealias}.email")
            ->set_is_sortable(true);

        // Type column.
        $columns[] = (new column(
            'type',
            new lang_string('type', 'tool_emailutils'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$tablealias}.type")
            ->set_is_sortable(true);

        // Subtypes column.
        $columns[] = (new column(
            'subtypes',
            new lang_string('subtypes', 'tool_emailutils'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TEXT)
            ->add_fields("{$tablealias}.subtypes")
            ->set_is_sortable(true)
            ->add_callback(function (?string $subtypes): string {
                if (empty($subtypes)) {
                    return '';
                } else if (in_array($subtypes, sns_notification::BLOCK_IMMEDIATELY)) {
                    return \html_writer::span($subtypes, 'alert alert-danger p-2');
                } else if (in_array($subtypes, sns_notification::BLOCK_IMMEDIATELY)) {
                    return \html_writer::span($subtypes, 'alert alert-warning p-2');
                }
                return $subtypes;
            });

        // Time column.
        $columns[] = (new column(
            'time',
            new lang_string('time'),
            $this->get_entity_name()
        ))
            ->add_joins($this->get_joins())
            ->set_type(column::TYPE_TIMESTAMP)
            ->add_fields("{$tablealias}.time")
            ->set_is_sortable(true)
            ->add_callback([format::class, 'userdate'], get_string('strftimedatetimeshortaccurate', 'core_langconfig'));

        return $columns;
    }

    /**
     * Return list of all available filters
     *
     * @return filter[]
     */
    protected function get_all_filters(): array {
        $tablealias = $this->get_table_alias('tool_emailutils_log');

        // Email filter.
        $filters[] = (new filter(
            text::class,
            'email',
            new lang_string('email'),
            $this->get_entity_name(),
            "{$tablealias}.email"
        ))
            ->add_joins($this->get_joins());

        // Type filter.
        $filters[] = (new filter(
            text::class,
            'type',
            new lang_string('type', 'tool_emailutils'),
            $this->get_entity_name(),
            "{$tablealias}.type"
        ))
            ->add_joins($this->get_joins());

        // Subtypes filter.
        $filters[] = (new filter(
            text::class,
            'subtypes',
            new lang_string('subtypes', 'tool_emailutils'),
            $this->get_entity_name(),
            "{$tablealias}.subtypes"
        ))
            ->add_joins($this->get_joins());

        // Time filter.
        $filters[] = (new filter(
            date::class,
            'time',
            new lang_string('time'),
            $this->get_entity_name(),
            "{$tablealias}.time"
        ))
            ->add_joins($this->get_joins())
            ->set_limited_operators([
                date::DATE_ANY,
                date::DATE_RANGE,
                date::DATE_PREVIOUS,
                date::DATE_CURRENT,
            ]);

        return $filters;
    }
}
