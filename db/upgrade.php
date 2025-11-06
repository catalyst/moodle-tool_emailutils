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
 * Email utils plugin upgrade code.
 *
 * @package    tool_emailutils
 * @copyright  2024 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Waleed ul hassan <waleed.hassan@catalyst-eu.net>
 */

/**
 * Upgrade script for the email utilities tool plugin.
 *
 * This function is executed during the plugin upgrade process.
 * It checks the current version of the plugin and applies
 * necessary upgrades, such as creating new database tables
 * or modifying existing structures.
 *
 * @param int $oldversion The version we are upgrading from.
 * @return bool Always returns true.
 */
function xmldb_tool_emailutils_upgrade($oldversion) {
    global $DB, $CFG;
    $dbman = $DB->get_manager();

    if ($oldversion < 2024100101) {

        // Define table tool_emailutils_suppression to be created.
        $table = new xmldb_table('tool_emailutils_suppression');

        // Adding fields to table tool_emailutils_suppression.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('email', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('reason', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
        $table->add_field('created_at', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table tool_emailutils_suppression.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Conditionally launch create table for tool_emailutils_suppression.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Emailutils savepoint reached.
        upgrade_plugin_savepoint(true, 2024100101, 'tool', 'emailutils');
    }

    if ($oldversion < 2024111800) {

        // The stored timestamps have lost timezones. These are replaced daily so easier to just remove instead of fix.
        $DB->delete_records('tool_emailutils_suppression');

        // Changing type of field created_at on table tool_emailutils_suppression to int.
        $table = new xmldb_table('tool_emailutils_suppression');
        $field = new xmldb_field('created_at', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'reason');

        // Launch change of type for field created_at.
        $dbman->change_field_type($table, $field);

        // Emailutils savepoint reached.
        upgrade_plugin_savepoint(true, 2024111800, 'tool', 'emailutils');
    }

    if ($oldversion < 2024112801) {

        // Define field subtypes to be added to tool_emailutils_log.
        $table = new xmldb_table('tool_emailutils_log');
        $field = new xmldb_field('subtypes', XMLDB_TYPE_CHAR, '32', null, null, null, null, 'type');

        // Conditionally launch add field subtypes.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field sendcount to be added to tool_emailutils_log.
        $field = new xmldb_field('sendcount', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'message');

        // Conditionally launch add field sendcount.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Emailutils savepoint reached.
        upgrade_plugin_savepoint(true, 2024112801, 'tool', 'emailutils');
    }

    if ($oldversion < 2025110601) {
        // Copy old 'enabled' config to 'enable_bounce_processing'.
        // This setting still does the same thing, it was just renamed.
        $enabled = get_config('tool_emailutils', 'enabled');
        set_config('enable_bounce_processing', $enabled, 'tool_emailutils');

        // Copy $CFG->minbounces to new plugin-specific config (if set).
        if (!empty($CFG->minbounces)) {
            set_config('minbounces', $CFG->minbounces, 'tool_emailutils');
        }

        // Copy $CFG->bounceratio to new plugin-specific config (if set).
        if (!empty($CFG->bounceratio)) {
            set_config('minbounces', $CFG->bounceratio, 'tool_emailutils');
        }

        // Emailutils savepoint reached.
        upgrade_plugin_savepoint(true, 2025110601, 'tool', 'emailutils');
    }

    return true;
}
