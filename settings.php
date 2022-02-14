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
 * @package    tool_emailutils
 * @copyright  2018 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Harry Barnard <harry.barnard@catalyst-eu.net>
 */

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $ADMIN->add('tools', new admin_category(
        'tool_emailutils',
        new lang_string('pluginname', 'tool_emailutils')
    ));

    $ADMIN->add('tool_emailutils', new admin_externalpage(
        'tool_emailutils_list',
        new lang_string('list', 'tool_emailutils'),
        new moodle_url('/admin/tool/emailutils/index.php')
    ));

    // Plugin Settings Page.
    $settings = new admin_settingpage(
        'tool_emailutils_options',
        new lang_string('settings', 'tool_emailutils')
    );

    // Enable Endpoint.
    $settings->add(new admin_setting_configcheckbox(
        'tool_emailutils/enabled',
        new lang_string('enabled', 'tool_emailutils'),
        new lang_string('enabled_help', 'tool_emailutils'),
        0)
    );
    // Auth Settings.
    $settings->add(new admin_setting_heading(
        'authorisation',
        new lang_string('authorisationcategory', 'tool_emailutils'),
        '')
    );
    // Auth Header.
    $settings->add(new admin_setting_configtext(
        'tool_emailutils/authorisation_header',
        new lang_string('header', 'tool_emailutils'),
        new lang_string('header_help', 'tool_emailutils'),
        'WWW-Authenticate: Basic realm="SNS Client"')
    );
    // Auth Username.
    $settings->add(new admin_setting_configtext(
        'tool_emailutils/authorisation_username',
        new lang_string('username', 'tool_emailutils'),
        new lang_string('username_help', 'tool_emailutils'),
        null)
    );
    // Auth Password.
    $settings->add(new \tool_emailutils\admin_setting_configpasswordhashed(
        'tool_emailutils/authorisation_password',
        new lang_string('password', 'tool_emailutils'),
        new lang_string('password_help', 'tool_emailutils'),
        null)
    );

    $ADMIN->add('tool_emailutils', $settings);

}
