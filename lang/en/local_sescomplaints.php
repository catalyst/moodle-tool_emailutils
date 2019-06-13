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

$string['pluginname'] = 'Amazon SES Complaints';

$string['list'] = 'Complaints List';
$string['settings'] = 'Settings';

$string['enabled'] = 'Enabled';
$string['enabled_help'] = 'Allow the plugin to process incoming messages';

$string['authorisationcategory'] = 'Authorisation Settings';
$string['header'] = 'Header';
$string['header_help'] = 'HTTP Basic Auth Header';
$string['username'] = 'Username';
$string['username_help'] = 'HTTP Basic Auth Username';
$string['password'] = 'Password';
$string['password_help'] = 'HTTP Basic Auth Password - Leave empty if you\'re not changing the password';

$string['event:notificationreceived'] = 'AWS SNS notification received';
