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
 * @package    tool_emailses
 * @copyright  2018 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Harry Barnard <harry.barnard@catalyst-eu.net>
 */

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $ADMIN->add('tools', new admin_category(
        'tool_emailses',
        new lang_string('pluginname', 'tool_emailses')
    ));

    $ADMIN->add('tool_emailses', new admin_externalpage(
        'tool_emailses_list',
        new lang_string('list', 'tool_emailses'),
        new moodle_url('/admin/tool/emailses/index.php')
    ));

    // Plugin Settings Page
    $settings = new admin_settingpage(
        'tool_emailses_options',
        new lang_string('settings', 'tool_emailses')
    );

    // Enable Endpoint
    $settings->add(new admin_setting_configcheckbox(
        'tool_emailses/enabled',
        new lang_string('enabled', 'tool_emailses'),
        new lang_string('enabled_help', 'tool_emailses'),
        1)
    );
    // Auth Settings
    $settings->add(new admin_setting_heading(
        'authorisation',
        new lang_string('authorisationcategory', 'tool_emailses'),
        '')
    );
    // Auth Header
    $settings->add(new admin_setting_configtext(
        'tool_emailses/authorisation_header',
        new lang_string('header', 'tool_emailses'),
        new lang_string('header_help', 'tool_emailses'),
        'WWW-Authenticate: Basic realm="SNS Client"')
    );
    // Auth Username
    $settings->add(new admin_setting_configtext(
        'tool_emailses/authorisation_username',
        new lang_string('username', 'tool_emailses'),
        new lang_string('username_help', 'tool_emailses'),
        null)
    );
    // Auth Password
    $settings->add(new \tool_emailses\admin_setting_configpasswordhashed(
        'tool_emailses/authorisation_password',
        new lang_string('password', 'tool_emailses'),
        new lang_string('password_help', 'tool_emailses'),
        null)
    );

    $ADMIN->add('tool_emailses', $settings);

}
