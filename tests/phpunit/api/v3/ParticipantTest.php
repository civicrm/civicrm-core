<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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

require_once 'CiviTest/CiviUnitTestCase.php';

/**
 * Test class for Batch API - civicrm_participant_*
 *
 *  @package CiviCRM_APIv3
 */
require_once 'CRM/Utils/DeprecatedUtils.php';
require_once 'CiviTest/CiviUnitTestCase.php';
class api_v3_ParticipantTest extends CiviUnitTestCase {

  protected $_apiversion;
  protected $_entity;
  protected $_contactID;
  protected $_contactID2;
  protected $_createdParticipants;
  protected $_participantID;
  protected $_eventID;
  protected $_individualId;
  protected $_params;
  public $_eNoticeCompliant = FALSE;

  function get_info() {
    return array(
      'name' => 'Participant Create',
      'description' => 'Test all Participant Create API methods.',
      'group' => 'CiviCRM API Tests',
    );
  }

  function setUp() {
    $this->_apiversion = 3;
    parent::setUp();
    $this->_entity  = 'participant';
    $event          = $this->eventCreate(NULL);
    $this->_eventID = $event['id'];

    $this->_contactID = $this->individualCreate(NULL);

    $this->_createdParticipants = array();
    $this->_individualId = $this->individualCreate(NULL);

    $this->_participantID = $this->participantCreate(array('contactID' => $this->_contactID, 'eventID' => $this->_eventID));
    $this->_contactID2 = $this->individualCreate(NULL);
    $this->_participantID2 = $this->participantCreate(array('contactID' => $this->_contactID2, 'eventID' => $this->_eventID, 'version' => $this->_apiversion));
    $this->_participantID3 = $this->participantCreate(array('contactID' => $this->_contactID2, 'eventID' => $this->_eventID, 'version' => $this->_apiversion));
    $this->_params = array(
      'contact_id' => $this->_contactID,
      'event_id' => $this->_eventID,
      'status_id' => 1,
      'role_id' => 1,
      // to ensure it matches later on
      'register_date' => '2007-07-21 00:00:00',
      'source' => 'Online Event Registration: API Testing',
      'version' => $this->_apiversion,
    );
  }

  function tearDown() {
    $this->eventDelete($this->_eventID);
    $tablesToTruncate = array(
      'civicrm_custom_group', 'civicrm_custom_field', 'civicrm_contact', 'civicrm_participant'
    );
    // true tells quickCleanup to drop any tables that might have been created in the test
    $this->quickCleanup($tablesToTruncate, TRUE);
  }

  /**
   * check with complete array + custom field
   * Note that the test is written on purpose without any
   * variables specific to participant so it can be replicated into other entities
   * and / or moved to the automated test suite
   */
  function testCreateWithCustom() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);

    $params = $this->_params;
    $params['custom_' . $ids['custom_field_id']] = "custom string";

    $result = civicrm_api($this->_entity, 'create', $params);
    $this->assertEquals($result['id'], $result['values'][$result['id']]['id']);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result);

    $check = civicrm_api($this->_entity, 'get', array('version' => 3, 'id' => $result['id']));
    $this->assertEquals("custom string", $check['values'][$check['id']]['custom_' . $ids['custom_field_id']], ' in line ' . __LINE__);

    $this->customFieldDelete($ids['custom_field_id']);
    $this->customGroupDelete($ids['custom_group_id']);
  }


  ///////////////// civicrm_participant_get methods

  /**
   * check with wrong params type
   */
  function testGetWrongParamsType() {
    $params = 'a string';
    $result = $this->callAPIFailure('participant', 'get', $params);
  }

  /**
   * Test civicrm_participant_get with empty params
   */
  function testGetEmptyParams() {
    $this->callAPISuccess('participant', 'get', array());
  }

  /**
   * check with participant_id
   */
  function testGetParticipantIdOnly() {
    $params = array(
      'participant_id' => $this->_participantID,
      'version' => $this->_apiversion,
      'return' => array(
        'participant_id',
        'event_id',
        'participant_register_date',
        'participant_source',
      )
    );
    $result = civicrm_api('participant', 'get', $params);
    $this->assertAPISuccess($result, " in line " . __LINE__);
    $this->assertEquals($result['values'][$this->_participantID]['event_id'], $this->_eventID, "in line " . __LINE__);
    $this->assertEquals($result['values'][$this->_participantID]['participant_register_date'], '2007-02-19 00:00:00', "in line " . __LINE__);
    $this->assertEquals($result['values'][$this->_participantID]['participant_source'], 'Wimbeldon', "in line " . __LINE__);
      $params = array(
      'id' => $this->_participantID,
      'version' => $this->_apiversion,
      'return' => 'id,participant_register_date,event_id',

    );
    $result = civicrm_api('participant', 'get', $params);
    $this->assertEquals($result['values'][$this->_participantID]['event_id'], $this->_eventID);
    $this->assertEquals($result['values'][$this->_participantID]['participant_register_date'], '2007-02-19 00:00:00');

  }

  /**
   * check with params id
   */
  function testGetParamsAsIdOnly() {
    $params = array(
      'id' => $this->_participantID,
      'version' => $this->_apiversion,
    );
    $result = civicrm_api('participant', 'get', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result);
    $this->assertEquals($result['values'][$this->_participantID]['event_id'], $this->_eventID);
    $this->assertEquals($result['values'][$this->_participantID]['participant_register_date'], '2007-02-19 00:00:00');
    $this->assertEquals($result['values'][$this->_participantID]['participant_source'], 'Wimbeldon');
    $this->assertEquals($result['id'], $result['values'][$this->_participantID]['id']);
  }

  /**
   * check with params id
   */
  function testGetNestedEventGet() {
    //create a second event & add participant to it.
    $event = $this->eventCreate(NULL);
    civicrm_api('participant', 'create', array('version' => 3, 'event_id' => $event['id'], 'contact_id' => $this->_contactID));


    $description = "use nested get to get an event";
    $subfile     = "NestedEventGet";
    $params      = array(
      'id' => $this->_participantID,
      'version' => $this->_apiversion,
      'api.event.get' => 1,
    );
    $result = civicrm_api('participant', 'get', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertAPISuccess($result);
    $this->assertEquals($result['values'][$this->_participantID]['event_id'], $this->_eventID);
    $this->assertEquals($result['values'][$this->_participantID]['participant_register_date'], '2007-02-19 00:00:00');
    $this->assertEquals($result['values'][$this->_participantID]['participant_source'], 'Wimbeldon');
    $this->assertEquals($this->_eventID, $result['values'][$this->_participantID]['api.event.get']['id']);
  }
  /*
     * Check Participant Get respects return properties
     */
  function testGetWithReturnProperties() {
    $params = array(
      'contact_id' => $this->_contactID,
      'version' => $this->_apiversion,
      'return.status_id' => 1,
      'return.participant_status_id' => 1,
      'options' => array('limit' => 1)
    );
    $result = civicrm_api('participant', 'get', $params);
    $this->assertArrayHasKey('participant_status_id', $result['values'][$result['id']]);
  }

  /**
   * check with contact_id
   */
  function testGetContactIdOnly() {
    $params = array(
      'contact_id' => $this->_contactID,
      'version' => $this->_apiversion,
    );
    $participant = civicrm_api('participant', 'get', $params);

    $this->assertEquals($this->_participantID, $participant['id'],
      "In line " . __LINE__
    );
    $this->assertEquals($this->_eventID, $participant['values'][$participant['id']]['event_id'],
      "In line " . __LINE__
    );
    $this->assertEquals('2007-02-19 00:00:00', $participant['values'][$participant['id']]['participant_register_date'],
      "In line " . __LINE__
    );
    $this->assertEquals('Wimbeldon', $participant['values'][$participant['id']]['participant_source'],
      "In line " . __LINE__
    );
    $this->assertEquals($participant['id'], $participant['values'][$participant['id']]['id'],
      "In line " . __LINE__
    );
  }

  /**
   * check with event_id
   * fetch first record
   */
  function testGetMultiMatchReturnFirst() {
    $params = array(
      'event_id' => $this->_eventID,
      'rowCount' => 1,
      'version' => $this->_apiversion,
    );

    $participant = civicrm_api('participant', 'get', $params);
    $this->assertNotNull($participant['id']);
  }

  /**
   * check with event_id
   * in v3 this should return all participants
   */
  function testGetMultiMatchNoReturnFirst() {
    $params = array(
      'event_id' => $this->_eventID,
      'version' => $this->_apiversion,
    );
    $participant = civicrm_api('participant', 'get', $params);
    $this->assertEquals($participant['is_error'], 0);
    $this->assertNotNull($participant['count'], 3);
  }

  ///////////////// civicrm_participant_get methods

  /**
   * Test civicrm_participant_get with wrong params type
   */
  function testSearchWrongParamsType() {
    $params = 'a string';
    $result = $this->callAPIFailure('participant', 'get', $params);
  }

  /**
   * Test civicrm_participant_get with empty params
   * In this case all the participant records are returned.
   */
  function testSearchEmptyParams() {
    $params = array('version' => $this->_apiversion);
    $result = civicrm_api('participant', 'get', $params);

    // expecting 3 participant records
    $this->assertEquals($result['count'], 3);
  }

  /**
   * check with participant_id
   */
  function testSearchParticipantIdOnly() {
    $params = array(
      'participant_id' => $this->_participantID,
      'version' => $this->_apiversion,
    );
    $participant = civicrm_api('participant', 'get', $params);
    $this->assertEquals($participant['values'][$this->_participantID]['event_id'], $this->_eventID);
    $this->assertEquals($participant['values'][$this->_participantID]['participant_register_date'], '2007-02-19 00:00:00');
    $this->assertEquals($participant['values'][$this->_participantID]['participant_source'], 'Wimbeldon');
  }

  /**
   * check with contact_id
   */
  function testSearchContactIdOnly() {
    // Should get 2 participant records for this contact.
    $params = array(
      'contact_id' => $this->_contactID2,
      'version' => $this->_apiversion,
    );
    $participant = civicrm_api('participant', 'get', $params);

    $this->assertEquals($participant['count'], 2);
  }

  /**
   * check with event_id
   */
  function testSearchByEvent() {
    // Should get >= 3 participant records for this event. Also testing that last_name and event_title are returned.
    $params = array(
      'event_id' => $this->_eventID,
      'return.last_name' => 1,
      'return.event_title' => 1,
      'version' => $this->_apiversion,
    );
    $participant = civicrm_api('participant', 'get', $params);
    if ($participant['count'] < 3) {
      $this->fail("Event search returned less than expected miniumum of 3 records.");
    }

    $this->assertEquals($participant['values'][$this->_participantID]['last_name'], 'Anderson');
    $this->assertEquals($participant['values'][$this->_participantID]['event_title'], 'Annual CiviCRM meet');
  }

  /**
   * check with event_id
   * fetch with limit
   */
  function testSearchByEventWithLimit() {
    // Should 2 participant records since we're passing rowCount = 2.
    $params = array(
      'event_id' => $this->_eventID,
      'rowCount' => 2,
      'version' => $this->_apiversion,
    );
    $participant = civicrm_api('participant', 'get', $params);

    $this->assertEquals($participant['count'], 2, 'in line ' . __LINE__);
  }

  ///////////////// civicrm_participant_create methods

  /**
   * Test civicrm_participant_create with wrong params type
   */
  function testCreateWrongParamsType() {
    $params = 'a string';
    $result = $this->callAPIFailure('participant', 'create', $params);
  }

  /**
   * Test civicrm_participant_create with empty params
   */
  function testCreateEmptyParams() {
    $params = array();
    $result = $this->callAPIFailure('participant', 'create', $params);
  }

  /**
   * check with event_id
   */
  function testCreateMissingContactID() {
    $params = array(
      'event_id' => $this->_eventID,
      'version' => $this->_apiversion,
    );
    $participant = civicrm_api('participant', 'create', $params);
    if (CRM_Utils_Array::value('id', $participant)) {
      $this->_createdParticipants[] = $participant['id'];
    }
    $this->assertEquals($participant['is_error'], 1);
    $this->assertNotNull($participant['error_message']);
  }

  /**
   * check with contact_id
   * without event_id
   */
  function testCreateMissingEventID() {
    $params = array(
      'contact_id' => $this->_contactID,
      'version' => $this->_apiversion,
    );
    $participant = civicrm_api('participant', 'create', $params);
    if (CRM_Utils_Array::value('id', $participant)) {
      $this->_createdParticipants[] = $participant['id'];
    }
    $this->assertEquals($participant['is_error'], 1);
    $this->assertNotNull($participant['error_message']);
  }

  /**
   * check with contact_id & event_id
   */
  function testCreateEventIdOnly() {
    $params = array(
      'contact_id' => $this->_contactID,
      'event_id' => $this->_eventID,
      'version' => $this->_apiversion,
    );
    $participant = civicrm_api('participant', 'create', $params);
    $this->assertAPISuccess($participant);
    $this->_participantID = $participant['id'];

    if (!$participant['is_error']) {
      // assertDBState compares expected values in $match to actual values in the DB
      unset($params['version']);
      $this->assertDBState('CRM_Event_DAO_Participant', $participant['id'], $params);
    }
  }

  /**
   * check with complete array
   */
  function testCreateAllParams() {
    $params = $this->_params;

    $participant = civicrm_api('participant', 'create', $params);
    $this->assertNotEquals($participant['is_error'], 1, 'in line ' . __LINE__);
    $this->_participantID = $participant['id'];
    if (!$participant['is_error']) {
      // assertDBState compares expected values in $match to actual values in the DB
      unset($params['version']);
      $this->assertDBState('CRM_Event_DAO_Participant', $participant['id'], $params);
    }
  }
  /*
     * Test to check if receive date is being changed per CRM-9763
     */
  function testCreateUpdateReceiveDate() {
    $participant = civicrm_api('participant', 'create', $this->_params);
    $update = array(
      'version' => 3,
      'id' => $participant['id'],
      'status_id' => 2,
    );
    civicrm_api('participant', 'create', $update);
    $this->getAndCheck(array_merge($this->_params, $update), $participant['id'], 'participant');
  }
  /*
     * Test to check if participant fee level is being changed per CRM-9781
     */
  function testCreateUpdateParticipantFeeLevel() {
    $myParams = $this->_params + array('participant_fee_level' => CRM_Core_DAO::VALUE_SEPARATOR . "fee" . CRM_Core_DAO::VALUE_SEPARATOR);
    $participant = civicrm_api('participant', 'create', $myParams);
    $this->assertAPISuccess($participant);
    $update = array(
      'version' => 3,
      'id' => $participant['id'],
      'status_id' => 2,
    );
    civicrm_api('participant', 'create', $update);
    $this->assertEquals($participant['values'][$participant['id']]['participant_fee_level'],
      $update['values'][$participant['id']]['participant_fee_level']
    );

    civicrm_api('participant', 'delete', array('version' => 3, 'id' => $participant['id']));
  }
  /*
     * Test to check if participant fee level is being changed per CRM-9781
     * Try again  without a custom separater to check that one isn't added
     * (get & check won't accept an array)
     */
  function testUpdateCreateParticipantFeeLevelNoSeparator() {

    $myParams = $this->_params + array('participant_fee_level' => "fee");
    $participant = civicrm_api('participant', 'create', $myParams);
    $this->assertAPISuccess($participant);
    $update = array(
      'version' => 3,
      'id' => $participant['id'],
      'status_id' => 2,
    );
    civicrm_api('participant', 'create', $update);
    $this->assertEquals($participant['values'][$participant['id']]['fee_level'],
      $myParams['participant_fee_level']
    );
    $this->getAndCheck($update, $participant['id'], 'participant');
  }
  ///////////////// civicrm_participant_update methods

  /**
   * Test civicrm_participant_update with wrong params type
   */
  function testUpdateWrongParamsType() {
    $params = 'a string';
    $result = $this->callAPIFailure('participant', 'create', $params);
    $this->assertEquals('Input variable `params` is not an array', $result['error_message'], 'In line ' . __LINE__);
  }

  /**
   * check with empty array
   */
  function testUpdateEmptyParams() {
    $params = array('version' => $this->_apiversion);
    $participant = $this->callAPIFailure('participant', 'create', $params);
    $this->assertEquals($participant['error_message'], 'Mandatory key(s) missing from params array: event_id, contact_id');
  }

  /**
   * check without event_id
   */
  function testUpdateWithoutEventId() {
    $participantId = $this->participantCreate(array('contactID' => $this->_individualId, 'eventID' => $this->_eventID, 'version' => $this->_apiversion));
    $params = array(
      'contact_id' => $this->_individualId,
      'status_id' => 3,
      'role_id' => 3,
      'register_date' => '2006-01-21',
      'source' => 'US Open',
      'event_level' => 'Donation',
      'version' => $this->_apiversion,
    );
    $participant = $this->callAPIFailure('participant', 'create', $params);
    $this->assertEquals($participant['error_message'], 'Mandatory key(s) missing from params array: event_id');
    // Cleanup created participant records.
    $result = $this->participantDelete($participantId);
  }

  /**
   * check with Invalid participantId
   */
  function testUpdateWithWrongParticipantId() {
    $params = array(
      'id' => 1234,
      'status_id' => 3,
      'role_id' => 3,
      'register_date' => '2006-01-21',
      'source' => 'US Open',
      'event_level' => 'Donation',
      'version' => $this->_apiversion,
    );
    $participant = $this->callAPIFailure('Participant', 'update', $params);
  }

  /**
   * check with Invalid ContactId
   */
  function testUpdateWithWrongContactId() {
    $participantId = $this->participantCreate(array(
      'contactID' => $this->_individualId,
        'eventID' => $this->_eventID,
      ), $this->_apiversion);
    $params = array(
      'id' => $participantId,
      'contact_id' => 12345,
      'status_id' => 3,
      'role_id' => 3,
      'register_date' => '2006-01-21',
      'source' => 'US Open',
      'event_level' => 'Donation',
      'version' => $this->_apiversion,
    );
    $participant = $this->callAPIFailure('participant', 'create', $params);
    $result = $this->participantDelete($participantId);
  }

  /**
   * check with complete array
   */
  function testUpdate() {
    $participantId = $this->participantCreate(array('contactID' => $this->_individualId, 'eventID' => $this->_eventID, $this->_apiversion));
    $params = array(
      'id' => $participantId,
      'contact_id' => $this->_individualId,
      'event_id' => $this->_eventID,
      'status_id' => 3,
      'role_id' => 3,
      'register_date' => '2006-01-21',
      'source' => 'US Open',
      'event_level' => 'Donation',
      'version' => $this->_apiversion,
    );
    $participant = civicrm_api('participant', 'create', $params);
    $this->assertNotEquals($participant['is_error'], 1);


    if (!$participant['is_error']) {
      $params['id'] = CRM_Utils_Array::value('id', $participant);

      // Create $match array with DAO Field Names and expected values
      $match = array(
        'id' => CRM_Utils_Array::value('id', $participant),
      );
      // assertDBState compares expected values in $match to actual values in the DB
      $this->assertDBState('CRM_Event_DAO_Participant', $participant['id'], $match);
    }
    // Cleanup created participant records.
    $result = $this->participantDelete($params['id']);
  }



  ///////////////// civicrm_participant_delete methods

  /**
   * Test civicrm_participant_delete with wrong params type
   */
  function testDeleteWrongParamsType() {
    $params = 'a string';
    $result = $this->callAPIFailure('participant', 'delete', $params);
  }

  /**
   * Test civicrm_participant_delete with empty params
   */
  function testDeleteEmptyParams() {
    $params = array();
    $result = $this->callAPIFailure('participant', 'delete', $params);
  }

  /**
   * check with participant_id
   */
  function testParticipantDelete() {
    $params = array(
      'id' => $this->_participantID,
      'version' => $this->_apiversion,
    );
    $participant = civicrm_api('participant', 'delete', $params);
    $this->assertAPISuccess($participant);
    $this->assertDBState('CRM_Event_DAO_Participant', $this->_participantID, NULL, TRUE);
  }

  /**
   * check without participant_id
   * and with event_id
   * This should return an error because required param is missing..
   */
  function testParticipantDeleteMissingID() {
    $params = array(
      'event_id' => $this->_eventID,
      'version' => $this->_apiversion,
    );
    $participant = $this->callAPIFailure('participant', 'delete', $params);
    $this->assertNotNull($participant['error_message']);
  }
  /*
    * delete with a get - a 'criteria delete'
    */
  function testNestedDelete() {
    $description  = "Criteria delete by nesting a GET & a DELETE";
    $subfile      = "NestedDelete";
    $participants = civicrm_api('Participant', 'Get', array('version' => 3));
    $this->assertEquals($participants['count'], 3);
    $params = array('version' => 3, 'contact_id' => $this->_contactID2, 'api.participant.delete' => 1);
    $participants = civicrm_api('Participant', 'Get', $params);
    $this->documentMe($params, $participants, __FUNCTION__, __FILE__, $description, $subfile, 'Get');
    $participants = civicrm_api('Participant', 'Get', array('version' => 3));
    $this->assertEquals(1, $participants['count'], "only one participant should be left. line " . __LINE__);
  }
  /*
     * Test creation of a participant with an associated contribution
     */
  function testCreateParticipantWithPayment() {
    $this->_contributionTypeId = $this->contributionTypeCreate();
    $description = "single function to create contact w partipation & contribution. Note that in the
      case of 'contribution' the 'create' is implied (api.contribution.create)";
    $subfile = "CreateParticipantPayment";
    $params = array(
      'contact_type' => 'Individual',
      'display_name' => 'dlobo',
      'version' => $this->_apiversion,
      'api.participant' => array(
        'event_id' => $this->_eventID,
        'status_id' => 1,
        'role_id' => 1,
        'format.only_id' => 1,
      ),
      'api.contribution.create' => array(
        'financial_type_id' => 1,
        'total_amount' => 100,
        'format.only_id' => 1,
      ),
      'api.participant_payment.create' => array(
        'contribution_id' => '$value.api.contribution.create',
        'participant_id' => '$value.api.participant',
      ),
    );

    $result = civicrm_api('contact', 'create', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertEquals(1, $result['values'][$result['id']]['api.participant_payment.create']['count']);
    civicrm_api('contact', 'delete', array('id' => $result['id'], 'version' => $this->_apiversion));
  }

  function testParticipantFormattedwithDuplicateParams() {
    $participantContact = $this->individualCreate(NULL);
    $params = array(
      'contact_id' => $participantContact,
      'event_id' => $this->_eventID,
      'version' => 3,
    );
    require_once 'CRM/Event/Import/Parser.php';
    $onDuplicate = CRM_Event_Import_Parser::DUPLICATE_NOCHECK;
    $participant = _civicrm_api3_deprecated_create_participant_formatted($params, $onDuplicate);
    $this->assertEquals($participant['is_error'], 0);
  }

  /**
   * Test civicrm_participant_formatted with wrong $onDuplicate
   */
  function testParticipantFormattedwithWrongDuplicateConstant() {
    $participantContact = $this->individualCreate(NULL);
    $params = array(
      'contact_id' => $participantContact,
      'event_id' => $this->_eventID,
      'version' => 3,
    );
    $onDuplicate = 11;
    $participant = _civicrm_api3_deprecated_create_participant_formatted($params, $onDuplicate);
    $this->assertEquals($participant['is_error'], 0);
  }

  function testParticipantcheckWithParams() {
    $participantContact = $this->individualCreate(NULL);
    $params = array(
      'contact_id' => $participantContact,
      'event_id' => $this->_eventID,
    );
    require_once 'CRM/Event/Import/Parser.php';
    $participant = _civicrm_api3_deprecated_participant_check_params($params);
    $this->assertEquals($participant, TRUE, 'Check the returned True');
  }

  /**
   * check get with role id - create 2 registrations with different roles.
   * Test that get without role var returns 2 & with returns one
   TEST COMMENteD OUT AS HAVE GIVIEN UP ON using filters on get
   function testGetParamsRole()
   {
   require_once 'CRM/Event/PseudoConstant.php';
   CRM_Event_PseudoConstant::flush('participantRole');
   $participantRole2 = civicrm_api('Participant', 'Create', array('version' => 3, 'id' => $this->_participantID2, 'participant_role_id' => 2));

   $params = array(

   'version' => $this->_apiversion,

   );
   $result = civicrm_api('participant','get', $params);
   $this->assertAPISuccess($result);
   $this->assertEquals($result['count'], 3);

   $params['participant_role_id'] =2;
   $result =  civicrm_api('participant','get', $params);

   $this->assertAPISuccess($result,  "in line " . __LINE__);
   $this->assertEquals(2,$result['count'], "in line " . __LINE__);
   $this->documentMe($params,$result ,__FUNCTION__,__FILE__);
   }
   */
}

