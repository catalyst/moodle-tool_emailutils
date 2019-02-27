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
 * @package    local_sescomplaints
 * @copyright  2018 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Harry Barnard <harry.barnard@catalyst-eu.net>
 */

defined('MOODLE_INTERNAL') || die;

require_once $CFG->dirroot . '/local/sescomplaints/lib/configpasswordhashed.php';

if ($hassiteconfig) {
    $ADMIN->add('localplugins', new admin_category(
        'local_sescomplaints',
        new lang_string('pluginname', 'local_sescomplaints')
    ));

    $ADMIN->add('local_sescomplaints', new admin_externalpage(
        'local_sescomplaints_list',
        new lang_string('list', 'local_sescomplaints'),
        new moodle_url('/local/sescomplaints/index.php')
    ));

    // Plugin Settings Page
    $settings = new admin_settingpage(
        'local_sescomplaints_options',
        new lang_string('settings', 'local_sescomplaints')
    );

    // Enable Endpoint
    $settings->add(new admin_setting_configcheckbox(
        'local_sescomplaints/enabled',
        new lang_string('enabled', 'local_sescomplaints'),
        new lang_string('enabled_help', 'local_sescomplaints'),
        1)
    );
    // Auth Settings
    $settings->add(new admin_setting_heading(
        'authorisation',
        new lang_string('authorisationcategory', 'local_sescomplaints'),
        '')
    );
    // Auth Header
    $settings->add(new admin_setting_configtext(
        'local_sescomplaints/authorisation_header',
        new lang_string('header', 'local_sescomplaints'),
        new lang_string('header_help', 'local_sescomplaints'),
        'WWW-Authenticate: Basic realm="SNS Client"')
    );
    // Auth Username
    $settings->add(new admin_setting_configtext(
        'local_sescomplaints/authorisation_username',
        new lang_string('username', 'local_sescomplaints'),
        new lang_string('username_help', 'local_sescomplaints'),
        null)
    );
    // Auth Password
    $settings->add(new admin_setting_configpasswordhashed(
        'local_sescomplaints/authorisation_password',
        new lang_string('password', 'local_sescomplaints'),
        new lang_string('password_help', 'local_sescomplaints'),
        null)
    );

    $ADMIN->add('local_sescomplaints', $settings);

}
