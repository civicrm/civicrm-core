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

  public function setUp() {
    parent::setUp();
    $this->_contactId = $this->individualCreate();
    $event = $this->eventCreate();
    $this->_eventId = $event['id'];
  }

  /**
   * Add() method (add and edit modes of participant)
   */
  public function testAdd() {
    $params = [
      'send_receipt' => 1,
      'is_test' => 0,
      'is_pay_later' => 0,
      'event_id' => $this->_eventId,
      'register_date' => date('Y-m-d') . " 00:00:00",
      'role_id' => 1,
      'status_id' => 1,
      'source' => 'Event_' . $this->_eventId,
      'contact_id' => $this->_contactId,
    ];

    // New Participant Created
    $participant = CRM_Event_BAO_Participant::add($params);

    $this->assertDBNotNull('CRM_Event_BAO_Participant', $this->_contactId, 'id',
      'contact_id', 'Check DB for Participant of the contact'
    );

    $this->assertDBCompareValue('CRM_Event_BAO_Participant', $participant->id, 'contact_id',
      'id', $this->_contactId, 'Check DB for contact of the participant'
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

    $this->contactDelete($this->_contactId);
    $this->eventDelete($this->_eventId);
  }

  /**
   * GetValues() method (fetch value of participant)
   */
  public function testgetValuesWithValidParams() {
    $participantId = $this->participantCreate(['contact_id' => $this->_contactId, 'event_id' => $this->_eventId]);
    $params = ['id' => $participantId];
    $values = $ids = [];

    $fetchParticipant = CRM_Event_BAO_Participant::getValues($params, $values, $ids);
    $compareValues = $fetchParticipant[$participantId];

    $params = [
      'send_receipt' => 1,
      'is_test' => 0,
      'is_pay_later' => 0,
      'event_id' => $this->_eventId,
      'register_date' => '2007-02-19 00:00:00',
      'role_id' => 1,
      'status_id' => 2,
      'source' => 'Wimbeldon',
      'contact_id' => $this->_contactId,
      'id' => $participantId,
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
    ];

    foreach ($compareValues as $key => $value) {
      if (substr($key, 0, 1) != '_' && $key != 'N') {
        $this->assertEquals($compareValues->$key, $params[$key], 'Check for ' . $key . ' for given participant');
      }
    }

    $this->participantDelete($participantId);
    $this->contactDelete($this->_contactId);
    $this->eventDelete($this->_eventId);
  }

  /**
   * GetValues() method (checking for behavior when params are empty )
   */
  public function testgetValuesWithoutValidParams() {
    $params = $values = $ids = [];
    $this->participantCreate(['contact_id' => $this->_contactId, 'event_id' => $this->_eventId]);
    $fetchParticipant = CRM_Event_BAO_Participant::getValues($params, $values, $ids);
    $this->assertNull($fetchParticipant);

    $this->contactDelete($this->_contactId);
    $this->eventDelete($this->_eventId);
  }

  /**
   * EventFull() method (checking the event for full )
   */
  public function testEventFull() {
    $eventParams = [
      'max_participants' => 1,
      'id' => $this->_eventId,
    ];
    CRM_Event_BAO_Event::add($eventParams);

    $participantId = $this->participantCreate(['contact_id' => $this->_contactId, 'event_id' => $this->_eventId]);
    $eventFull = CRM_Event_BAO_Participant::eventFull($this->_eventId);

    $this->assertEquals($eventFull, 'Sorry! We are already full', 'Checking if Event is full.');

    $this->participantDelete($participantId);
    $this->contactDelete($this->_contactId);
    $this->eventDelete($this->_eventId);
  }

  /**
   * ImportableFields() method ( Checking the Event's Importable Fields )
   */
  public function testimportableFields() {
    $importableFields = CRM_Event_BAO_Participant::importableFields();
    $this->assertNotEquals(count($importableFields), 0, 'Checking array not to be empty.');

    $this->contactDelete($this->_contactId);
    $this->eventDelete($this->_eventId);
  }

  /**
   * ParticipantDetails() method ( Checking the Participant Details )
   */
  public function testparticipantDetails() {
    $participant = $this->callAPISuccess('Participant', 'create', ['contact_id' => $this->_contactId, 'event_id' => $this->_eventId]);
    $params = ['name' => 'Anderson, Anthony', 'title' => 'Annual CiviCRM meet'];

    $participantDetails = CRM_Event_BAO_Participant::participantDetails($participant['id']);

    $this->assertEquals(count($participantDetails), 3, 'Equating the array contains.');
    $this->assertEquals($participantDetails['name'], $params['name'], 'Checking Name of Participant.');
    $this->assertEquals($participantDetails['title'], $params['title'], 'Checking Event Title in which participant is enroled.');

    $this->participantDelete($participant['id']);
    $this->contactDelete($this->_contactId);
    $this->eventDelete($this->_eventId);
  }

  /**
   * DeleteParticipant() method ( Delete a Participant )
   */
  public function testdeleteParticipant() {
    $params = [
      'send_receipt' => 1,
      'is_test' => 0,
      'is_pay_later' => 0,
      'event_id' => $this->_eventId,
      'register_date' => date('Y-m-d') . " 00:00:00",
      'role_id' => 1,
      'status_id' => 1,
      'source' => 'Event_' . $this->_eventId,
      'contact_id' => $this->_contactId,
    ];

    // New Participant Created
    $participant = CRM_Event_BAO_Participant::add($params);

    $this->assertDBNotNull('CRM_Event_BAO_Participant', $this->_contactId, 'id',
      'contact_id', 'Check DB for Participant of the contact'
    );

    $this->assertDBCompareValue('CRM_Event_BAO_Participant', $participant->id, 'contact_id',
      'id', $this->_contactId, 'Check DB for contact of the participant'
    );

    CRM_Event_BAO_Participant::deleteParticipant($participant->id);
    $this->assertDBNull('CRM_Event_BAO_Participant', $participant->id, 'contact_id', 'id', 'Check DB for deleted Participant.');

    $this->contactDelete($this->_contactId);
    $this->eventDelete($this->_eventId);
  }

  /**
   * CheckDuplicate() method ( Checking for Duplicate Participant returns array of participant id)
   */
  public function testcheckDuplicate() {
    $duplicate = [];

    //Creating 3 new participants
    for ($i = 0; $i < 3; $i++) {
      $partiId[] = $this->participantCreate(['contact_id' => $this->_contactId, 'event_id' => $this->_eventId]);
    }

    $params = ['event_id' => $this->_eventId, 'contact_id' => $this->_contactId];
    CRM_Event_BAO_Participant::checkDuplicate($params, $duplicate);

    $this->assertEquals(count($duplicate), 3, 'Equating the array contains with duplicate array.');

    //Checking for the duplicate participant
    foreach ($duplicate as $key => $value) {
      $this->assertEquals($partiId[$key], $duplicate[$key], 'Equating the contactid which is in the database.');
    }

    //Deleting all participant
    for ($i = 0; $i < 3; $i++) {
      $partidel[] = $this->participantDelete($partiId[$i]);
    }

    $this->contactDelete($this->_contactId);
    $this->eventDelete($this->_eventId);
  }

  /**
   * Create() method (create and updation of participant)
   */
  public function testCreate() {
    $params = [
      'send_receipt' => 1,
      'is_test' => 0,
      'is_pay_later' => 0,
      'event_id' => $this->_eventId,
      'register_date' => date('Y-m-d') . " 00:00:00",
      'role_id' => 1,
      'status_id' => 1,
      'source' => 'Event_' . $this->_eventId,
      'contact_id' => $this->_contactId,
      'note' => 'Note added for Event_' . $this->_eventId,
    ];

    $participant = CRM_Event_BAO_Participant::create($params);
    //Checking for Contact id in the participant table.
    $pid = $this->assertDBNotNull('CRM_Event_DAO_Participant', $this->_contactId, 'id',
      'contact_id', 'Check DB for Participant of the contact'
    );

    //Checking for Activity added in the table for relative participant.
    $this->assertDBCompareValue('CRM_Activity_DAO_Activity', $this->_contactId, 'source_record_id',
      'source_contact_id', $participant->id, 'Check DB for activity added for the participant'
    );

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
    $this->assertDBCompareValue('CRM_Activity_DAO_Activity', $this->_contactId, 'source_record_id',
      'source_contact_id', $participant->id, 'Check DB for activity added for the participant'
    );

    //Checking for Note added in the table for relative participant.
    $session = CRM_Core_Session::singleton();
    $id = $session->get('userID');
    if (!$id) {
      $id = $this->_contactId;
    }

    //Deleting the Participant created by create function in this function
    CRM_Event_BAO_Participant::deleteParticipant($participant->id);
    $this->assertDBNull('CRM_Event_DAO_Participant', $this->_contactId, 'id',
      'contact_id', 'Check DB for deleted participant. Should be NULL.'
    );

    $this->contactDelete($this->_contactId);
    $this->eventDelete($this->_eventId);
  }

  /**
   * ExportableFields() method ( Exportable Fields for Participant)
   */
  public function testexportableFields() {
    $exportableFields = CRM_Event_BAO_Participant::exportableFields();
    $this->assertNotEquals(count($exportableFields), 0, 'Checking array not to be empty.');

    $this->contactDelete($this->_contactId);
    $this->eventDelete($this->_eventId);
  }

  /**
   * FixEventLevel() method (Setting ',' values), resolveDefaults(assinging value to array) method
   */
  public function testfixEventLevel() {

    $paramsSet['title'] = 'Price Set';
    $paramsSet['name'] = CRM_Utils_String::titleToVar('Price Set');
    $paramsSet['is_active'] = FALSE;
    $paramsSet['extends'] = 1;

    $priceset = CRM_Price_BAO_PriceSet::create($paramsSet);

    //Checking for priceset added in the table.
    $this->assertDBCompareValue('CRM_Price_BAO_PriceSet', $priceset->id, 'title',
      'id', $paramsSet['title'], 'Check DB for created priceset'
    );
    $paramsField = [
      'label' => 'Price Field',
      'name' => CRM_Utils_String::titleToVar('Price Field'),
      'html_type' => 'Text',
      'price' => 10,
      'option_label' => ['1' => 'Price Field'],
      'option_value' => ['1' => 10],
      'option_name' => ['1' => 10],
      'option_weight' => ['1' => 1],
      'is_display_amounts' => 1,
      'weight' => 1,
      'options_per_line' => 1,
      'is_active' => ['1' => 1],
      'price_set_id' => $priceset->id,
      'is_enter_qty' => 1,
    ];

    $ids = [];
    $pricefield = CRM_Price_BAO_PriceField::create($paramsField, $ids);

    //Checking for priceset added in the table.
    $this->assertDBCompareValue('CRM_Price_BAO_PriceField', $pricefield->id, 'label',
      'id', $paramsField['label'], 'Check DB for created pricefield'
    );

    $eventId = $this->_eventId;
    $participantParams = [
      'send_receipt' => 1,
      'is_test' => 0,
      'is_pay_later' => 0,
      'event_id' => $eventId,
      'register_date' => date('Y-m-d') . " 00:00:00",
      'role_id' => 1,
      'status_id' => 1,
      'source' => 'Event_' . $eventId,
      'contact_id' => $this->_contactId,
      'note' => 'Note added for Event_' . $eventId,
      'fee_level' => 'Price_Field - 55',
    ];

    $participant = CRM_Event_BAO_Participant::add($participantParams);

    //Checking for participant added in the table.
    $this->assertDBCompareValue('CRM_Event_BAO_Participant', $this->_contactId, 'id',
      'contact_id', $participant->id, 'Check DB for created participant'
    );

    $values = [];
    $ids = [];
    $params = ['id' => $participant->id];

    CRM_Event_BAO_Participant::getValues($params, $values, $ids);
    $this->assertNotEquals(count($values), 0, 'Checking for empty array.');

    CRM_Event_BAO_Participant::resolveDefaults($values[$participant->id]);

    if ($values[$participant->id]['fee_level']) {
      CRM_Event_BAO_Participant::fixEventLevel($values[$participant->id]['fee_level']);
    }

    CRM_Price_BAO_PriceField::deleteField($pricefield->id);
    $this->assertDBNull('CRM_Price_BAO_PriceField', $pricefield->id, 'name',
      'id', 'Check DB for non-existence of Price Field.'
    );

    CRM_Price_BAO_PriceSet::deleteSet($priceset->id);
    $this->assertDBNull('CRM_Price_BAO_PriceSet', $priceset->id, 'title',
      'id', 'Check DB for non-existence of Price Set.'
    );

    $this->participantDelete($participant->id);
    $this->contactDelete($this->_contactId);
    $this->eventDelete($eventId);
  }

  /**
   * Test various self-service eligibility scenarios.
   *
   * @dataProvider selfServiceScenarios
   * @param $selfSvcEnabled
   * @param $selfSvcHours
   * @param $hoursToEvent
   * @param $participantStatusId
   * @param $isBackOffice
   * @param $successExpected  A boolean that indicates whether this test should pass or fail.
   */
  public function testGetSelfServiceEligibility($selfSvcEnabled, $selfSvcHours, $hoursToEvent, $participantStatusId, $isBackOffice, $successExpected) {
    $participantId = $this->participantCreate(['contact_id' => $this->_contactId, 'event_id' => $this->_eventId, 'status_id' => $participantStatusId]);
    $now = new Datetime();
    $startDate = $now->add(new DateInterval("PT{$hoursToEvent}H"))->format('Y-m-d H:i:s');
    $this->callAPISuccess('Event', 'create', [
      'id' => $this->_eventId,
      'allow_selfcancelxfer' => $selfSvcEnabled,
      'selfcancelxfer_time' => $selfSvcHours,
      'start_date' => $startDate,
    ]);
    $url = CRM_Utils_System::url('civicrm/event/info', "reset=1&id={$this->_eventId}");
    // If we return without an error, then success.  But we don't always expect success.
    try {
      CRM_Event_BAO_Participant::getSelfServiceEligibility($participantId, $url, $isBackOffice);
    }
    catch (\Exception $e) {
      if ($successExpected === FALSE) {
        return;
      }
      else {
        $this->fail();
      }
    }
    if ($successExpected === FALSE) {
      $this->fail();
    }
  }

  public function selfServiceScenarios() {
    // Standard pass scenario
    $scenarios[] = [
      'selfSvcEnabled' => TRUE,
      'selfSvcHours' => 12,
      'hoursToEvent' => 16,
      'participantStatusId' => 1,
      'isBackOffice' => FALSE,
      'successExpected' => TRUE,
    ];
    // Too late to self-service
    $scenarios[] = [
      'selfSvcEnabled' => TRUE,
      'selfSvcHours' => 12,
      'hoursToEvent' => 8,
      'participantStatusId' => 1,
      'isBackOffice' => FALSE,
      'successExpected' => FALSE,
    ];
    // Participant status is other than "Registered".
    $scenarios[] = [
      'selfSvcEnabled' => TRUE,
      'selfSvcHours' => 12,
      'hoursToEvent' => 16,
      'participantStatusId' => 2,
      'isBackOffice' => FALSE,
      'successExpected' => FALSE,
    ];
    return $scenarios;
  }

}
