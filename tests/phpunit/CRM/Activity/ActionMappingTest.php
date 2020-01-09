<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

require_once 'CiviTest/CiviUnitTestCase.php';

/**
 * Class CRM_Activity_ActionMappingTest
 * @group ActionSchedule
 *
 * This class tests various configurations of scheduled-reminders, with a focus on
 * reminders for *activity types*. It follows a design/pattern described in
 * AbstractMappingTest.
 *
 * NOTE: There are also pretty deep tests of activity-based reminders in
 * CRM_Core_BAO_ActionScheduleTest.
 *
 * @see \Civi\ActionSchedule\AbstractMappingTest
 * @see CRM_Core_BAO_ActionScheduleTest
 */
class CRM_Activity_ActionMappingTest extends \Civi\ActionSchedule\AbstractMappingTest {

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
   *        - recipients: array of emails
   *        - subject: regex
   */
  public function createTestCases() {
    $cs = [];

    $cs[] = [
      '2015-02-01 00:00:00',
      'addAliceMeeting scheduleForAny startOnTime useHelloFirstName recipientIsActivitySource',
      [
        [
          'time' => '2015-02-01 00:00:00',
          'to' => ['alice@example.org'],
          'subject' => '/Hello, Alice.*via subject/',
        ],
      ],
    ];

    // FIXME: CRM-19415: This test should pass...
    //    $cs[] = array(
    //      '2015-02-01 00:00:00',
    //      'addAliceMeeting scheduleForAny startOnTime useHelloFirstName recipientIsBob',
    //      array(
    //        array(
    //          'time' => '2015-02-01 00:00:00',
    //          'to' => array('bob@example.org'),
    //          'subject' => '/Hello, Bob.*via subject/',
    //          // It might make more sense to get Alice's details... but path of least resistance...
    //        ),
    //      ),
    //    );

    $cs[] = [
      '2015-02-01 00:00:00',
      'addAliceMeeting addBobPhoneCall scheduleForMeeting startOnTime useHelloFirstName recipientIsActivitySource',
      [
        [
          'time' => '2015-02-01 00:00:00',
          'to' => ['alice@example.org'],
          'subject' => '/Hello, Alice.*via subject/',
        ],
      ],
    ];

    $cs[] = [
      '2015-02-01 00:00:00',
      'addAliceMeeting addBobPhoneCall scheduleForAny startOnTime useHelloFirstName recipientIsActivitySource',
      [
        [
          'time' => '2015-02-01 00:00:00',
          'to' => ['alice@example.org'],
          'subject' => '/Hello, Alice.*via subject/',
        ],
        [
          'time' => '2015-02-01 00:00:00',
          'to' => ['bob@example.org'],
          'subject' => '/Hello, Bob.*via subject/',
        ],
      ],
    ];

    $cs[] = [
      '2015-02-02 00:00:00',
      'addAliceMeeting addBobPhoneCall scheduleForPhoneCall startWeekBefore repeatTwoWeeksAfter useHelloFirstName recipientIsActivitySource',
      [
        [
          'time' => '2015-01-26 00:00:00',
          'to' => ['bob@example.org'],
          'subject' => '/Hello, Bob.*via subject/',
        ],
        [
          'time' => '2015-02-02 00:00:00',
          'to' => ['bob@example.org'],
          'subject' => '/Hello, Bob.*via subject/',
        ],
        [
          'time' => '2015-02-09 00:00:00',
          'to' => ['bob@example.org'],
          'subject' => '/Hello, Bob.*via subject/',
        ],
        [
          'time' => '2015-02-16 00:00:00',
          'to' => ['bob@example.org'],
          'subject' => '/Hello, Bob.*via subject/',
        ],
      ],
    ];

    // No recipients: Dave has `do_not_email`, Edith is dead, and Francis' email
    // is on hold.
    $cs[] = [
      '2015-02-01 00:00:00',
      'addDaveMeeting addEdithMeeting addFrancisMeeting scheduleForMeeting startOnTime useHelloFirstName recipientIsActivitySource',
      [],
    ];

    // Gretchen has one email on hold, but her primary email is not on hold.
    $cs[] = [
      '2015-02-01 00:00:00',
      'addGretchenMeeting scheduleForMeeting startOnTime useHelloFirstName recipientIsActivitySource',
      [
        [
          'time' => '2015-02-01 00:00:00',
          'to' => ['gretchen@example.org'],
          'subject' => '/Hello, Gretchen.*via subject/',
        ],
      ],
    ];

    return $cs;
  }

  /**
   * Create an activity record for Alice with type "Meeting".
   */
  public function addAliceMeeting() {
    $this->callAPISuccess('Activity', 'create', [
      'source_contact_id' => $this->contacts['alice']['id'],
      'activity_type_id' => 'Meeting',
      'subject' => 'Subject for Alice',
      'activity_date_time' => date('Y-m-d H:i:s', strtotime($this->targetDate)),
      'status_id' => 2,
      'assignee_contact_id' => [$this->contacts['carol']['id']],
    ]);
  }

  /**
   * Create a contribution record for Bob with type "Donation".
   */
  public function addBobPhoneCall() {
    $this->callAPISuccess('Activity', 'create', [
      'source_contact_id' => $this->contacts['bob']['id'],
      'activity_type_id' => 'Phone Call',
      'subject' => 'Subject for Bob',
      'activity_date_time' => date('Y-m-d H:i:s', strtotime($this->targetDate)),
      'status_id' => 2,
      'assignee_contact_id' => [$this->contacts['carol']['id']],
    ]);
  }

  /**
   * Create an activity record for Dave with type "Meeting".  Dave has
   * "do_not_email" set, so he should never receive an email reminder.
   */
  public function addDaveMeeting() {
    $this->callAPISuccess('Activity', 'create', [
      'source_contact_id' => $this->contacts['dave']['id'],
      'activity_type_id' => 'Meeting',
      'subject' => 'Subject for Dave',
      'activity_date_time' => date('Y-m-d H:i:s', strtotime($this->targetDate)),
      'status_id' => 2,
      'assignee_contact_id' => [$this->contacts['carol']['id']],
    ]);
  }

  /**
   * Create an activity record for Edith with type "Meeting". Edith is dead, so
   * she should never receive an email reminder.
   */
  public function addEdithMeeting() {
    $this->callAPISuccess('Activity', 'create', [
      'source_contact_id' => $this->contacts['edith']['id'],
      'activity_type_id' => 'Meeting',
      'subject' => 'Subject for Edith',
      'activity_date_time' => date('Y-m-d H:i:s', strtotime($this->targetDate)),
      'status_id' => 2,
      'assignee_contact_id' => [$this->contacts['carol']['id']],
    ]);
  }

  /**
   * Create an activity record for Francis with type "Meeting". Francis' email
   * is misspelled and has bounced, so he should never receive an email reminder.
   */
  public function addFrancisMeeting() {
    $this->callAPISuccess('Activity', 'create', [
      'source_contact_id' => $this->contacts['francis']['id'],
      'activity_type_id' => 'Meeting',
      'subject' => 'Subject for Francis',
      'activity_date_time' => date('Y-m-d H:i:s', strtotime($this->targetDate)),
      'status_id' => 2,
      'assignee_contact_id' => [$this->contacts['carol']['id']],
    ]);
  }

  /**
   * Create an activity record for Gretchen with type "Meeting".
   */
  public function addGretchenMeeting() {
    $this->callAPISuccess('Activity', 'create', [
      'source_contact_id' => $this->contacts['gretchen']['id'],
      'activity_type_id' => 'Meeting',
      'subject' => 'Subject for Gretchen',
      'activity_date_time' => date('Y-m-d H:i:s', strtotime($this->targetDate)),
      'status_id' => 2,
      'assignee_contact_id' => [$this->contacts['carol']['id']],
    ]);
  }

  /**
   * Schedule message delivery for activities of type "Meeting".
   */
  public function scheduleForMeeting() {
    $actTypes = CRM_Activity_BAO_Activity::buildOptions('activity_type_id');
    $this->schedule->mapping_id = CRM_Activity_ActionMapping::ACTIVITY_MAPPING_ID;
    $this->schedule->start_action_date = 'receive_date';
    $this->schedule->entity_value = CRM_Utils_Array::implodePadded([array_search('Meeting', $actTypes)]);
    $this->schedule->entity_status = CRM_Utils_Array::implodePadded([2]);
  }

  /**
   * Schedule message delivery for activities of type "Phone Call".
   */
  public function scheduleForPhoneCall() {
    $actTypes = CRM_Activity_BAO_Activity::buildOptions('activity_type_id');
    $this->schedule->mapping_id = CRM_Activity_ActionMapping::ACTIVITY_MAPPING_ID;
    $this->schedule->start_action_date = 'receive_date';
    $this->schedule->entity_value = CRM_Utils_Array::implodePadded([array_search('Phone Call', $actTypes)]);
    $this->schedule->entity_status = CRM_Utils_Array::implodePadded(NULL);
  }

  /**
   * Schedule message delivery for any contribution, regardless of type.
   */
  public function scheduleForAny() {
    $actTypes = CRM_Activity_BAO_Activity::buildOptions('activity_type_id');
    $this->schedule->mapping_id = CRM_Activity_ActionMapping::ACTIVITY_MAPPING_ID;
    $this->schedule->start_action_date = 'receive_date';
    $this->schedule->entity_value = CRM_Utils_Array::implodePadded(array_keys($actTypes));
    $this->schedule->entity_status = CRM_Utils_Array::implodePadded(NULL);
  }

  /**
   * Set the recipient to "Choose Recipient(s): Bob".
   */
  public function recipientIsBob() {
    $this->schedule->limit_to = 1;
    $this->schedule->recipient = NULL;
    $this->schedule->recipient_listing = NULL;
    $this->schedule->recipient_manual = $this->contacts['bob']['id'];
  }

  /**
   * Set the recipient to "Activity Assignee".
   */
  public function recipientIsActivityAssignee() {
    $this->schedule->limit_to = 1;
    $this->schedule->recipient = 1;
    $this->schedule->recipient_listing = NULL;
    $this->schedule->recipient_manual = NULL;
  }

  /**
   * Set the recipient to "Activity Source".
   */
  public function recipientIsActivitySource() {
    $this->schedule->limit_to = 1;
    $this->schedule->recipient = 2;
    $this->schedule->recipient_listing = NULL;
    $this->schedule->recipient_manual = NULL;
  }

}
