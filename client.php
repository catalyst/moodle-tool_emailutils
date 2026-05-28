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
 * An email bounce complaint handler webhook
 *
 * This is the endpoint that AWS notifies when it receives a complaint.
 * This handles the complaint by incrementing the users bounce level and
 * emiting a Moodle event.
 *
 * @package    tool_emailutils
 * @copyright  2018 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Harry Barnard <harry.barnard@catalyst-eu.net>
 */

use tool_emailutils\sns_client;

define('NO_MOODLE_COOKIES', true);

require_once(__DIR__ . '/../../../config.php');

if (!get_config('tool_emailutils', 'enabled')) {
    header('HTTP/1.1 403 Forbidden');
    echo "Not enabled\n";
    exit;
}

$client = new sns_client(
    get_config('tool_emailutils', 'authorisation_header'),
    get_config('tool_emailutils', 'authorisation_username'),
    get_config('tool_emailutils', 'authorisation_password')
);

if (!$client->is_authorised()) {
    header('HTTP/1.1 403 Forbidden');
    echo "Not authorised\n";
    exit;
}

if ($client->process_message() && $client->is_notification()) {
    $notification = $client->get_notification();
    $notification->process_notification();
}
