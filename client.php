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
use tool_emailutils\event\notification_received;

require_once(__DIR__ . '/../../../config.php');

if (!get_config('tool_emailutils', 'enabled')) {
    exit;
}

$client = new sns_client(get_config('tool_emailutils', 'authorisation_header'),
    get_config('tool_emailutils', 'authorisation_username'), get_config('tool_emailutils', 'authorisation_password'));

if (!$client->is_authorised()) {
    exit;
}

if ($client->process_message() && $client->is_notification()) {
    global $DB;

    $notification = $client->get_notification();

    $user = $DB->get_record('user', ['email' => $notification->get_destination()], 'id, email');

    if ($user) {
        if ($notification->is_complaint()) {
            $type = 'c';
        } else if ($notification->is_bounce()) {
            $type = 'b';
        } else {
            http_response_code(400); // Invalid request.
            exit;
        }

        // Increment the user preference email_bounce_count.
        set_bounce_count($user);

        $event = notification_received::create(array(
            'relateduserid' => $user->id,
            'context'  => context_system::instance(),
            'other' => $notification->get_messageasstring(),
        ));
        $event->trigger();
    }
}
