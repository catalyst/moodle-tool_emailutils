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
$string['incorrect_access'] = 'Incorrect access detected. For use only by AWS SNS.';
$string['bouncesreset'] = 'Bounces have been reset for the selected users';
$string['resetbounces'] = 'Reset the number of bounces';
$string['bouncecheckfull'] = 'Are you absolutely sure you want to reset the bounce count for {$a} ?';
$string['bouncecount'] = 'Bounce count';
$string['sendcount'] = 'Send count';
$string['configmissing'] = 'Missing config.php setting ($CFG->handlebounces) please review config-dist.php for more information.';

$string['event:notificationreceived'] = 'AWS SNS notification received';

// Complaints list strings.
$string['not_implemented'] = 'Not implemented yet. Search the user report for emails ending with ".b.invalid" and ".c.invalid".';
$string['bounces'] = 'For a list of bounces, visit {$a} and search for emails ending with ".b.invalid."';
$string['complaints'] = 'For a list of complaints, search for ".c.invalid"';
