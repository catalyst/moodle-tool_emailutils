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
 * Amazon SNS Notification Class
 *
 * @package    tool_emailutils
 * @copyright  2018 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Harry Barnard <harry.barnard@catalyst-eu.net>
 */

namespace tool_emailutils;

use tool_emailutils\event\notification_received;
use tool_emailutils\event\over_bounce_threshold;

/**
 * Amazon SNS Notification Class
 *
 * Parses Amazon SES complaints and bounces contained in SNS Notification messages.
 */
class sns_notification {
    /** Bounce subtypes that should be blocked immediately */
    const BLOCK_IMMEDIATELY = [
        'Permanent:General',
        'Permanent:NoEmail',
        'Permanent:Suppressed',
        'Permanent:OnAccountSuppressionList',
    ];

    /** Bounce subtypes that should be blocked after a few failures */
    const BLOCK_SOFTLY = [
        'Undetermined:Undetermined',
        'Transient:General',
        'Transient:MailboxFull',
    ];

    /**
     * SNS Message
     * @var mixed
     */
    public $message;

    /**
     * Unprocessed SNS Message
     * @var mixed
     */
    public $messageraw;

    /**
     * Set SNS Message
     * @param mixed $message SNS Message
     * @return sns_notification
     */
    public function set_message($message): sns_notification {
        $this->messageraw = $message;
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
            http_response_code(400); // Invalid request.
            exit;
        }
    }

    /**
     * Get the raw message as provided by AWS
     * @return string Message as string
     */
    public function get_raw_message() {
        if (isset($this->messageraw)) {
            return $this->messageraw;
        } else {
            http_response_code(400); // Invalid request.
            exit;
        }
    }

    /**
     * Get SNS Message Type
     * @return string SNS Message Type
     */
    public function get_type(): string {
        return $this->message['notificationType'];
    }

    /**
     * Get the email address that sent out the offending email
     * @return string Source email address
     */
    public function get_source_email(): string {
        return $this->message['mail']['source'];
    }

    /**
     * Get the IP address of the server that sent out the offending email
     * @return string Source IP address
     */
    public function get_source_ip(): string {
        return $this->message['mail']['sourceIp'];
    }

    /**
     * Get the Amazon Resource Name that sent out the offending email
     * @return string Source ARN
     */
    public function get_source_arn(): string {
        return $this->message['mail']['sourceArn'];
    }

    /**
     * Get the email address that complained about or bounced the source email
     * @return string Destination email address
     */
    public function get_destination(): string {
        return $this->message['mail']['destination'][0];
    }

    /**
     * Gets the type of bounce that occured
     * https://docs.aws.amazon.com/ses/latest/dg/notification-contents.html#bounce-types
     * @return string Bounce type
     */
    public function get_bouncetype(): string {
        if ($this->is_bounce()) {
            return $this->message['bounce']['bounceType'];
        }
        return '';
    }

    /**
     * Gets the subtype of bounce that occured
     * https://docs.aws.amazon.com/ses/latest/dg/notification-contents.html#bounce-types
     * @return string Bounce sub type
     */
    public function get_bouncesubtype(): string {
        if ($this->is_bounce()) {
            return $this->message['bounce']['bounceSubType'];
        }
        return '';
    }

    /**
     * Gets the type of complaint that occured
     * https://docs.aws.amazon.com/ses/latest/dg/notification-contents.html#complaint-types
     * @return string Complaint type
     */
    public function get_complainttype(): string {
        if ($this->is_complaint()) {
            // Feedback type is only available if a feedback report is attached to the complaint.
            return $this->message['complaint']['complaintFeedbackType'] ?? '';
        }
        return '';
    }

    /**
     * Gets the subtype of complaint that occured
     * This should either be field can either be null or OnAccountSuppressionList
     * https://docs.aws.amazon.com/ses/latest/dg/notification-contents.html#complaint-object
     * @return string Complaint sub type
     */
    public function get_complaintsubtype(): string {
        if ($this->is_complaint()) {
            return $this->message['complaint']['complaintSubType'] ?? '';
        }
        return '';
    }

    /**
     * Returns all the message subtypes as an array
     * @return string subtypes as a string, split by ':'
     */
    protected function get_subtypes(): string {
        $subtypes = [];
        if ($this->is_complaint()) {
            $subtypes = [
                $this->get_complainttype(),
                $this->get_complaintsubtype(),
            ];
        } else if ($this->is_bounce()) {
            $subtypes = [
                $this->get_bouncetype(),
                $this->get_bouncesubtype(),
            ];
        }
        return implode(':', array_filter($subtypes));
    }

    /**
     * Is the message about a complaint?
     * @return bool Is complaint?
     */
    public function is_complaint(): bool {
        return $this->get_type() === sns_client::COMPLAINT_TYPE;
    }

    /**
     * Is the message about a bounce?
     * @return bool Is bounce?
     */
    public function is_bounce(): bool {
        return $this->get_type() === sns_client::BOUNCE_TYPE;
    }

    /**
     * Is the message about a delivery?
     * @return bool Is delivery?
     */
    public function is_delivery(): bool {
        return $this->get_type() === sns_client::DELIVERY_TYPE;
    }

    /**
     * Does this bounce type imply this should be blocked immediately?
     * @return bool block immediately?
     */
    public function should_block_immediately(): bool {
        return in_array($this->get_subtypes(), self::BLOCK_IMMEDIATELY);
    }

    /**
     * Does this bounce type imply this should be blocked softly?
     * @return bool block softly?
     */
    public function should_block_softly(): bool {
        return in_array($this->get_subtypes(), self::BLOCK_SOFTLY);
    }

    /**
     * Processes a delivery notification
     * @return void
     */
    protected function process_delivery_notification(): void {
        global $DB;

        // Only need to process notifications if consecutive bounce count is being used.
        if (!helper::use_consecutive_bounces()) {
            return;
        }

        $users = $DB->get_records('user', ['email' => $this->get_destination()], 'id ASC', 'id, email');
        foreach ($users as $user) {
            // Clear bounces on successful delivery when using consecutive bounce counts.
            if (!empty(get_user_preferences('email_bounce_count', 0, $user))) {
                helper::reset_bounce_count($user);
            }
        }
    }

    /**
     * Processes a bounce notification based on the subtype
     * @param \stdClass $user
     * @return void
     */
    protected function process_bounce_notification(\stdClass $user): void {
        if (helper::over_bounce_threshold($user)) {
            // Can occur if multiple notifications are received close together. No action required.
            return;
        }
        $sendcount = get_user_preferences('email_send_count', 0, $user);
        $bouncecount = get_user_preferences('email_bounce_count', 0, $user);

        if ($this->should_block_immediately()) {
            // User should only be able to recover from this if they change their email or have their bounces reset.
            $bouncecount = helper::get_min_bounces();
            set_user_preference('email_bounce_count', $bouncecount, $user);
        } else if ($this->should_block_softly()) {
            // Swap back to set_bounce_count($user) once MDL-73798 is integrated.
            $bouncecount++;
            set_user_preference('email_bounce_count', $bouncecount, $user);
        }

        // If send count isn't set (bug prior to MDL-73798) we need to set a placeholder.
        if (empty($sendcount) && !empty($bouncecount)) {
            set_user_preference('email_send_count', $bouncecount, $user);
        }

        if (helper::over_bounce_threshold($user)) {
            $event = over_bounce_threshold::create([
                'relateduserid' => $user->id,
                'context'  => \context_system::instance(),
            ]);
            $event->trigger();
        }
    }

    /**
     * Processes a notification based on the type
     * @return void
     */
    public function process_notification(): void {
        global $DB;

        if ($this->is_delivery()) {
            $this->process_delivery_notification();
            return;
        }

        if (!$this->is_complaint() && !$this->is_bounce()) {
            // Invalid request. We should never be here.
            http_response_code(400);
            return;
        }

        // Allow for shared emails. Only create a notification for the first user, but process all.
        $users = $DB->get_records('user', ['email' => $this->get_destination()], 'id ASC', 'id, email');
        $user = reset($users);

        // Log all bounces and complaints as an event, even if user is invalid.
        $event = notification_received::create([
            'relateduserid' => $user->id ?? null,
            'context'  => \context_system::instance(),
            'other' => $this->get_messageasstring(),
        ]);
        $event->trigger();

        if (empty($users)) {
            return;
        }

        if ($this->is_bounce()) {
            // Ideally bounce handling would be tracked per email instead of user.
            if (helper::use_consecutive_bounces()) {
                helper::check_consecutive_bounces($this->get_destination(), $users);
            }
            foreach ($users as $user) {
                $this->process_bounce_notification($user);
            }
        }

        // TODO: Implement complaint handling.

        // Save to emailutils log. This should be done last as processing may increase send count.
        // Time should represent when this was processed - send time will be in message if needed.
        $DB->insert_record('tool_emailutils_log', [
            'time' => time(),
            'type' => $this->get_type(),
            'subtypes' => $this->get_subtypes(),
            'email' => $this->get_destination(),
            'message' => $this->messageraw,
            'sendcount' => helper::get_sum_send_count($users),
        ]);
    }

    /**
     * Return the message as a string
     * Eg. "Type about x from y"
     * @return string Message as string
     */
    public function get_messageasstring(): string {
        if ($this->is_complaint() || $this->is_bounce()) {
            $subtypes = $this->get_subtypes();
            $subtypestring = !empty($subtypes) ? " ($subtypes)" : '';
            $type = $this->get_type() . $subtypestring;
            return $type . ' about ' . $this->get_source_email() . ' from ' . $this->get_destination();
        } else {
            http_response_code(400); // Invalid request.
            exit;
        }
    }

    /**
     * Print the message string
     * @return sns_notification
     */
    public function print_log(): sns_notification {
        echo $this->get_messageasstring() . "\n";
        return $this;
    }
}
