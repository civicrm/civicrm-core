<?php
namespace Civi\ActionSchedule;

/**
 * The AbstractMappingTest is a base class which can help define new
 * tests for scheduled-reminders.
 *
 * Generally, the problem of testing scheduled-reminders is one of permutations --
 * there are many different types of records, fields on the records, and scheduling options.
 * To test these, we setup a schedule of cron-runs (eg Jan 20 to Mar 1) and create some example
 * records.
 *
 * To setup the examples, we need to string together several helper functions, like:
 *
 *  - startOnTime(), startWeekBefore(), or startWeekAfter()
 *  - repeatTwoWeeksAfter()
 *  - limitToRecipientAlice(), limitToRecipientBob(), alsoRecipientBob()
 *  - addAliceDues(), addBobDonation()
 *  - addAliceMeeting(), addBobPhoneCall()
 *
 * (Some of these helpers are defined in AbstractMappingTest. Some are defined in subclasses.)
 *
 * Concrete subclasses should implement a few elements:
 *
 *   - Optionally, modify $cronSchedule to specify when the cron jobs run.
 *     (By default, it specifies daily from 20-Jan-15 to 1-Mar-15.)
 *   - Implement at least one setup-helper which creates example records.
 *     The example records should use the specified date (`$this->targetDate`)
 *     and should relate to `$this->contact['alice']` (or 'bob 'or 'carol').
 *   - Implement at least one schedule-helper which configures `$this->schedule`
 *     to use the preferred action mapping. It may define various
 *     filters, such as value-filters, status-filters, or recipient-filters.
 *   - Implement `createTestCases()` which defines various
 *     permutations of tests to run. Each test provides a list of emails
 *     which should be fired (datetime/recipient/subject).
 *
 * For examples:
 * @see CRM_Contribute_ActionMapping_ByTypeTest
 * @see CRM_Activity_ActionMappingTest
 */
abstract class AbstractMappingTest extends \CiviUnitTestCase {

  /**
   * @var \CRM_Core_DAO_ActionSchedule
   */
  public $schedule;

  /**
   * The date which should be stored on the matching record in the DB.
   *
   * @var string
   */
  public $targetDate;

  /**
   * Example contact records.
   *
   * @var array
   */
  public $contacts;

  /**
   * The schedule for invoking cron.
   *
   * @var array
   *  - start: string
   *  - end: string
   *  - interval: int, seconds
   */
  public $cronSchedule;

  /**
   * When comparing timestamps, treat them as the same if they
   * occur within a certain distance of each other.
   *
   * @var int seconds
   */
  public $dateTolerance = 120;

  /**
   * @var \CiviMailUtils
   */
  public $mut;

  /**
   * Generate a list of test cases, where each is a distinct combination of
   * data, schedule-rules, and schedule results.
   *
   * @return array
   *   - targetDate: string; eg "2015-02-01 00:00:01"
   *   - setupFuncs: string, space-separated list of setup functions
   *   - messages: array; each item is a message that's expected to be sent
   *     each message may include keys:
   *        - time: approximate time (give or take a few seconds)
   *        - subject: regex
   *        - message: regex
   */
  abstract public function createTestCases();

  // ---------------------------------------- Setup Helpers ----------------------------------------

  /**
   * Send first message on the designated date.
   */
  public function startOnTime() {
    $this->schedule->start_action_condition = 'before';
    $this->schedule->start_action_offset = '0';
    $this->schedule->start_action_unit = 'day';
  }

  /**
   * Send first message one week before designated date.
   */
  public function startWeekBefore() {
    $this->schedule->start_action_condition = 'before';
    $this->schedule->start_action_offset = '7';
    $this->schedule->start_action_unit = 'day';
  }

  /**
   * Send first message one week after designated date.
   */
  public function startWeekAfter() {
    $this->schedule->start_action_condition = 'after';
    $this->schedule->start_action_offset = '7';
    $this->schedule->start_action_unit = 'day';
  }

  /**
   * Send repeated messages until two weeks after designated date.
   */
  public function repeatTwoWeeksAfter() {
    $this->schedule->is_repeat = 1;
    $this->schedule->repetition_frequency_interval = '7';
    $this->schedule->repetition_frequency_unit = 'day';

    $this->schedule->end_action = 'after';
    $this->schedule->end_date = $this->schedule->start_action_date;
    $this->schedule->end_frequency_interval = '14';
    $this->schedule->end_frequency_unit = 'day';
  }

  /**
   * Compose a "Hello" email which includes the recipient's first name.
   */
  public function useHelloFirstName() {
    $this->schedule->subject = 'Hello, {contact.first_name}. (via subject)';
    $this->schedule->body_html = '<p>Hello, {contact.first_name}. (via body_html)</p>';
    $this->schedule->body_text = 'Hello, {contact.first_name}. (via body_text)';
  }

  /**
   * Limit possible recipients to Alice.
   */
  public function limitToRecipientAlice() {
    $this->schedule->limit_to = 1;
    $this->schedule->recipient = NULL;
    $this->schedule->recipient_listing = NULL;
    $this->schedule->recipient_manual = $this->contacts['alice']['id'];
  }

  /**
   * Limit possible recipients to Bob.
   */
  public function limitToRecipientBob() {
    $this->schedule->limit_to = 1;
    $this->schedule->recipient = NULL;
    $this->schedule->recipient_listing = NULL;
    $this->schedule->recipient_manual = $this->contacts['bob']['id'];
  }

  /**
   * Also include recipient Bob.
   */
  public function alsoRecipientBob() {
    $this->schedule->limit_to = 0;
    $this->schedule->recipient = NULL;
    $this->schedule->recipient_listing = NULL;
    $this->schedule->recipient_manual = $this->contacts['bob']['id'];
  }

  // ---------------------------------------- Core test definitions ----------------------------------------

  /**
   * Setup an empty schedule and some contacts.
   */
  protected function setUp() {
    parent::setUp();
    $this->useTransaction();

    $this->mut = new \CiviMailUtils($this, TRUE);

    $this->cronSchedule = array(
      'start' => '2015-01-20 00:00:00',
      'end' => '2015-03-01 00:00:00',
      // seconds
      'interval' => 24 * 60 * 60,
    );

    $this->schedule = new \CRM_Core_DAO_ActionSchedule();
    $this->schedule->title = $this->getName(TRUE);
    $this->schedule->name = \CRM_Utils_String::munge($this->schedule->title);
    $this->schedule->is_active = 1;
    $this->schedule->group_id = NULL;
    $this->schedule->recipient = NULL;
    $this->schedule->recipient_listing = NULL;
    $this->schedule->recipient_manual = NULL;
    $this->schedule->absolute_date = NULL;
    $this->schedule->msg_template_id = NULL;
    $this->schedule->record_activity = NULL;

    $this->contacts['alice'] = $this->callAPISuccess('Contact', 'create', array(
      'contact_type' => 'Individual',
      'first_name' => 'Alice',
      'last_name' => 'Exemplar',
      'email' => 'alice@example.org',
    ));
    $this->contacts['bob'] = $this->callAPISuccess('Contact', 'create', array(
      'contact_type' => 'Individual',
      'first_name' => 'Bob',
      'last_name' => 'Exemplar',
      'email' => 'bob@example.org',
    ));
    $this->contacts['carol'] = $this->callAPISuccess('Contact', 'create', array(
      'contact_type' => 'Individual',
      'first_name' => 'Carol',
      'last_name' => 'Exemplar',
      'email' => 'carol@example.org',
    ));
  }

  /**
   * Execute the default schedule, without any special recipient selections.
   *
   * @dataProvider createTestCases
   *
   * @param string $targetDate
   * @param string $setupFuncs
   * @param array $expectMessages
   *
   * @throws \Exception
   */
  public function testDefault($targetDate, $setupFuncs, $expectMessages) {
    $this->targetDate = $targetDate;

    foreach (explode(' ', $setupFuncs) as $setupFunc) {
      $this->{$setupFunc}();
    }
    $this->schedule->save();

    $actualMessages = array();
    foreach ($this->cronTimes() as $time) {
      \CRM_Utils_Time::setTime($time);
      $this->callAPISuccess('job', 'send_reminder', array());
      foreach ($this->mut->getAllMessages('ezc') as $message) {
        /** @var \ezcMail $message */
        $simpleMessage = array(
          'time' => $time,
          'to' => \CRM_Utils_Array::collect('email', $message->to),
          'subject' => $message->subject,
        );
        sort($simpleMessage['to']);
        $actualMessages[] = $simpleMessage;
        $this->mut->clearMessages();
      }
    }

    $errorText = "Incorrect messages: " . print_r(array(
      'actualMessages' => $actualMessages,
      'expectMessages' => $expectMessages,
    ), 1);
    $this->assertEquals(count($expectMessages), count($actualMessages), $errorText);
    usort($expectMessages, array(__CLASS__, 'compareSimpleMsgs'));
    usort($actualMessages, array(__CLASS__, 'compareSimpleMsgs'));
    foreach ($expectMessages as $offset => $expectMessage) {
      $actualMessage = $actualMessages[$offset];
      $this->assertApproxEquals(strtotime($expectMessage['time']), strtotime($actualMessage['time']), $this->dateTolerance, $errorText);
      if (isset($expectMessage['to'])) {
        sort($expectMessage['to']);
        $this->assertEquals($expectMessage['to'], $actualMessage['to'], $errorText);
      }
      if (isset($expectMessage['subject'])) {
        $this->assertRegExp($expectMessage['subject'], $actualMessage['subject'], $errorText);
      }
    }
  }

  protected function cronTimes() {
    $skew = 0;
    $times = array();
    $end = strtotime($this->cronSchedule['end']);
    for ($time = strtotime($this->cronSchedule['start']); $time < $end; $time += $this->cronSchedule['interval']) {
      $times[] = date('Y-m-d H:i:s', $time + $skew);
      //$skew++;
    }
    return $times;
  }

  protected function compareSimpleMsgs($a, $b) {
    if ($a['time'] != $b['time']) {
      return ($a['time'] < $b['time']) ? 1 : -1;
    }
    if ($a['to'] != $b['to']) {
      return ($a['to'] < $b['to']) ? 1 : -1;
    }
    if ($a['subject'] != $b['subject']) {
      return ($a['subject'] < $b['subject']) ? 1 : -1;
    }
  }

}
