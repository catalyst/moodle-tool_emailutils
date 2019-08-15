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

require __DIR__ . '/../vendor/autoload.php';

use Aws\Sns\Exception\InvalidSnsMessageException;
use Aws\Sns\Message;
use Aws\Sns\MessageValidator;
use GuzzleHttp\Client;

define('SUBSCRIPTION_TYPE', 'SubscriptionConfirmation');
define('UNSUBSCRIPTION_TYPE', 'UnsubscribeConfirmation');
define('NOTIFICATION_TYPE', 'Notification');
define('COMPLAINT_TYPE', 'Complaint');
define('BOUNCE_TYPE', 'Bounce');

class SNSClientException extends Exception
{}

/**
 * Amazon SNS Notification
 *
 * Parses Amazon SES complaints and bounces contained in SNS Notification messages.
 */
class SNSNotification
{

    /**
     * SNS Message
     * @var mixed
     */
    public $message;

    public $message_raw;

    /**
     * Set SNS Message
     * @param mixed $message SNS Message
     * @return SNSNotification
     */
    public function setMessage($message)
    {
        $this->message_raw = $message;
        $this->message = json_decode($message, true);
        return $this;
    }

    /**
     * Get SNS Message
     * @return mixed SNS Message
     * @throws SNSClientException
     */
    public function getMessage()
    {
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
    public function getRawMessage()
    {
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
    public function getType()
    {
        return $this->message['notificationType'];
    }

    /**
     * Get the email address that sent out the offending email
     * @return string Source email address
     */
    public function getSourceEmail()
    {
        return $this->message['mail']['source'];
    }

    /**
     * Get the IP address of the server that sent out the offending email
     * @return string Source IP address
     */
    public function getSourceIP()
    {
        return $this->message['mail']['sourceIp'];
    }

    /**
     * Get the Amazon Resource Name that sent out the offending email
     * @return string Source ARN
     */
    public function getSourceArn()
    {
        return $this->message['mail']['sourceArn'];
    }

    /**
     * Get the email address that complained about or bounced the source email
     * @return string Destination email address
     */
    public function getDestination()
    {
        return $this->message['mail']['destination'][0];
    }

    /**
     * Is the message about a complaint?
     * @return boolean Is complaint?
     */
    public function isComplaint()
    {
        return ($this->getType() === COMPLAINT_TYPE ? true : false);
    }

    /**
     * Is the message about a bounce?
     * @return boolean Is bounce?
     */
    public function isBounce()
    {
        return ($this->getType() === BOUNCE_TYPE ? true : false);
    }

    /**
     * Return the message as a string
     * Eg. "Type about x from y"
     * @return string Message as string
     */
    public function getMessageAsString()
    {
        if ($this->isComplaint() || $this->isBounce()) {
            return $this->getType() . ' about ' . $this->getSourceEmail() . ' from ' . $this->getDestination();
        } else {
            http_response_code(400); // Invalid request
            exit;
        }
    }

    /**
     * Log the notification message to a given file
     * @param  string $path File path
     * @return SNSNotification
     */
    public function log($path)
    {
        $file = new SplFileObject($path, 'a');
        $file->fwrite($this->getRawMessage() . "\n");
        return $this;
    }

    /**
     * Print the message string
     * @return SNSNotification
     */
    public function printLog()
    {
        echo $this->getMessageAsString() . "\n";
        return $this;
    }
}

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
class SNSClient
{

    /**
     * SNS Message Object
     * @var Aws\Sns\Message
     */
    private $_message;

    /**
     * Guzzle HTTP Client
     * @var GuzzleHttp\Client
     */
    private $_client;

    /**
     * SNS Message Validator
     * @var Aws\Sns\MessageValidator
     */
    private $_validator;

    /**
     * SNS Notification Object
     * @var SNSNotification
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
    public function __construct($header, $username, $password)
    {

        // Make sure the request is POST.
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405); // Method not allowed.
            print(get_string('incorrect_access', 'local_sescomplaints'));
            exit;
        }

        /**
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
        } elseif (isset($SERVER['PHP_AUTH_USER'])) {
            // Credentials supplied - check they are valid.
            if (!_verifyUsername($username, $_SERVER['PHP_AUTH_USER']) && !_verifyPassword($password, $_SERVER['PHP_AUTH_PW'])) {
                // Invalid credentials!
                http_response_code(401); // Unauthorized
                exit;
            }
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            // Some servers don't provide the credentials seperately so strip them out of the auth header.
            $header_username = null;
            $header_password = null;
            if (strpos(strtolower($_SERVER['HTTP_AUTHORIZATION']), 'basic') === 0) {
                list($header_username, $header_password) = explode(':', base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));
            }

            if (is_null($header_username) || (!_verifyUsername($username, $_SERVER['PHP_AUTH_USER']) && !_verifyPassword($password, $_SERVER['PHP_AUTH_PW']))) {
                // Invalid credentials!
                http_response_code(401); // Unauthorized.
                exit;
            }
        }

        $this->_validator = new MessageValidator();
        $this->_client = new GuzzleHttp\Client();
        $this->notification = new SNSNotification();

        // Get the message from the POST data.
        $this->_message = Message::fromRawPostData();

        // Validate the incoming message.
        try {
            $this->_validator->validate($this->_message);
        } catch (InvalidSnsMessageException $e) {
            // Message not valid!
            http_response_code(400); // Bad request.
            exit;
        }

        // Process the message depending on it's type
        switch ($this->_message['Type']) {
            case SUBSCRIPTION_TYPE:
                // This is a subscription request so get the provided URL to confirm it
                $this->_client->get($this->_message['SubscribeURL']);
                break;
            case UNSUBSCRIPTION_TYPE:
                // This is an unsubscribe request. Since we probably didn't want to unsubscribe we'll
                // resubscribe by getting the subscribe URL
                $this->_client->get($this->_message['SubscribeURL']);
                break;
            case NOTIFICATION_TYPE:
                // This is a notification so set the message
                $this->_setMessage($this->_message);
                break;
            default:
                // We're not interested in other message types
                http_response_code(405); // Method not allowed
                exit;
                break;
        }

        return $this;
    }

    /**
     * Verify given auth usernames match
     *
     * @param string $username Username from settings
     * @param string $header_username Username given in auth header
     * @return boolean Is verified?
     */
    private function _verifyUsername($username, $header_username)
    {
        return ($header_username === $username ? true : false);
    }

    /**
     * Verify given password hash matches password in auth header
     *
     * @param string $password Password hash
     * @param string $header_password Password given in auth header
     * @return boolean Is verified?
     */
    private function _verifyPassword($password, $header_password)
    {
        return (password_verify($header_password, $password) ? true : false);
    }

    /**
     * Set SNS Message
     *
     * @param  Message $message Aws\Sns\Message
     * @return void
     */
    private function _setMessage(Message $message)
    {
        $this->notification->setMessage($message['Message']);

        return $this;
    }

    /**
     * Check if Message is of a notification type
     *
     * @return boolean Is of notification type
     */
    public function isNotification()
    {
        return ($this->_message['Type'] === NOTIFICATION_TYPE ? true : false);
    }

    /**
     * Check if Message is of a subscription type
     *
     * @return boolean Is of subscription type
     */
    public function isSubscription()
    {
        return ($this->_message['Type'] === NOTIFICATION_TYPE ? true : false);
    }

    /**
     * Check if Message is of an unsubscribe type
     *
     * @return boolean Is of unsubscribe type
     */
    public function isUnsubscription()
    {
        return ($this->_message['Type'] === NOTIFICATION_TYPE ? true : false);
    }

    /**
     * Get SNS Notification object
     *
     * @return SNSNotification SNS Notification Object
     */
    public function getNotification()
    {
        if (isset($this->notification)) {
            return $this->notification;
        }
        return;
    }
}
