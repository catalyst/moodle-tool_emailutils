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
    global $DB;
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

    return true;
}
