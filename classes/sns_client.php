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

namespace tool_emailses;


use Aws\Sns\Exception\InvalidSnsMessageException;
use Aws\Sns\Message;
use Aws\Sns\MessageValidator;
use GuzzleHttp\Client;

/**
 * Amazon SNS Client Interface
 *
 * Example usage:
 *
 * $client = new SNSClient('WWW-Authenticate: Basic realm="SNS Client"', 'username' , 'password');
 * if($client->isNotification()) {
 *     $notification = $client->getNotification();
 *     $notification->log('/path/to/logfile')->print();
 * }
 */
class sns_client {

    const SUBSCRIPTION_TYPE = 'SubscriptionConfirmation';

    const UNSUBSCRIPTION_TYPE = 'UnsubscribeConfirmation';

    const NOTIFICATION_TYPE = 'Notification';

    const COMPLAINT_TYPE = 'Complaint';

    const BOUNCE_TYPE = 'Bounce';

    /**
     * SNS Message Object
     * @var \Aws\Sns\Message
     */
    protected $message;

    /**
     * Guzzle HTTP Client
     * @var \GuzzleHttp\Client
     */
    protected $client;

    /**
     * SNS Message Validator
     * @var \Aws\Sns\MessageValidator
     */
    protected $validator;

    /**
     * SNS Notification Object
     * @var sns_notification
     */
    public $notification;

    /**
     * Constructor
     *
     * Creates an endpoint and reacts to posted messages
     *
     * @param string $header Auth header
     * @param string $username Auth username
     * @param string $password Auth password hash
     */
    public function __construct($header, $username, $password) {

        // Make sure the request is POST.
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405); // Method not allowed.
            print(get_string('incorrect_access', 'tool_emailses'));
            exit;
        }

        /*
         * Request Authorisation
         * Note: AWS SNS sends two requests to an endpoint with basic auth. The first is without the Authorization
         * header. When it receives a 401 status it repeats the request with the Authorization header set. This
         * is to replicate the behaviour of a user encountering then completing a credentials modal dialog in a
         * browser.
         */
        if (!isset($_SERVER['PHP_AUTH_USER'])) {
            // No credentials provided - ask for some!
            header($header);
            header('HTTP/1.0 401 Unauthorized');
            exit;
        } else if (isset($_SERVER['PHP_AUTH_USER'])) {
            // Credentials supplied - check they are valid.
            if (!static::verify_username($username, $_SERVER['PHP_AUTH_USER']) &&
                !static::verify_password($password, $_SERVER['PHP_AUTH_PW'])) {
                // Invalid credentials!
                http_response_code(401); // Unauthorized.
                exit;
            }
        } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            // Some servers don't provide the credentials seperately so strip them out of the auth header.
            $headerusername = null;
            $headerpassword = null;
            if (strpos(strtolower($_SERVER['HTTP_AUTHORIZATION']), 'basic') === 0) {
                list($headerusername, $headerpassword) = explode(':', base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));
            }

            if (is_null($headerusername) || (!static::verify_username($username, $_SERVER['PHP_AUTH_USER']) &&
                !static::verify_password($password, $_SERVER['PHP_AUTH_PW']))) {
                // Invalid credentials!
                http_response_code(401); // Unauthorized.
                exit;
            }
        }

        $this->validator = new MessageValidator();
        $this->client = new Client();
        $this->notification = new sns_notification();

        // Get the message from the POST data.
        $this->message = Message::fromRawPostData();

        // Validate the incoming message.
        try {
            $this->validator->validate($this->message);
        } catch (InvalidSnsMessageException $e) {
            // Message not valid!
            http_response_code(400); // Bad request.
            exit;
        }

        // Process the message depending on it's type.
        switch ($this->message['Type']) {
            case self::SUBSCRIPTION_TYPE:
                // This is a subscription request so get the provided URL to confirm it.
                $this->client->get($this->message['SubscribeURL']);
                break;
            case self::UNSUBSCRIPTION_TYPE:
                // This is an unsubscribe request. Since we probably didn't want to unsubscribe we'll
                // resubscribe by getting the subscribe URL.
                $this->client->get($this->message['SubscribeURL']);
                break;
            case self::NOTIFICATION_TYPE:
                // This is a notification so set the message.
                $this->set_message($this->message);
                break;
            default:
                // We're not interested in other message types.
                http_response_code(405); // Method not allowed.
                exit;
                break;
        }

        return $this;
    }

    /**
     * Verify given auth usernames match
     *
     * @param string $username Username from settings
     * @param string $headerusername Username given in auth header
     * @return boolean Is verified?
     */
    public static function verify_username($username, $headerusername) {
        return ($headerusername === $username ? true : false);
    }

    /**
     * Verify given password hash matches password in auth header
     *
     * @param string $password Password hash
     * @param string $headerpassword Password given in auth header
     * @return boolean Is verified?
     */
    public static function verify_password($password, $headerpassword) : boolean {
        return (password_verify($headerpassword, $password) ? true : false);
    }

    /**
     * Set SNS Message
     *
     * @param  Message $message Aws\Sns\Message
     * @return void
     */
    protected function set_message(Message $message) : sns_client {
        $this->notification->set_message($message['Message']);

        return $this;
    }

    /**
     * Check if Message is of a notification type
     *
     * @return boolean Is of notification type
     */
    public function is_notification() : boolean {
        return ($this->message['Type'] === self::NOTIFICATION_TYPE ? true : false);
    }

    /**
     * Check if Message is of a subscription type
     *
     * @return boolean Is of subscription type
     */
    public function is_subscription() : boolean {
        return ($this->message['Type'] === self::NOTIFICATION_TYPE ? true : false);
    }

    /**
     * Check if Message is of an unsubscribe type
     *
     * @return boolean Is of unsubscribe type
     */
    public function is_unsubscription() : boolean {
        return ($this->message['Type'] === self::NOTIFICATION_TYPE ? true : false);
    }

    /**
     * Get SNS Notification object
     *
     * @return sns_notification SNS Notification Object
     */
    public function get_notification() {
        if (isset($this->notification)) {
            return $this->notification;
        }
        return;
    }
}
