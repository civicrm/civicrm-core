<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 * Class CRM_Event_BAO_ParticipantTest
 */
class CRM_Event_BAO_ParticipantTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
    $this->_contactId = Contact::createIndividual();
    $this->_eventId = Event::create($this->_contactId);
  }

  /**
   * Add() method (add and edit modes of participant)
   */
  public function testAdd() {
    $params = array(
      'send_receipt' => 1,
      'is_test' => 0,
      'is_pay_later' => 0,
      'event_id' => $this->_eventId,
      'register_date' => date('Y-m-d') . " 00:00:00",
      'role_id' => 1,
      'status_id' => 1,
      'source' => 'Event_' . $this->_eventId,
      'contact_id' => $this->_contactId,
    );

    // New Participant Created
    $participant = CRM_Event_BAO_Participant::add($params);

    $this->assertDBNotNull('CRM_Event_BAO_Participant', $this->_contactId, 'id',
      'contact_id', 'Check DB for Participant of the contact'
    );

    $this->assertDBCompareValue('CRM_Event_BAO_Participant', $participant->id, 'contact_id',
      'id', $this->_contactId, 'Check DB for contact of the participant'
    );

    $params = array_merge($params, array(
      'id' => $participant->id,
      'role_id' => 2,
      'status_id' => 3,
    ));

    // Participant Edited
    $updatedParticipant = CRM_Event_BAO_Participant::add($params);
    $this->assertDBCompareValue('CRM_Event_BAO_Participant', $updatedParticipant->id, 'role_id',
      'id', 2, 'Check DB for updated role id of the participant'
    );

    $this->assertDBCompareValue('CRM_Event_BAO_Participant', $updatedParticipant->id, 'status_id',
      'id', 3, 'Check DB for updated status id  of the participant'
    );

    Contact::delete($this->_contactId);
    Event::delete($this->_eventId);
  }

  /**
   * GetValues() method (fetch value of participant)
   */
  public function testgetValuesWithValidParams() {
    $participantId = Participant::create($this->_contactId, $this->_eventId);
    $params = array('id' => $participantId);
    $values = $ids = array();

    $fetchParticipant = CRM_Event_BAO_Participant::getValues($params, $values, $ids);
    $compareValues = $fetchParticipant[$participantId];

    $params = array(
      'send_receipt' => 1,
      'is_test' => 0,
      'is_pay_later' => 0,
      'event_id' => $this->_eventId,
      'register_date' => date('Y-m-d') . " 00:00:00",
      'role_id' => 1,
      'status_id' => 1,
      'source' => 'Event_' . $this->_eventId,
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
      'transferred_to_contact_id' => NULL, //CRM-16761
    );

    foreach ($compareValues as $key => $value) {
      if (substr($key, 0, 1) != '_' && $key != 'N') {
        $this->assertEquals($compareValues->$key, $params[$key], 'Check for ' . $key . ' for given participant');
      }
    }

    Participant::delete($participantId);
    Contact::delete($this->_contactId);
    Event::delete($this->_eventId);
  }

  /**
   * GetValues() method (checking for behavior when params are empty )
   */
  public function testgetValuesWithoutValidParams() {
    $params = $values = $ids = array();
    $participantId = Participant::create($this->_contactId, $this->_eventId);
    $fetchParticipant = CRM_Event_BAO_Participant::getValues($params, $values, $ids);
    $this->assertNull($fetchParticipant);

    Contact::delete($this->_contactId);
    Event::delete($this->_eventId);
  }

  /**
   * EventFull() method (checking the event for full )
   */
  public function testEventFull() {
    $eventParams = array(
      'max_participants' => 1,
      'id' => $this->_eventId,
    );
    CRM_Event_BAO_Event::add($eventParams);

    $participantId = Participant::create($this->_contactId, $this->_eventId);
    $eventFull = CRM_Event_BAO_Participant::eventFull($this->_eventId);

    $this->assertEquals($eventFull, 'This event is full.', 'Checking if Event is full.');

    Participant::delete($participantId);
    Contact::delete($this->_contactId);
    Event::delete($this->_eventId);
  }

  /**
   * ImportableFields() method ( Checking the Event's Importable Fields )
   */
  public function testimportableFields() {
    $importableFields = CRM_Event_BAO_Participant::importableFields();
    $this->assertNotEquals(count($importableFields), 0, 'Checking array not to be empty.');

    Contact::delete($this->_contactId);
    Event::delete($this->_eventId);
  }

  /**
   * ParticipantDetails() method ( Checking the Participant Details )
   */
  public function testparticipantDetails() {
    $participantId = Participant::create($this->_contactId, $this->_eventId);
    $params = array('name' => 'Doe, John', 'title' => 'Test Event');

    $participantDetails = CRM_Event_BAO_Participant::participantDetails($participantId);

    $this->assertEquals(count($participantDetails), 3, 'Equating the array contains.');
    $this->assertEquals($participantDetails['name'], $params['name'], 'Checking Name of Participant.');
    $this->assertEquals($participantDetails['title'], $params['title'], 'Checking Event Title in which participant is enroled.');

    Participant::delete($participantId);
    Contact::delete($this->_contactId);
    Event::delete($this->_eventId);
  }

  /**
   * DeleteParticipant() method ( Delete a Participant )
   */
  public function testdeleteParticipant() {
    $params = array(
      'send_receipt' => 1,
      'is_test' => 0,
      'is_pay_later' => 0,
      'event_id' => $this->_eventId,
      'register_date' => date('Y-m-d') . " 00:00:00",
      'role_id' => 1,
      'status_id' => 1,
      'source' => 'Event_' . $this->_eventId,
      'contact_id' => $this->_contactId,
    );

    // New Participant Created
    $participant = CRM_Event_BAO_Participant::add($params);

    $this->assertDBNotNull('CRM_Event_BAO_Participant', $this->_contactId, 'id',
      'contact_id', 'Check DB for Participant of the contact'
    );

    $this->assertDBCompareValue('CRM_Event_BAO_Participant', $participant->id, 'contact_id',
      'id', $this->_contactId, 'Check DB for contact of the participant'
    );

    $deleteParticipant = CRM_Event_BAO_Participant::deleteParticipant($participant->id);
    $this->assertDBNull('CRM_Event_BAO_Participant', $participant->id, 'contact_id', 'id', 'Check DB for deleted Participant.');

    Contact::delete($this->_contactId);
    Event::delete($this->_eventId);
  }

  /**
   * CheckDuplicate() method ( Checking for Duplicate Participant returns array of participant id)
   */
  public function testcheckDuplicate() {
    $duplicate = array();

    //Creating 3 new participants
    for ($i = 0; $i < 3; $i++) {
      $partiId[] = Participant::create($this->_contactId, $this->_eventId);
    }

    $params = array('event_id' => $this->_eventId, 'contact_id' => $this->_contactId);
    $checkDuplicate = CRM_Event_BAO_Participant::checkDuplicate($params, $duplicate);

    $this->assertEquals(count($duplicate), 3, 'Equating the array contains with duplicate array.');

    //Checking for the duplicate participant
    foreach ($duplicate as $key => $value) {
      $this->assertEquals($partiId[$key], $duplicate[$key], 'Equating the contactid which is in the database.');
    }

    //Deleting all participant
    for ($i = 0; $i < 3; $i++) {
      $partidel[] = Participant::delete($partiId[$i]);
    }

    Contact::delete($this->_contactId);
    Event::delete($this->_eventId);
  }

  /**
   * Create() method (create and updation of participant)
   */
  public function testCreate() {
    $params = array(
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
    );

    $participant = CRM_Event_BAO_Participant::create($params);
    //Checking for Contact id in the participant table.
    $pid = $this->assertDBNotNull('CRM_Event_DAO_Participant', $this->_contactId, 'id',
      'contact_id', 'Check DB for Participant of the contact'
    );

    //Checking for Activity added in the table for relative participant.
    $this->assertDBCompareValue('CRM_Activity_DAO_Activity', $this->_contactId, 'source_record_id',
      'source_contact_id', $participant->id, 'Check DB for activity added for the participant'
    );

    $params = array_merge($params, array(
      'id' => $participant->id,
      'role_id' => 2,
      'status_id' => 3,
      'note' => 'Test Event in edit mode is running successfully ....',
    ));

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
    $deleteParticipant = CRM_Event_BAO_Participant::deleteParticipant($participant->id);
    $this->assertDBNull('CRM_Event_DAO_Participant', $this->_contactId, 'id',
      'contact_id', 'Check DB for deleted participant. Should be NULL.'
    );

    Contact::delete($this->_contactId);
    Event::delete($this->_eventId);
  }

  /**
   * ExportableFields() method ( Exportable Fields for Participant)
   */
  public function testexportableFields() {
    $exportableFields = CRM_Event_BAO_Participant::exportableFields();
    $this->assertNotEquals(count($exportableFields), 0, 'Checking array not to be empty.');

    Contact::delete($this->_contactId);
    Event::delete($this->_eventId);
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
    $paramsField = array(
      'label' => 'Price Field',
      'name' => CRM_Utils_String::titleToVar('Price Field'),
      'html_type' => 'Text',
      'price' => 10,
      'option_label' => array('1' => 'Price Field'),
      'option_value' => array('1' => 10),
      'option_name' => array('1' => 10),
      'option_weight' => array('1' => 1),
      'is_display_amounts' => 1,
      'weight' => 1,
      'options_per_line' => 1,
      'is_active' => array('1' => 1),
      'price_set_id' => $priceset->id,
      'is_enter_qty' => 1,
    );

    $ids = array();
    $pricefield = CRM_Price_BAO_PriceField::create($paramsField, $ids);

    //Checking for priceset added in the table.
    $this->assertDBCompareValue('CRM_Price_BAO_PriceField', $pricefield->id, 'label',
      'id', $paramsField['label'], 'Check DB for created pricefield'
    );

    $eventId = $this->_eventId;
    $participantParams = array(
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
    );

    $participant = CRM_Event_BAO_Participant::add($participantParams);

    //Checking for participant added in the table.
    $this->assertDBCompareValue('CRM_Event_BAO_Participant', $this->_contactId, 'id',
      'contact_id', $participant->id, 'Check DB for created participant'
    );

    $values = array();
    $ids = array();
    $params = array('id' => $participant->id);

    CRM_Event_BAO_Participant::getValues($params, $values, $ids);
    $this->assertNotEquals(count($values), 0, 'Checking for empty array.');

    CRM_Event_BAO_Participant::resolveDefaults($values[$participant->id]);

    if ($values[$participant->id]['fee_level']) {
      CRM_Event_BAO_Participant::fixEventLevel($values[$participant->id]['fee_level']);
    }

    $deletePricefield = CRM_Price_BAO_PriceField::deleteField($pricefield->id);
    $this->assertDBNull('CRM_Price_BAO_PriceField', $pricefield->id, 'name',
      'id', 'Check DB for non-existence of Price Field.'
    );

    $deletePriceset = CRM_Price_BAO_PriceSet::deleteSet($priceset->id);
    $this->assertDBNull('CRM_Price_BAO_PriceSet', $priceset->id, 'title',
      'id', 'Check DB for non-existence of Price Set.'
    );

    Participant::delete($participant->id);
    Contact::delete($this->_contactId);
    Event::delete($eventId);
  }

}
