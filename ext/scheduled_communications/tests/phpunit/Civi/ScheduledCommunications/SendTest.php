<?php
namespace Civi\ScheduledCommunications;

use CRM_ScheduledCommunications_ExtensionUtil as E;
use Civi\Api4\Activity;
use Civi\Test\CiviEnvBuilder;
use Civi\Test\HeadlessInterface;
use Civi\Core\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class SendTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

  use \Civi\Test\Api4TestTrait;

  /**
   * @var \CiviMailUtils
   */
  public $mut;

  /**
   * Setup for tests.
   */
  public function setUp(): void {
    parent::setUp();

    $this->mut = new \CiviMailUtils($this, TRUE);
  }

  public function tearDown(): void {
    \CRM_Utils_Time::resetTime();
    parent::tearDown();
  }

  public function setUpHeadless(): CiviEnvBuilder {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function testBirthdayMessage():void {
    $lastName = uniqid();
    $sampleContacts = [
      ['first_name' => 'A', 'last_name' => $lastName, 'email_primary.email' => "a@$lastName", 'birth_date' => '2020-02-29'],
      ['first_name' => 'AA', 'last_name' => $lastName, 'email_primary.email' => "aa@$lastName", 'birth_date' => '2017-02-28'],
      ['first_name' => 'B', 'last_name' => $lastName, 'email_primary.email' => "b@$lastName", 'birth_date' => '2019-02-19'],
      ['first_name' => 'C', 'last_name' => $lastName, 'email_primary.email' => "c@$lastName", 'birth_date' => '2018-02-09', 'is_deceased' => TRUE],
      ['email_primary.email' => "alt1@$lastName"],
    ];
    $cid = $this->saveTestRecords('Individual', [
      'records' => $sampleContacts,
    ])->column('id');
    $savedSearch = $this->createTestRecord('SavedSearch', [
      'label' => __FUNCTION__,
      'api_entity' => 'Individual',
      'api_params' => [
        'version' => 4,
        'select' => ['id', 'first_name', 'last_name', 'NEXTANNIV(birth_date) AS upcoming_birthday'],
        'where' => [['last_name', '=', $lastName]],
      ],
    ]);
    $this->createTestRecord('ActionSchedule', [
      'title' => __FUNCTION__,
      'mapping_id:name' => 'saved_search',
      'entity_value' => $savedSearch['id'],
      'entity_status' => 'id',
      'start_action_offset' => 1,
      'start_action_unit' => 'day',
      'start_action_condition' => 'before',
      'limit_to:name' => 'copy',
      'recipient' => 'manual',
      'recipient_manual' => [$cid[4]],
      'start_action_date' => 'upcoming_birthday',
      'body_html' => '<p>Your birthday is tomorrow!</p>',
      'subject' => 'Happy birthday {contact.first_name}!',
    ]);
    $this->assertCronRuns([
      [
        // No birthdays tomorrow
        'time' => '2025-04-02 04:00:00',
        'to' => [],
        'subjects' => [],
      ],
      [
        'time' => '2025-02-18 04:00:00',
        'to' => [["b@$lastName"]],
        'all_recipients' => ["b@$lastName;alt1@$lastName"],
        'subjects' => ['Happy birthday B!'],
      ],
      [
        // Upcoming birthday but contact is deceased
        'time' => '2025-02-08 04:00:00',
        'to' => [],
        'subjects' => [],
      ],
      [
        // On a non-leap-year, birthday is the 28th
        'time' => '2025-02-27 04:00:00',
        'to' => [["a@$lastName"], ["aa@$lastName"]],
        'all_recipients' => ["a@$lastName;alt1@$lastName", "aa@$lastName;alt1@$lastName"],
        'subjects' => ['Happy birthday A!', 'Happy birthday AA!'],
      ],
    ]);
  }

  public function testAlternateRecipients():void {
    $lastName = uniqid();
    $sampleContacts = [
      ['first_name' => 'A', 'last_name' => $lastName, 'email_primary.email' => "a@$lastName", 'birth_date' => '2020-02-29'],
      ['first_name' => 'B', 'last_name' => $lastName, 'email_primary.email' => "b@$lastName", 'birth_date' => '2020-02-29'],
      ['email_primary.email' => "alt1@$lastName"],
      ['email_primary.email' => "alt2@$lastName"],
    ];
    $cid = $this->saveTestRecords('Individual', [
      'records' => $sampleContacts,
    ])->column('id');
    $savedSearch = $this->createTestRecord('SavedSearch', [
      'label' => __FUNCTION__,
      'api_entity' => 'Individual',
      'api_params' => [
        'version' => 4,
        'select' => ['id', 'first_name', 'last_name', 'NEXTANNIV(birth_date) AS upcoming_birthday'],
        'where' => [['last_name', '=', $lastName]],
      ],
    ]);
    $this->createTestRecord('ActionSchedule', [
      'title' => __FUNCTION__,
      'mapping_id:name' => 'saved_search',
      'entity_value' => $savedSearch['id'],
      'entity_status' => 'id',
      'start_action_offset' => 1,
      'start_action_unit' => 'day',
      'start_action_condition' => 'before',
      'start_action_date' => 'upcoming_birthday',
      'limit_to:name' => 'reroute',
      'recipient' => 'manual',
      'recipient_manual' => [$cid[2], $cid[3]],
      'body_html' => "<p>Wish happy birthday to them.</p>",
      'subject' => "Tomorrow is {contact.first_name}'s birthday",
    ]);
    $this->assertCronRuns([
      [
        // No birthdays tomorrow
        'time' => '2025-04-02 04:00:00',
        'to' => [],
        'subjects' => [],
      ],
      [
        // Sending email to 2 alternate contacts instead of the birthday people
        'time' => '2025-02-27 04:00:00',
        'to' => [["alt1@$lastName", "alt2@$lastName"], ["alt1@$lastName", "alt2@$lastName"]],
        'subjects' => ["Tomorrow is A's birthday", "Tomorrow is B's birthday"],
      ],
    ]);
  }

  public function testSms(): void {
    $smsProvider = $this->getTestSmsProvider();
    $lastName = uniqid();
    $sampleContacts = [
      ['first_name' => 'A', 'last_name' => $lastName, 'phone_primary.phone' => "12345", 'birth_date' => '2020-02-29'],
      ['first_name' => 'B', 'last_name' => $lastName, 'phone_primary.phone' => "54321", 'birth_date' => '2019-02-01'],
      ['phone_primary.phone' => "67890"],
      ['phone_primary.phone' => "09876"],
    ];
    $cid = $this->saveTestRecords('Individual', [
      'records' => $sampleContacts,
      'defaults' => ['phone_primary.phone_type_id:name' => 'Mobile'],
    ])->column('id');
    $savedSearch = $this->createTestRecord('SavedSearch', [
      'label' => __FUNCTION__,
      'api_entity' => 'Individual',
      'api_params' => [
        'version' => 4,
        'select' => ['id', 'first_name', 'last_name', 'NEXTANNIV(birth_date) AS upcoming_birthday'],
        'where' => [['last_name', '=', $lastName]],
      ],
    ]);
    $this->createTestRecord('ActionSchedule', [
      'title' => __FUNCTION__,
      'mapping_id:name' => 'saved_search',
      'entity_value' => $savedSearch['id'],
      'entity_status' => 'id',
      'mode' => 'SMS',
      'sms_provider_id' => $smsProvider['id'],
      'start_action_offset' => 1,
      'start_action_unit' => 'day',
      'start_action_condition' => 'before',
      'start_action_date' => 'upcoming_birthday',
      'limit_to:name' => 'copy',
      'recipient' => 'manual',
      'recipient_manual' => [$cid[2], $cid[3]],
      'sms_body_text' => "Tomorrow is {contact.first_name}'s birthday",
    ]);
    $this->assertCronRuns([
      [
        'time' => '2025-02-27 04:00:00',
      ],
    ]);
    $activity = Activity::get(FALSE)
      ->addWhere('target_contact_id', 'CONTAINS', $cid[0])
      ->addSelect('activity_type_id:name', 'target_contact_id', '*')
      ->addOrderBy('id', 'DESC')
      ->execute()->single();
    $this->assertEquals("Tomorrow is A's birthday", $activity['details']);
    $this->assertEquals('SMS', $activity['activity_type_id:name']);
    $this->assertEquals([$cid[0], $cid[2], $cid[3]], $activity['target_contact_id']);
  }

  public function testGroupedRows():void {
    $lastName = uniqid();
    $activitySubject = "Meeting with the {$lastName}s";
    $sampleContacts = [
      ['first_name' => 'A', 'last_name' => $lastName, 'email_primary.email' => "a@$lastName"],
      ['first_name' => 'B', 'last_name' => $lastName, 'email_primary.email' => "b@$lastName"],
    ];
    $cid = $this->saveTestRecords('Individual', [
      'records' => $sampleContacts,
    ])->column('id');
    $activity1 = $this->createTestRecord('Activity', [
      'subject' => $activitySubject,
      'source_contact_id' => $cid[0],
      'assignee_contact_id' => [$cid[0], $cid[1]],
    ]);
    $activity2 = $this->createTestRecord('Activity', [
      'subject' => $activitySubject,
      'source_contact_id' => $cid[0],
      'assignee_contact_id' => [$cid[0]],
    ]);
    $savedSearch = $this->createTestRecord('SavedSearch', [
      'label' => '__FUNCTION__',
      'api_entity' => 'Activity',
      'api_params' => [
        'version' => 4,
        'join' => [
          [
            'Contact AS Activity_ActivityContact_Contact_01',
            'INNER',
            'ActivityContact',
            ['id', '=', 'Activity_ActivityContact_Contact_01.activity_id'],
          ],
        ],
        'groupBy' => ['id', 'Activity_ActivityContact_Contact_01.id'],
        'where' => [
          [
            'Activity_ActivityContact_Contact_01.record_type_id:name',
            'IN',
            ['Activity Source', 'Activity Assignees'],
          ],
          ['subject', '=', $activitySubject],
        ],
        'select' => ['id', 'activity_date_time', 'Activity_ActivityContact_Contact_01.id'],
      ],
    ]);
    $this->createTestRecord('ActionSchedule', [
      'title' => __FUNCTION__,
      'mapping_id:name' => 'saved_search',
      'entity_value' => $savedSearch['id'],
      'entity_status' => 'Activity_ActivityContact_Contact_01.id',
      'start_action_offset' => 0,
      'start_action_unit' => 'day',
      'start_action_condition' => 'before',
      'limit_to' => NULL,
      'recipient' => 'manual',
      'recipient_manual' => [],
      'start_action_date' => 'activity_date_time',
      'body_html' => 'You are the source or assignee of an activity',
      'subject' => 'Reminder: Activity {activity.id} today',
    ]);
    $this->assertCronRuns([
      [
        // Only one message should be sent per contact per activity,
        // no matter how many roles that contact plays on the activity
        'time' => $activity1['activity_date_time'],
        'message_count' => 3,
        'all_recipients' => ["a@$lastName", "b@$lastName", "a@$lastName"],
        'subjects' => [
          "Reminder: Activity {$activity1['id']} today",
          "Reminder: Activity {$activity1['id']} today",
          "Reminder: Activity {$activity2['id']} today",
        ],
      ],
    ]);
  }

  /**
   * Run a series of cron jobs and make an assertion about email deliveries.
   *
   * @param array $cronRuns
   *   array specifying when to run cron and what messages to expect; each item is an array with keys:
   *   - time: string, e.g. '2012-06-15 21:00:01'
   *   - recipients: array(array(string)), list of email addresses which should receive messages
   *
   * @throws \CRM_Core_Exception
   * @noinspection DisconnectedForeachInstructionInspection
   */
  public function assertCronRuns(array $cronRuns): void {
    foreach ($cronRuns as $cronRunKey => $cronRun) {
      \CRM_Utils_Time::setTime($cronRun['time']);
      civicrm_api3('job', 'send_reminder');

      $allMessages = $this->mut->getAllMessages('ezc');

      if (array_key_exists('message_count', $cronRun)) {
        $summary = sprintf("Cron Run #%s (%s). Found %d messages:\n", $cronRunKey, $cronRun['time'], count($allMessages));
        foreach ($allMessages as $message) {
          /** @var \ezcMail $message */
          $summary .= sprintf(" * %s (%s)\n", json_encode($message->subject), json_encode($message->to));
        }
        $this->assertEquals($cronRun['message_count'], count($allMessages), "Found wrong message count\n$summary");
      }
      if (array_key_exists('to', $cronRun)) {
        $this->mut->assertRecipients($cronRun['to']);
      }
      if (array_key_exists('all_recipients', $cronRun)) {
        $this->mut->assertRecipientEmails($cronRun['all_recipients']);
      }
      if (array_key_exists('subjects', $cronRun)) {
        $this->mut->assertSubjects($cronRun['subjects']);
      }
      $this->mut->clearMessages();
    }
  }

  protected function getTestSmsProvider(): array {
    global $civicrm_root;
    require_once $civicrm_root . '/tests/phpunit/CiviTest/CiviTestSMSProvider.php';
    $this->createTestRecord('OptionValue', ['option_group_id:name' => 'sms_provider_name', 'name' => 'dummy sms', 'label' => 'Dummy']);
    return $this->createTestRecord('SmsProvider', [
      'name' => 'CiviTestSMSProvider',
      'title' => 'Test',
      'username' => 'Test',
      'password' => 'Test',
      'api_type' => 1,
      'api_params' => 'From=+1234567890',
    ]);
  }

}
