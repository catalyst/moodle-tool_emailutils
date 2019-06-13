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

define('AUTH_HEADER', 'WWW-Authenticate: Basic realm="SNS Client"');
define('AUTH_USER', 'awssns');
define('AUTH_PSWD', '8N5v7nvfbiX4xhY8');
define('MESSAGE_LOG_PATH', '/etc/messages.log');

require_once dirname(dirname(dirname(__FILE__))) . '/config.php';
require_once $CFG->dirroot . '/local/sescomplaints/lib/snsclient.php';

/**
 * Get the Auth Header Setting
 *
 * @return string Auth Header
 */
function get_header()
{
    global $DB;

    return $DB->get_field('config_plugins', 'value', array('plugin' => 'local_sescomplaints', 'name' => 'authorisation_header'), MUST_EXIST);
}

/**
 * Get the Auth username
 *
 * @return void
 */
function get_username()
{
    global $DB;

    return $DB->get_field('config_plugins', 'value', array('plugin' => 'local_sescomplaints', 'name' => 'authorisation_username'), MUST_EXIST);
}

function get_password_hash()
{
    global $DB;

    return $DB->get_field('config_plugins', 'value', array('plugin' => 'local_sescomplaints', 'name' => 'authorisation_password'), MUST_EXIST);
}

$client = new SNSClient(get_header(), get_username(), get_password_hash());
if ($client->isNotification()) {
    global $DB;

    $notification = $client->getNotification();
    $user = $DB->get_record_sql("SELECT id, email FROM {user} WHERE email LIKE '%" . $notification->getDestination() . "%'");

    if (strpos($user->email, 'invalid') === false) {
        if ($notification->isComplaint()) {
            $type = 'c';
        }  else if ($notification->isBounce()) {
            $type = 'b';
        } else {
            http_response_code(400); // Invalid request
            exit;
        }

        $record = new stdClass();
        $record->id = $user->id;
        $record->email = $user->email . '.' . $type . '.invalid';
        $DB->update_record('user', $record);
    }
}
