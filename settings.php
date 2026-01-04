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
 * Add admin settings
 *
 * @package    tool_emailutils
 * @copyright  2018 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Harry Barnard <harry.barnard@catalyst-eu.net>
 */

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $ADMIN->add(
        'email',
        new admin_category(
            'tool_emailutils',
            new lang_string('pluginname', 'tool_emailutils')
        )
    );

    // DNS check settings.
    $settings = new admin_settingpage(
        'tool_emailutils_dns',
        new lang_string('dnssettings', 'tool_emailutils')
    );

    $settings->add(
        new admin_setting_configtext(
            'tool_emailutils/dnsspfinclude',
            new lang_string('dnsspfinclude', 'tool_emailutils'),
            new lang_string('dnsspfinclude_help', 'tool_emailutils'),
            ''
        )
    );

    $settings->add(
        new admin_setting_configtext(
            'tool_emailutils/postmastergoogletoken',
            new lang_string('postmastergoogletoken', 'tool_emailutils'),
            new lang_string('postmastergoogletoken_help', 'tool_emailutils'),
            '',
            PARAM_RAW,
            70
        )
    );

    $ADMIN->add('tool_emailutils', $settings);

    $ADMIN->add(
        'tool_emailutils',
        new admin_externalpage(
            'tool_emailutils_dkim',
            new lang_string('dkimmanager', 'tool_emailutils'),
            new moodle_url('/admin/tool/emailutils/dkim.php')
        )
    );

    // Plugin Settings Page.
    $settings = new admin_settingpage(
        'tool_emailutils_options',
        new lang_string('settings', 'tool_emailutils')
    );

    // Enable Endpoint.
    $settings->add(
        new admin_setting_configcheckbox(
            'tool_emailutils/enabled',
            new lang_string('enabled', 'tool_emailutils'),
            new lang_string('enabled_help', 'tool_emailutils'),
            0
        )
    );

    // Add the enable_suppression_list setting.
    $settings->add(
        new admin_setting_configcheckbox(
            'tool_emailutils/enable_suppression_list',
            new lang_string('enable_suppression_list', 'tool_emailutils'),
            new lang_string('enable_suppression_list_desc', 'tool_emailutils'),
            0
        )
    );

    $blocksetting = new admin_setting_configcheckbox(
        'tool_emailutils/block_bouncethreshold_enabled',
        new lang_string('block_bouncethreshold', 'tool_emailutils'),
        new lang_string('block_bouncethreshold_desc', 'tool_emailutils'),
        0
    );
    if (class_exists('\core\hook\email\before_email_to_user')) {
        $settings->add($blocksetting);
    }

    // Auth Settings.
    $settings->add(
        new admin_setting_heading(
            'authorisation',
            new lang_string('authorisationcategory', 'tool_emailutils'),
            ''
        )
    );
    // Auth Header.
    $settings->add(
        new admin_setting_configtext(
            'tool_emailutils/authorisation_header',
            new lang_string('header', 'tool_emailutils'),
            new lang_string('header_help', 'tool_emailutils'),
            'WWW-Authenticate: Basic realm="SNS Client"'
        )
    );
    // Auth Username.
    $settings->add(
        new admin_setting_configtext(
            'tool_emailutils/authorisation_username',
            new lang_string('username', 'tool_emailutils'),
            new lang_string('username_help', 'tool_emailutils'),
            ''
        )
    );
    // Auth Password.
    $settings->add(
        new \tool_emailutils\admin_setting_configpasswordhashed(
            'tool_emailutils/authorisation_password',
            new lang_string('password', 'tool_emailutils'),
            new lang_string('password_help', 'tool_emailutils'),
            ''
        )
    );

    // Add AWS credentials settings.
    $settings->add(
        new admin_setting_configtext(
            'tool_emailutils/aws_region',
            new lang_string('aws_region', 'tool_emailutils'),
            new lang_string('aws_region_desc', 'tool_emailutils'),
            '',
            PARAM_TEXT
        )
    );

    $settings->add(
        new admin_setting_configtext(
            'tool_emailutils/aws_key',
            new lang_string('aws_key', 'tool_emailutils'),
            new lang_string('aws_key_desc', 'tool_emailutils'),
            '',
            PARAM_TEXT
        )
    );

    $settings->add(
        new admin_setting_configpasswordunmask(
            'tool_emailutils/aws_secret',
            new lang_string('aws_secret', 'tool_emailutils'),
            new lang_string('aws_secret_desc', 'tool_emailutils'),
            ''
        )
    );

    $ADMIN->add('tool_emailutils', $settings);

    $ADMIN->add(
        'tool_emailutils',
        new admin_externalpage(
            'tool_emailutils_bounces',
            get_string('reportbounces', 'tool_emailutils'),
            new moodle_url('/admin/tool/emailutils/bounces.php')
        )
    );

    if (get_config('tool_emailutils', 'enable_suppression_list')) {
        $ADMIN->add(
            'email',
            new admin_externalpage(
                'tool_emailutils_suppressionlist',
                new lang_string('aws_suppressionlist', 'tool_emailutils'),
                new moodle_url('/admin/tool/emailutils/aws/suppression_list.php')
            )
        );
    }
}
