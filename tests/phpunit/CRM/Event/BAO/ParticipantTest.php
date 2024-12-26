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

/**
 * Class CRM_Event_BAO_ParticipantTest
 *
 * @group headless
 */
class CRM_Event_BAO_ParticipantTest extends CiviUnitTestCase {

  /**
   * API version in use.
   *
   * @var int
   */
  protected $_apiversion = 4;

  public function setUp(): void {
    parent::setUp();
    $this->individualCreate();
    $this->eventCreateUnpaid();
  }

  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    parent::tearDown();
  }

  /**
   * Add() method (add and edit modes of participant)
   */
  public function testAdd(): void {
    $params = [
      'send_receipt' => 1,
      'is_test' => 0,
      'is_pay_later' => 0,
      'event_id' => $this->getEventID(),
      'register_date' => date('Y-m-d') . ' 00:00:00',
      'role_id' => 1,
      'status_id' => 1,
      'source' => 'Event_' . $this->getEventID(),
      'contact_id' => $this->ids['Contact']['individual_0'],
    ];

    // New Participant Created
    $participant = CRM_Event_BAO_Participant::add($params);

    $this->assertDBNotNull('CRM_Event_BAO_Participant', $this->ids['Contact']['individual_0'], 'id',
      'contact_id', 'Check DB for Participant of the contact'
    );

    $this->assertDBCompareValue('CRM_Event_BAO_Participant', $participant->id, 'contact_id',
      'id', $this->ids['Contact']['individual_0'], 'Check DB for contact of the participant'
    );

    $params = array_merge($params, [
      'id' => $participant->id,
      'role_id' => 2,
      'status_id' => 3,
    ]);

    // Participant Edited
    $updatedParticipant = CRM_Event_BAO_Participant::add($params);
    $this->assertDBCompareValue('CRM_Event_BAO_Participant', $updatedParticipant->id, 'role_id',
      'id', 2, 'Check DB for updated role id of the participant'
    );

    $this->assertDBCompareValue('CRM_Event_BAO_Participant', $updatedParticipant->id, 'status_id',
      'id', 3, 'Check DB for updated status id  of the participant'
    );
  }

  /**
   * GetValues() method (fetch value of participant)
   */
  public function testGetValuesWithValidParams(): void {
    $participantID = $this->participantCreate(['contact_id' => $this->ids['Contact']['individual_0'], 'event_id' => $this->getEventID()]);
    $params = ['id' => $participantID];
    $values = [];

    $fetchParticipant = CRM_Event_BAO_Participant::getValues($params, $values);
    $compareValues = $fetchParticipant[$participantID];

    $params = [
      'send_receipt' => 1,
      'is_test' => 0,
      'is_pay_later' => 0,
      'event_id' => $this->getEventID(),
      'register_date' => '2007-02-19 00:00:00',
      'role_id' => 1,
      'status_id' => 2,
      'source' => 'Wimbledon',
      'contact_id' => $this->ids['Contact']['individual_0'],
      'id' => $participantID,
      'campaign_id' => NULL,
      'fee_level' => NULL,
      'fee_amount' => NULL,
      'registered_by_id' => NULL,
      'discount_id' => NULL,
      'fee_currency' => NULL,
      'discount_amount' => NULL,
      'cart_id' => NULL,
      'must_wait' => NULL,
      'transferred_to_contact_id' => NULL,
      'created_id' => $this->ids['Contact']['individual_0'],
    ];

    foreach ($compareValues as $key => $value) {
      if ($key[0] !== '_' && $key !== 'N') {
        $this->assertEquals($compareValues->$key, $params[$key], 'Check for ' . $key . ' for given participant');
      }
    }
  }

  /**
   * GetValues() method (checking for behavior when params are empty )
   */
  public function testGetValuesWithoutValidParams(): void {
    $params = $values = $ids = [];
    $this->participantCreate(['contact_id' => $this->ids['Contact']['individual_0'], 'event_id' => $this->getEventID()]);
    $fetchParticipant = CRM_Event_BAO_Participant::getValues($params, $values, $ids);
    $this->assertNull($fetchParticipant);
  }

  /**
   * EventFull() method (checking the event for full).
   */
  public function testEventFull(): void {
    $this->callAPISuccess('Event', 'update', [
      'max_participants' => 1,
      'id' => $this->getEventID(),
    ]);

    $this->participantCreate(['contact_id' => $this->ids['Contact']['individual_0'], 'event_id' => $this->getEventID()]);
    $eventFull = CRM_Event_BAO_Participant::eventFull($this->getEventID());

    $this->assertEquals('Sorry! We are already full', $eventFull, 'Checking if Event is full.');
  }

  /**
   * ParticipantDetails() method ( Checking the Participant Details )
   */
  public function testParticipantDetails(): void {
    $participant = $this->callAPISuccess('Participant', 'create', ['contact_id' => $this->ids['Contact']['individual_0'], 'event_id' => $this->getEventID()]);
    $params = ['name' => 'Anderson, Anthony II', 'title' => 'Annual CiviCRM meet'];

    $participantDetails = CRM_Event_BAO_Participant::participantDetails($participant['id']);

    $this->assertCount(3, $participantDetails, 'Equating the array contains.');
    $this->assertEquals($participantDetails['name'], $params['name'], 'Checking Name of Participant.');
    $this->assertEquals($participantDetails['title'], $params['title'], 'Checking Event Title in which participant is enrolled.');
  }

  /**
   * DeleteParticipant() method.
   */
  public function testDeleteParticipant(): void {
    $this->createTestEntity('Participant', [
      'send_receipt' => 1,
      'is_test' => 0,
      'is_pay_later' => 0,
      'event_id' => $this->getEventID(),
      'register_date' => date('Y-m-d') . ' 00:00:00',
      'role_id' => 1,
      'status_id' => 1,
      'source' => 'Event_' . $this->getEventID(),
      'contact_id' => $this->ids['Contact']['individual_0'],
    ]);
    CRM_Event_BAO_Participant::deleteParticipant($this->ids['Participant']['default']);
    $this->assertDBNull('CRM_Event_BAO_Participant', $this->ids['Participant']['default'], 'contact_id', 'id', 'Check DB for deleted Participant.');
  }

  /**
   * CheckDuplicate() method ( Checking for Duplicate Participant returns array of participant id)
   */
  public function testCheckDuplicate(): void {
    $duplicate = [];

    //Creating 3 new participants
    for ($i = 0; $i < 3; $i++) {
      $partiId[] = $this->participantCreate(['contact_id' => $this->ids['Contact']['individual_0'], 'event_id' => $this->getEventID()]);
    }

    $params = ['event_id' => $this->getEventID(), 'contact_id' => $this->ids['Contact']['individual_0']];
    CRM_Event_BAO_Participant::checkDuplicate($params, $duplicate);

    $this->assertCount(3, $duplicate, 'Equating the array contains with duplicate array.');

    //Checking for the duplicate participant
    foreach ($duplicate as $key => $value) {
      $this->assertEquals($partiId[$key], $value, 'Equating the contact ID which is in the database.');
    }
  }

  /**
   * Create() method (create and updating of participant)
   */
  public function testCreate(): void {
    $params = [
      'send_receipt' => 1,
      'is_test' => 0,
      'is_pay_later' => 0,
      'event_id' => $this->getEventID(),
      'register_date' => date('Y-m-d') . ' 00:00:00',
      'role_id' => 1,
      'status_id' => 1,
      'source' => 'Event_' . $this->getEventID(),
      'contact_id' => $this->ids['Contact']['individual_0'],
      'note' => 'Note added for Event_' . $this->getEventID(),
    ];

    $participant = CRM_Event_BAO_Participant::create($params);
    //Checking for Contact id in the participant table.
    $this->assertDBNotNull('CRM_Event_DAO_Participant', $this->ids['Contact']['individual_0'], 'id',
      'contact_id', 'Check DB for Participant of the contact'
    );

    //Checking for Activity added in the table for relative participant.
    $this->assertDBCompareValue('CRM_Activity_DAO_Activity', $this->ids['Contact']['individual_0'], 'source_record_id',
      'source_contact_id', $participant->id, 'Check DB for activity added for the participant'
    );
    //Checking for participant contact_id added to activity target.
    $params_activity = ['contact_id' => $this->ids['Contact']['individual_0'], 'record_type_id' => 3];
    $this->assertDBCompareValues('CRM_Activity_DAO_ActivityContact', $params_activity, $params_activity);

    $params = array_merge($params, [
      'id' => $participant->id,
      'role_id' => 2,
      'status_id' => 3,
      'note' => 'Test Event in edit mode is running successfully ....',
    ]);

    $participant = CRM_Event_BAO_Participant::create($params);

    //Checking Edited Value of role_id in the database.
    $this->assertDBCompareValue('CRM_Event_DAO_Participant', $participant->id, 'role_id',
      'id', 2, 'Check DB for updated role id of the participant'
    );

    //Checking Edited Value of status_id in the database.
    $this->assertDBCompareValue('CRM_Event_DAO_Participant', $participant->id, 'status_id',
      'id', 3, 'Check DB for updated status id  of the participant'
    );

    //Checking for Activity added in the table for relative participant.
    $this->assertDBCompareValue('CRM_Activity_DAO_Activity', $this->ids['Contact']['individual_0'], 'source_record_id',
      'source_contact_id', $participant->id, 'Check DB for activity added for the participant'
    );
    //Checking for participant contact_id added to activity target.
    $params_activity = ['contact_id' => $this->ids['Contact']['individual_0'], 'record_type_id' => 3];
    $this->assertDBCompareValues('CRM_Activity_DAO_ActivityContact', $params_activity, $params_activity);
  }

  /**
   * ExportableFields() method ( Exportable Fields for Participant)
   */
  public function testExportableFields(): void {
    $exportableFields = CRM_Event_BAO_Participant::exportableFields();
    $this->assertNotCount(0, $exportableFields, 'Checking array not to be empty.');
  }

  /**
   * FixEventLevel().
   */
  public function testFixEventLevel(): void {
    $fee = CRM_Core_DAO::VALUE_SEPARATOR . 'Blah' . CRM_Core_DAO::VALUE_SEPARATOR . 'blah' . CRM_Core_DAO::VALUE_SEPARATOR;
    CRM_Event_BAO_Participant::fixEventLevel($fee);
    $this->assertEquals('Blah, blah', $fee);
  }

  /**
   * Test various self-service eligibility scenarios.
   *
   * @dataProvider selfServiceScenarios
   *
   * @param int $selfSvcEnabled
   * @param int $selfSvcHours
   * @param int $hoursToEvent
   * @param int $participantStatusID
   * @param bool $isBackOffice
   * @param bool $successExpected  A boolean that indicates whether this test should pass or fail.
   */
  public function testGetSelfServiceEligibility(int $selfSvcEnabled, int $selfSvcHours, int $hoursToEvent, int $participantStatusID, bool $isBackOffice, bool $successExpected): void {
    $participantId = $this->participantCreate(['contact_id' => $this->ids['Contact']['individual_0'], 'event_id' => $this->getEventID(), 'status_id' => $participantStatusID]);
    $now = new Datetime();
    if ($hoursToEvent >= 0) {
      $startDate = $now->add(new DateInterval("PT{$hoursToEvent}H"))->format('Y-m-d H:i:s');
    }
    else {
      $hoursAfterEvent = abs($hoursToEvent);
      $startDate = $now->sub(new DateInterval("PT{$hoursAfterEvent}H"))->format('Y-m-d H:i:s');
    }
    $this->callAPISuccess('Event', 'create', [
      'id' => $this->getEventID(),
      'allow_selfcancelxfer' => $selfSvcEnabled,
      'selfcancelxfer_time' => $selfSvcHours,
      'start_date' => $startDate,
    ]);
    $url = CRM_Utils_System::url('civicrm/event/info', "reset=1&id={$this->getEventID()}");
    $details = CRM_Event_BAO_Participant::getSelfServiceEligibility($participantId, $url, $isBackOffice);
    $this->assertEquals($details['eligible'], $successExpected);
  }

  public function selfServiceScenarios(): array {
    // Standard pass scenario
    $scenarios[] = [
      'selfSvcEnabled' => 1,
      'selfSvcHours' => 12,
      'hoursToEvent' => 16,
      'participantStatusId' => 1,
      'isBackOffice' => FALSE,
      'successExpected' => TRUE,
    ];
    // Allow to cancel if on waitlist
    $scenarios[] = [
      'selfSvcEnabled' => 1,
      'selfSvcHours' => 12,
      'hoursToEvent' => 16,
      'participantStatusId' => 7,
      'isBackOffice' => FALSE,
      'successExpected' => TRUE,
    ];
    // Too late to self-service
    $scenarios[] = [
      'selfSvcEnabled' => 1,
      'selfSvcHours' => 12,
      'hoursToEvent' => 8,
      'participantStatusId' => 1,
      'isBackOffice' => FALSE,
      'successExpected' => FALSE,
    ];
    // Participant status cannot cancel (ex: Attended)
    $scenarios[] = [
      'selfSvcEnabled' => 1,
      'selfSvcHours' => 12,
      'hoursToEvent' => 16,
      'participantStatusId' => 2,
      'isBackOffice' => FALSE,
      'successExpected' => FALSE,
    ];
    // Event doesn't allow self-service
    $scenarios[] = [
      'selfSvcEnabled' => 0,
      'selfSvcHours' => 12,
      'hoursToEvent' => 16,
      'participantStatusId' => 1,
      'isBackOffice' => FALSE,
      'successExpected' => FALSE,
    ];
    // Cancellation deadline is > 24 hours, still ok to cancel
    $scenarios[] = [
      'selfSvcEnabled' => 1,
      'selfSvcHours' => 36,
      'hoursToEvent' => 46,
      'participantStatusId' => 1,
      'isBackOffice' => FALSE,
      'successExpected' => TRUE,
    ];
    // Cancellation deadline is > 24 hours, too late to cancel
    $scenarios[] = [
      'selfSvcEnabled' => 1,
      'selfSvcHours' => 36,
      'hoursToEvent' => 25,
      'participantStatusId' => 1,
      'isBackOffice' => FALSE,
      'successExpected' => FALSE,
    ];
    // Cancellation deadline is < 0 hours
    $scenarios[] = [
      'selfSvcEnabled' => 1,
      'selfSvcHours' => -12,
      'hoursToEvent' => 4,
      'participantStatusId' => 1,
      'isBackOffice' => FALSE,
      'successExpected' => TRUE,
    ];
    $scenarios[] = [
      'selfSvcEnabled' => 1,
      'selfSvcHours' => 0,
      'hoursToEvent' => -6,
      'participantStatusId' => 1,
      'isBackOffice' => FALSE,
      'successExpected' => FALSE,
    ];
    // Update from back office even when self-service is disabled
    $scenarios[] = [
      'selfSvcEnabled' => 0,
      'selfSvcHours' => 12,
      'hoursToEvent' => 16,
      'participantStatusId' => 1,
      'isBackOffice' => TRUE,
      'successExpected' => TRUE,
    ];
    // Update from back office when participant status is Attended
    $scenarios[] = [
      'selfSvcEnabled' => 0,
      'selfSvcHours' => 12,
      'hoursToEvent' => 16,
      'participantStatusId' => 2,
      'isBackOffice' => TRUE,
      'successExpected' => TRUE,
    ];
    return $scenarios;
  }

}
