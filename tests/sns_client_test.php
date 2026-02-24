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

namespace tool_emailutils;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../lib/aws-sns-message-validator/src/Message.php');

use Aws\Sns\Message;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for SNS client.
 *
 * @package    tool_emailutils
 * @author     Benjamin Walker <benjaminwalker@catalyst-au.net>
 * @copyright  Catalyst IT 2024
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[
    CoversMethod(sns_client::class, '__construct'),
    CoversMethod(sns_notification::class, 'process_notification'),
]
final class sns_client_test extends \advanced_testcase {
    /**
     * Test email for user in unit test */
    const TEST_EMAIL = 'user@example.com';

    /**
     * Test required libs are installed.
     */
    public function test_lib(): void {
        // Use process message to ensure no errors from missing libs are produced.
        // This should result in an exception not an error.
        $this->expectException(\Exception::class);
        $client = new \tool_emailutils\sns_client('', '', '');
        $client->process_message();
    }

    /**
     * Creates a mock message
     * @param string $message message contents
     * @return string string json encoded message
     */
    private function get_mock_message(string $message): string {
        // @codingStandardsIgnoreStart
        return json_encode([
            "Type" => "Notification",
            "MessageId" => "165545c9-2a5c-472c-8df2-7ff2be2b3b1b",
            "TopicArn" => "arn:aws:sns:us-west-2:123456789012:MyTopic",
            "Message" => $message,
            "Timestamp" => "2012-04-26T20:45:04.751Z",
            "SignatureVersion" => "1",
            "Signature" => "EXAMPLEpH+DcEwjAPg8O9mY8dReBSwksfg2S7WKQcikcNKWLQjwu6A4VbeS0QHVCkhRS7fUQvi2egU3N858fiTDN6bkkOxYDVrY0Ad8L10Hs3zH81mtnPk5uvvolIC1CXGu43obcgFxeL3khZl8IKvO61GWB6jI9b5+gLPoBc1Q=",
            "SigningCertURL" => "https://sns.us-west-2.amazonaws.com/SimpleNotificationService-f3ecfb7224c7233fe7bb5f59f96de52f.pem",
            "UnsubscribeURL" => "https://sns.us-west-2.amazonaws.com/?Action=Unsubscribe&SubscriptionArn=arn:aws:sns:us-west-2:123456789012:MyTopic:c9135db0-26c4-47ec-8998-413945fb5a96"
        ]);
        // @codingStandardsIgnoreEnd
    }

    /**
     * Creates a mock delivery notification
     * @return \tool_emailutils\sns_notification
     */
    private function get_mock_delivery_notification(): sns_notification {
        $client = new sns_client('', '', '');

        // Create mock delivery notification using data from AWS example docs.
        $mockdelivery = json_encode([
            "notificationType" => "Delivery",
            "delivery" => [
                "timestamp" => "2012-05-25T14:59:35.605Z",
                "processingTimeMillis" => 546,
                "recipients" => [self::TEST_EMAIL],
                "smtpResponse" => "250 ok:  Message 64111812 accepted",
                "reportingMTA" => "a8-70.smtp-out.amazonses.com",
                "remoteMtaIp" => "127.0.2.0",
            ],
            "mail" => [
                "timestamp" => "2012-05-25T14:59:35.605Z",
                "source" => "sender@example.com",
                "destination" => [self::TEST_EMAIL],
            ],
        ]);

        $mockmessage = $this->get_mock_message($mockdelivery);
        $client->process_message(Message::fromJsonString($mockmessage));
        return $client->get_notification();
    }

    /**
     * Creates a mock bounce notification.
     * @param string $bouncetype
     * @param string $bouncesubtype
     * @return \tool_emailutils\sns_notification mock bounce notification
     */
    private function get_mock_bounce_notification(string $bouncetype, string $bouncesubtype): sns_notification {
        $client = new sns_client('', '', '');

        // Create mock bounces using data from AWS example docs.
        $mockbounce = json_encode([
            "notificationType" => "Bounce",
            "bounce" => [
                "bounceType" => $bouncetype,
                "bounceSubType" => $bouncesubtype,
                "bouncedRecipients" => [
                    [
                        "status" => "5.0.0",
                        "action" => "failed",
                        "diagnosticCode" => "smtp; 550 user unknown",
                        "emailAddress" => self::TEST_EMAIL,
                    ],
                ],
                "timestamp" => "2012-05-25T14:59:38.605Z",
                "feedbackId" => "000001378603176d-5a4b5ad9-6f30-4198-a8c3-b1eb0c270a1d-000000",
            ],
            "mail" => [
                "timestamp" => "2012-05-25T14:59:35.605Z",
                "source" => "sender@example.com",
                "destination" => [self::TEST_EMAIL],
            ],
        ]);

        $mockmessage = $this->get_mock_message($mockbounce);
        $client->process_message(Message::fromJsonString($mockmessage));
        return $client->get_notification();
    }

    /**
     * Data provider for {@see test_email_bounce_threshold}
     *
     * @return array
     */
    public static function bounce_processing_provider(): array {
        // To be tested with minbounces of 3 and bounceratio of -1.
        return [
            'Block immediately' => [
                'type' => 'Permanent',
                'subtype' => 'General',
                'notifications' => 1,
                'sendcount' => 1,
                'expectedbounces' => 3,
                'overthreshold' => true,
            ],
            'Block softly' => [
                'type' => 'Transient',
                'subtype' => 'General',
                'notifications' => 1,
                'sendcount' => 1,
                'expectedbounces' => 1,
                'overthreshold' => false,
            ],
            'Multiple block softly' => [
                'type' => 'Transient',
                'subtype' => 'General',
                'notifications' => 3,
                'sendcount' => 3,
                'expectedbounces' => 3,
                'overthreshold' => true,
            ],
            'Extra block softly' => [
                'type' => 'Transient',
                'subtype' => 'General',
                'notifications' => 4,
                'sendcount' => 4,
                'expectedbounces' => 3,
                'overthreshold' => true,
            ],
            'Do nothing' => [
                'type' => 'Transient',
                'subtype' => 'AttachmentRejected',
                'notifications' => 1,
                'sendcount' => 1,
                'expectedbounces' => 0,
                'overthreshold' => false,
            ],
            'Divide by 0 sendcount' => [
                'type' => 'Permanent',
                'subtype' => 'General',
                'notifications' => 1,
                'sendcount' => 0,
                'expectedbounces' => 3,
                'overthreshold' => true,
            ],
        ];
    }

    /**
     * Tests the email bounce thresholds
     *
     * @param string $type bounce type
     * @param string $subtype bounce subtype
     * @param int $notifications the number of notifications to process
     * @param int $sendcount the email send count
     * @param int $expectedbounces expected bounce conut
     * @param bool $overthreshold expected to be over the threshold
     **/
    #[DataProvider('bounce_processing_provider')]
    public function test_bounce_processing(
        string $type,
        string $subtype,
        int $notifications,
        int $sendcount,
        int $expectedbounces,
        bool $overthreshold
    ): void {
        global $CFG, $DB;

        // Setup config and users.
        $this->resetAfterTest();
        $CFG->handlebounces = true;
        $CFG->minbounces = 3;
        $CFG->bounceratio = -1;
        $CFG->allowaccountssameemail = true;

        $user1 = $this->getDataGenerator()->create_user(['email' => self::TEST_EMAIL]);
        $user2 = $this->getDataGenerator()->create_user(['email' => self::TEST_EMAIL]);
        set_user_preference('email_send_count', $sendcount, $user1->id);
        set_user_preference('email_send_count', $sendcount, $user2->id);

        // Process notification and store events.
        $sink = $this->redirectEvents();
        $notification = $this->get_mock_bounce_notification($type, $subtype);
        for ($i = 0; $i < $notifications; $i++) {
            $notification->process_notification();
        }
        $events = $sink->get_events();
        $sink->close();

        // Confirm bouncecount and over threshold.
        $this->assertEquals($expectedbounces, get_user_preferences('email_bounce_count', 0, $user1));
        $this->assertSame($overthreshold, helper::over_bounce_threshold($user1));

        // There should be one event for each bounce notification plus one threshold event per user over the threshold.
        $bounceevents = 2 * (int) $overthreshold;
        $this->assertCount($notifications + $bounceevents, $events);

        // Confirm bounce notification stored in emailutils log table.
        $records = $DB->get_records('tool_emailutils_log', null, 'id ASC');
        $this->assertCount($notifications, $records);

        // Confirm that shared email addresses have the same status.
        $this->assertSame($overthreshold, helper::over_bounce_threshold($user2));
    }

    /**
     * Tests the email delivery processing
     **/
    public function test_delivery_processing(): void {
        global $CFG, $DB;

        // Setup config and users.
        $this->resetAfterTest();
        $CFG->handlebounces = true;
        $CFG->minbounces = 3;
        $CFG->bounceratio = -1;
        $CFG->allowaccountssameemail = true;

        $user1 = $this->getDataGenerator()->create_user(['email' => self::TEST_EMAIL]);
        $user2 = $this->getDataGenerator()->create_user(['email' => self::TEST_EMAIL]);
        $user3 = $this->getDataGenerator()->create_user(['email' => self::TEST_EMAIL]);

        // Testing 3 scenarios with a single notification - above minbounces, below minbounces and no bounces.
        set_user_preference('email_bounce_count', 5, $user1->id);
        set_user_preference('email_bounce_count', 2, $user2->id);
        set_user_preference('email_bounce_count', 0, $user3->id);

        $this->assertTrue(helper::use_consecutive_bounces());

        // Process notification and store events.
        $sink = $this->redirectEvents();
        $notification = $this->get_mock_delivery_notification();
        $notification->process_notification();

        $events = $sink->get_events();
        $sink->close();

        // Confirm both were reset.
        $this->assertEquals(0, get_user_preferences('email_bounce_count', 0, $user1->id));
        $this->assertEquals(0, get_user_preferences('email_bounce_count', 0, $user2->id));
        $this->assertFalse(helper::over_bounce_threshold($user1));
        $this->assertFalse(helper::over_bounce_threshold($user2));

        // There should be one event for each user who had their bounce count reset.
        // This also confirms the third user didn't have their count reset.
        $this->assertCount(2, $events);

        // Confirm delivery notification isn't stored in emailutils log table.
        $records = $DB->get_records('tool_emailutils_log');
        $this->assertCount(0, $records);

        // Ensure bounces aren't reset when bounce ratio config is positive.
        $CFG->bounceratio = 0.5;
        $this->assertFalse(helper::use_consecutive_bounces());
        set_user_preference('email_bounce_count', 5, $user1->id);
        $notification->process_notification();
        $this->assertEquals(5, get_user_preferences('email_bounce_count', 0, $user1->id));
    }
}
