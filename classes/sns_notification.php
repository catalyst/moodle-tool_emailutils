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

namespace local_sescomplaints;

use local_sescomplaints\sns_client;

/**
 * Amazon SNS Notification Class
 *
 * Parses Amazon SES complaints and bounces contained in SNS Notification messages.
 */
class sns_notification {

    /**
     * SNS Message
     * @var mixed
     */
    public $message;

    /**
     * Unprocessed SNS Message
     * @var mixed
     */
    public $message_raw;

    /**
     * Set SNS Message
     * @param mixed $message SNS Message
     * @return sns_notification
     */
    public function set_message($message) : sns_notification {
        $this->message_raw = $message;
        $this->message = json_decode($message, true);
        return $this;
    }

    /**
     * Get SNS Message
     * @return mixed SNS Message
     */
    public function get_message() {
        if (isset($this->message)) {
            return $this->message;
        } else {
            http_response_code(400); // Invalid request
            exit;
        }
    }

    /**
     * Get the raw message as provided by AWS
     * @return string Message as string
     */
    public function get_raw_message() {
        if (isset($this->message_raw)) {
            return $this->message_raw;
        } else {
            http_response_code(400); // Invalid request
            exit;
        }
    }

    /**
     * Get SNS Message Type
     * @return string SNS Message Type
     */
    public function get_type() : string {
        return $this->message['notificationType'];
    }

    /**
     * Get the email address that sent out the offending email
     * @return string Source email address
     */
    public function get_source_email() : string {
        return $this->message['mail']['source'];
    }

    /**
     * Get the IP address of the server that sent out the offending email
     * @return string Source IP address
     */
    public function get_source_ip() : string {
        return $this->message['mail']['sourceIp'];
    }

    /**
     * Get the Amazon Resource Name that sent out the offending email
     * @return string Source ARN
     */
    public function get_source_arn() : string {
        return $this->message['mail']['sourceArn'];
    }

    /**
     * Get the email address that complained about or bounced the source email
     * @return string Destination email address
     */
    public function get_destination() :string {
        return $this->message['mail']['destination'][0];
    }

    /**
     * Is the message about a complaint?
     * @return boolean Is complaint?
     */
    public function is_complaint() : boolean {
        return ($this->get_type() === sns_client::COMPLAINT_TYPE ? true : false);
    }

    /**
     * Is the message about a bounce?
     * @return boolean Is bounce?
     */
    public function is_bounce() : boolean {
        return ($this->get_type() === sns_client::BOUNCE_TYPE ? true : false);
    }

    /**
     * Return the message as a string
     * Eg. "Type about x from y"
     * @return string Message as string
     */
    public function get_messageasstring() : boolean {
        if ($this->is_complaint() || $this->is_bounce()) {
            return $this->get_type() . ' about ' . $this->get_source_email() . ' from ' . $this->get_destination();
        } else {
            http_response_code(400); // Invalid request
            exit;
        }
    }

    /**
     * Log the notification message to a given file
     * @param  string $path File path
     * @return sns_notification
     */
    public function log($path) : sns_notification {
        $file = new SplFileObject($path, 'a');
        $file->fwrite($this->get_raw_message() . "\n");
        return $this;
    }

    /**
     * Print the message string
     * @return sns_notification
     */
    public function print_log() : sns_notification {
        echo $this->get_messageasstring() . "\n";
        return $this;
    }
}