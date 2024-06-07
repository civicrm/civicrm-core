<?php
namespace Civi\ScheduledCommunications;

use CRM_ScheduledCommunications_ExtensionUtil as E;
use Civi\Test\CiviEnvBuilder;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class SendTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

  use \Civi\Test\Api4TestTrait;

  /**
   * @var CiviMailUtils
   */
  public $mut;

  /**
   * Setup for tests.
   */
  public function setUp(): void {
    parent::setUp();

    $this->mut = new \CiviMailUtils($this, TRUE);
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
      ['first_name' => 'B', 'last_name' => $lastName, 'email_primary.email' => "b@$lastName", 'birth_date' => '2019-02-19'],
      ['first_name' => 'C', 'last_name' => $lastName, 'email_primary.email' => "c@$lastName", 'birth_date' => '2018-02-09', 'is_deceased' => TRUE],
    ];
    $this->saveTestRecords('Individual', [
      'records' => $sampleContacts,
    ]);
    $savedSearch = $this->createTestRecord('SavedSearch', [
      'label' => __FUNCTION__,
      'api_entity' => 'Individual',
      'api_params' => [
        'version' => 4,
        'select' => ['id', 'first_name', 'last_name', 'NEXTANNIV(birth_date) AS upcoming_birthday'],
        'where' => [['last_name', '=', $lastName]],
      ],
    ]);
    $actionSchedule = $this->createTestRecord('ActionSchedule', [
      'title' => __FUNCTION__,
      'mapping_id:name' => 'saved_search',
      'entity_value' => $savedSearch['id'],
      'entity_status' => 'id',
      'start_action_offset' => 1,
      'start_action_unit' => 'day',
      'start_action_condition' => 'before',
      'start_action_date' => 'upcoming_birthday',
      'body_html' => '<p>Your birthday is tomorrow!</p>',
      'subject' => 'Happy birthday {contact.first_name}!',
    ]);
    $this->assertCronRuns([
      [
        // No birthdays tomorrow
        'time' => '2025-04-02 04:00:00',
        'recipients' => [],
        'subjects' => [],
      ],
      [
        'time' => '2025-02-18 04:00:00',
        'recipients' => [["b@$lastName"]],
        'subjects' => ['Happy birthday B!'],
      ],
      [
        'time' => '2025-02-08 04:00:00',
        'recipients' => [],
        'subjects' => [],
      ],
      [
        // On a non-leap-year, birthday is the 28th
        'time' => '2025-02-27 04:00:00',
        'recipients' => [["a@$lastName"]],
        'subjects' => ['Happy birthday A!'],
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
    foreach ($cronRuns as $cronRun) {
      \CRM_Utils_Time::setTime($cronRun['time']);
      civicrm_api3('job', 'send_reminder');
      $this->mut->assertRecipients($cronRun['recipients']);
      if (array_key_exists('subjects', $cronRun)) {
        $this->mut->assertSubjects($cronRun['subjects']);
      }
      $this->mut->clearMessages();
    }
  }

}
