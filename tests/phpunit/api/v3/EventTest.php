<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * Class api_v3_EventTest
 */
class api_v3_EventTest extends CiviUnitTestCase {
  protected $_params;
  protected $_apiversion;
  protected $_entity;

  public function setUp() {
    parent::setUp();
    $this->_apiversion = 3;
    $this->_entity = 'event';
    $this->_params = array(
      array(
        'title' => 'Annual CiviCRM meet',
        'summary' => 'If you have any CiviCRM realted issues or want to track where CiviCRM is heading, Sign up now',
        'description' => 'This event is intended to give brief idea about progess of CiviCRM and giving solutions to common user issues',
        'event_type_id' => 1,
        'is_public' => 1,
        'start_date' => 20081021,
        'end_date' => 20081023,
        'is_online_registration' => 1,
        'registration_start_date' => 20080601,
        'registration_end_date' => '2008-10-15',
        'max_participants' => 100,
        'event_full_text' => 'Sorry! We are already full',
        'is_monetary' => 0,
        'is_active' => 1,
        'is_show_location' => 0,
      ),
      array(
        'title' => 'Annual CiviCRM meet 2',
        'summary' => 'If you have any CiviCRM realted issues or want to track where CiviCRM is heading, Sign up now',
        'description' => 'This event is intended to give brief idea about progess of CiviCRM and giving solutions to common user issues',
        'event_type_id' => 1,
        'is_public' => 1,
        'start_date' => 20101021,
        'end_date' => 20101023,
        'is_online_registration' => 1,
        'registration_start_date' => 20100601,
        'registration_end_date' => '2010-10-15',
        'max_participants' => 100,
        'event_full_text' => 'Sorry! We are already full',
        'is_monetory' => 0,
        'is_active' => 1,
        'is_show_location' => 0,
      ),
    );

    $params = array(
      array(
        'title' => 'Annual CiviCRM meet',
        'event_type_id' => 1,
        'start_date' => 20081021,
      ),
      array(
        'title' => 'Annual CiviCRM meet 2',
        'event_type_id' => 1,
        'start_date' => 20101021,
      ),
    );

    $this->events = array();
    $this->eventIds = array();
    foreach ($params as $event) {
      $result = $this->callAPISuccess('Event', 'Create', $event);
      $this->_events[] = $result;
      $this->_eventIds[] = $result['id'];
    }
  }

  public function tearDown() {
    foreach ($this->eventIds as $eventId) {
      $this->eventDelete($eventId);
    }
    $tablesToTruncate = array(
      'civicrm_participant',
      'civicrm_event',
    );
    $this->quickCleanup($tablesToTruncate, TRUE);
  }

  /**
   * civicrm_event_get methods.
   */
  public function testGetEventById() {
    $params = array(
      'id' => $this->_events[1]['id'],
    );
    $result = $this->callAPISuccess('event', 'get', $params);
    $this->assertEquals($result['values'][$this->_eventIds[1]]['event_title'], 'Annual CiviCRM meet 2');
  }

  public function testGetEventByEventTitle() {

    $params = array(
      'event_title' => 'Annual CiviCRM meet',
      'sequential' => TRUE,
    );

    $result = $this->callAPIAndDocument('event', 'get', $params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
    $this->assertEquals($result['values'][0]['id'], $this->_eventIds[0]);
  }

  public function testGetEventByWrongTitle() {
    $params = array(
      'title' => 'No event with that title',
    );
    $result = $this->callAPISuccess('Event', 'Get', $params);
    $this->assertEquals(0, $result['count']);
  }

  public function testGetEventByIdSort() {
    $params = array(
      'return.sort' => 'id ASC',
      'return.max_results' => 1,
    );
    $result = $this->callAPISuccess('Event', 'Get', $params);
    $this->assertEquals(1, $result['id'], ' in line ' . __LINE__);
    $params = array(
      'options' => array(
        'sort' => 'id DESC',
        'limit' => 1,
      ),
    );

    $result = $this->callAPISuccess('Event', 'Get', $params);
    $this->assertAPISuccess($result, ' in line ' . __LINE__);
    $this->assertEquals(2, $result['id'], ' in line ' . __LINE__);
    $params = array(
      'options' => array(
        'sort' => 'id ASC',
        'limit' => 1,
      ),
    );
    $result = $this->callAPISuccess('Event', 'Get', $params);
    $this->assertEquals(1, $result['id'], ' in line ' . __LINE__);

  }
  /*
   * Getting the id back of an event.
   * Does not work yet, bug in API
   */

  /*
  public function testGetIdOfEventByEventTitle() {
  $params = array(      'title' => 'Annual CiviCRM meet',
  'return' => 'id'
  );

  $result = $this->callAPISuccess('Event', 'Get', $params);
  }
   */


  /**
   * Test 'is.Current' option. Existing event is 'old' so only current should be returned
   */
  public function testGetIsCurrent() {
    $params = array(
      'isCurrent' => 1,
    );
    $currentEventParams = array(
      'start_date' => date('Y-m-d', strtotime('+ 1 day')),
      'end_date' => date('Y-m-d', strtotime('+ 1 week')),
    );
    $currentEventParams = array_merge($this->_params[1], $currentEventParams);
    $currentEvent = $this->callAPISuccess('Event', 'Create', $currentEventParams);
    $description = "Demonstrates use of is.Current option.";
    $subfile = "IsCurrentOption";
    $result = $this->callAPIAndDocument('Event', 'Get', $params, __FUNCTION__, __FILE__, $description, $subfile);
    $allEvents = $this->callAPISuccess('Event', 'Get', array());
    $this->callAPISuccess('Event', 'Delete', array('id' => $currentEvent['id']));
    $this->assertEquals(1, $result['count'], 'confirm only one event found in line ' . __LINE__);
    $this->assertEquals(3, $allEvents['count'], 'confirm three events exist (ie. two not found) ' . __LINE__);
    $this->assertEquals($currentEvent['id'], $result['id'], '');
  }

  /**
   * There has been a schema change & the api needs to buffer developers from it
   */
  public function testGetPaymentProcessorId() {
    $params = $this->_params[0];
    $params['payment_processor_id'] = 1;
    $params['sequential'] = 1;
    $result = $this->callAPISuccess('event', 'create', $params);
    $this->assertEquals(1, $result['values'][0]['payment_processor'][0], "handing of payment processor compatibility");
    $result = $this->callAPISuccess('event', 'get', $params);
    $this->assertEquals($result['values'][0]['payment_processor_id'], 1, "handing get payment processor compatibility");
  }

  public function testInvalidData() {
    $params = $this->_params[0];
    $params['sequential'] = 1;
    $params['loc_block_id'] = 100;
    $result = $this->callAPIFailure('event', 'create', $params);

  }

  /**
   * Test 'is.Current' option. Existing event is 'old' so only current should be returned
   */
  public function testGetSingleReturnIsFull() {
    $contactID = $this->individualCreate();
    $params = array(
      'id' => $this->_eventIds[0],
      'max_participants' => 1,
    );
    $result = $this->callAPISuccess('Event', 'Create', $params);

    $getEventParams = array(
      'id' => $this->_eventIds[0],
      'return.is_full' => 1,
    );

    $currentEvent = $this->callAPISuccess('Event', 'getsingle', $getEventParams);
    $description = "Demonstrates use of return is_full .";
    $subfile = "IsFullOption";
    $this->assertEquals(0, $currentEvent['is_full'], ' is full is set in line ' . __LINE__);
    $this->assertEquals(1, $currentEvent['available_places'], 'available places is set in line ' . __LINE__);
    $participant = $this->callAPISuccess('Participant', 'create', array(
        'participant_status' => 1,
        'role_id' => 1,
        'contact_id' => $contactID,
        'event_id' => $this->_eventIds[0],
      ));
    $currentEvent = $this->callAPIAndDocument('Event', 'getsingle', $getEventParams, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertEquals(1, $currentEvent['is_full'], ' is full is set in line ' . __LINE__);
    $this->assertEquals(0, $currentEvent['available_places'], 'available places is set in line ' . __LINE__);

    $this->contactDelete($contactID);
  }

  /**
   * Legacy support for Contribution Type ID.
   *
   * We need to ensure this is supported as an alias for financial_type_id.
   */
  public function testCreateGetEventLegacyContributionTypeID() {
    $contributionTypeArray = array('contribution_type_id' => 3);
    if (isset($this->_params[0]['financial_type_id'])) {
      //in case someone edits $this->_params & invalidates this test :-)
      unset($this->_params[0]['financial_type_id']);
    }
    $result = $this->callAPISuccess('event', 'create', $this->_params[0] + $contributionTypeArray);
    $getresult = $this->callAPISuccess('event', 'get', array() + $contributionTypeArray);
    $this->assertEquals($getresult['values'][$getresult['id']]['contribution_type_id'], 3);
    $this->assertEquals($result['id'], $getresult['id']);
    $this->callAPISuccess('event', 'delete', array('id' => $result['id']));
  }

  /**
   * Check with complete array + custom field.
   *
   * Note that the test is written on purpose without any
   * variables specific to participant so it can be replicated into other entities
   * and / or moved to the automated test suite.
   */
  public function testCreateWithCustom() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);

    $params = $this->_params[0];
    $params['custom_' . $ids['custom_field_id']] = "custom string";

    $result = $this->callAPIAndDocument($this->_entity, 'create', $params, __FUNCTION__, __FILE__);

    $check = $this->callAPISuccess($this->_entity, 'get', array(
        'return.custom_' . $ids['custom_field_id'] => 1,
        'id' => $result['id'],
      ));
    $this->assertEquals("custom string", $check['values'][$check['id']]['custom_' . $ids['custom_field_id']], ' in line ' . __LINE__);

    $this->customFieldDelete($ids['custom_field_id']);
    $this->customGroupDelete($ids['custom_group_id']);
    $this->callAPISuccess($this->_entity, 'Delete', array('id' => $result['id']));
  }

  /**
   * Check searching on custom fields.
   *
   * https://issues.civicrm.org/jira/browse/CRM-16036
   */
  public function testSearchCustomField() {
    // create custom group with custom field on event
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);

    // Search for events having CRM-16036 as the value for this custom
    // field. This should not return anything.
    $check = $this->callAPISuccess($this->_entity, 'get', array(
        'custom_' . $ids['custom_field_id'] => 'CRM-16036',
      ));

    $this->assertEquals(0, $check['count']);

    $this->customFieldDelete($ids['custom_field_id']);
    $this->customGroupDelete($ids['custom_group_id']);
  }

  /**
   * Test that an event with a price set can be created.
   */
  public function testCreatePaidEvent() {
    //@todo alter API so that an integer is converted to an array
    $priceSetParams = array('price_set_id' => (array) 1, 'is_monetary' => 1);
    $result = $this->callAPISuccess('Event', 'Create', array_merge($this->_params[0], $priceSetParams));
    $event = $this->callAPISuccess('Event', 'getsingle', array('id' => $result['id'], 'return' => 'price_set_id'));
    $this->assertArrayKeyExists('price_set_id', $event);
  }

  public function testCreateEventParamsNotArray() {
    $params = NULL;
    $result = $this->callAPIFailure('event', 'create', $params);
  }

  public function testCreateEventEmptyParams() {
    $params = array();
    $result = $this->callAPIFailure('event', 'create', $params);
  }

  public function testCreateEventParamsWithoutTitle() {
    unset($this->_params['title']);
    $result = $this->callAPIFailure('event', 'create', $this->_params);
    $this->assertAPIFailure($result);
  }

  public function testCreateEventParamsWithoutEventTypeId() {
    unset($this->_params['event_type_id']);
    $result = $this->callAPIFailure('event', 'create', $this->_params);
  }

  public function testCreateEventParamsWithoutStartDate() {
    unset($this->_params['start_date']);
    $result = $this->callAPIFailure('event', 'create', $this->_params);
  }

  public function testCreateEventSuccess() {
    $result = $this->callAPIAndDocument('Event', 'Create', $this->_params[0], __FUNCTION__, __FILE__);
    $this->assertArrayHasKey('id', $result['values'][$result['id']], 'In line ' . __LINE__);
    $result = $this->callAPISuccess($this->_entity, 'Get', array('id' => $result['id']));
    $this->callAPISuccess($this->_entity, 'Delete', array('id' => $result['id']));
    $this->assertEquals('2008-10-21 00:00:00', $result['values'][$result['id']]['start_date'], 'start date is not set in line ' . __LINE__);
    $this->assertEquals('2008-10-23 00:00:00', $result['values'][$result['id']]['end_date'], 'end date is not set in line ' . __LINE__);
    $this->assertEquals('2008-06-01 00:00:00', $result['values'][$result['id']]['registration_start_date'], 'start date is not set in line ' . __LINE__);
    $this->assertEquals('2008-10-15 00:00:00', $result['values'][$result['id']]['registration_end_date'], 'end date is not set in line ' . __LINE__);
  }

  /**
   * Test that passing in Unique field names works.
   */
  public function testCreateEventSuccessUniqueFieldNames() {
    $this->_params[0]['event_start_date'] = $this->_params[0]['start_date'];
    unset($this->_params[1]['start_date']);
    $this->_params[0]['event_title'] = $this->_params[0]['title'];
    unset($this->_params[0]['title']);
    $result = $this->callAPISuccess('Event', 'Create', $this->_params[0]);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertArrayHasKey('id', $result['values'][$result['id']], 'In line ' . __LINE__);
    $result = $this->callAPISuccess($this->_entity, 'Get', array('id' => $result['id']));
    $this->callAPISuccess($this->_entity, 'Delete', array('id' => $result['id']));

    $this->assertEquals('2008-10-21 00:00:00', $result['values'][$result['id']]['start_date'], 'start date is not set in line ' . __LINE__);
    $this->assertEquals('2008-10-23 00:00:00', $result['values'][$result['id']]['end_date'], 'end date is not set in line ' . __LINE__);
    $this->assertEquals('2008-06-01 00:00:00', $result['values'][$result['id']]['registration_start_date'], 'start date is not set in line ' . __LINE__);
    $this->assertEquals('2008-10-15 00:00:00', $result['values'][$result['id']]['registration_end_date'], 'end date is not set in line ' . __LINE__);
    $this->assertEquals($this->_params[0]['event_title'], $result['values'][$result['id']]['title'], 'end date is not set in line ' . __LINE__);
  }

  public function testUpdateEvent() {
    $result = $this->callAPISuccess('event', 'create', $this->_params[1]);

    $params = array(
      'id' => $result['id'],
      'max_participants' => 150,
    );
    $this->callAPISuccess('Event', 'Create', $params);
    $updated = $this->callAPISuccess('Event', 'Get', $params, __FUNCTION__, __FILE__);
    $this->assertEquals(150, $updated['values'][$result['id']]['max_participants']);
    $this->assertEquals('Annual CiviCRM meet 2', $updated['values'][$result['id']]['title']);
    $this->callAPISuccess($this->_entity, 'Delete', array('id' => $result['id']));
  }


  public function testDeleteEmptyParams() {
    $result = $this->callAPIFailure('Event', 'Delete', array());
  }

  public function testDelete() {
    $params = array(
      'id' => $this->_eventIds[0],
    );
    $result = $this->callAPIAndDocument('Event', 'Delete', $params, __FUNCTION__, __FILE__);
  }

  /**
   * Check event_id still supported for delete.
   */
  public function testDeleteWithEventId() {
    $params = array(
      'event_id' => $this->_eventIds[0],
    );
    $result = $this->callAPISuccess('Event', 'Delete', $params);
    $this->assertAPISuccess($result, 'in line ' . __LINE__);
  }

  /**
   * Trying to delete an event with participants should return error.
   */
  public function testDeleteWithExistingParticipant() {
    $contactID = $this->individualCreate();
    $participantID = $this->participantCreate(
      array(
        'contactID' => $contactID,
        'eventID' => $this->_eventIds[0],
      )
    );
    $result = $this->callAPISuccess('Event', 'Delete', array('id' => $this->_eventIds[0]));
  }

  public function testDeleteWithWrongEventId() {
    $params = array('event_id' => $this->_eventIds[0]);
    $result = $this->callAPISuccess('Event', 'Delete', $params);
    // try to delete again - there's no such event anymore
    $params = array('event_id' => $this->_eventIds[0]);
    $result = $this->callAPIFailure('Event', 'Delete', $params);
  }

  ///////////////// civicrm_event_search methods

  /**
   * Test civicrm_event_search with wrong params type.
   */
  public function testSearchWrongParamsType() {
    $params = 'a string';
    $result = $this->callAPIFailure('event', 'get', $params);
  }

  /**
   * Test civicrm_event_search with empty params.
   */
  public function testSearchEmptyParams() {
    $this->callAPISuccess('event', 'create', $this->_params[1]);

    $getParams = array(
      'sequential' => 1,
    );
    $result = $this->callAPISuccess('event', 'get', $getParams);
    $this->assertEquals($result['count'], 3);
    $res = $result['values'][0];
    $this->assertArrayKeyExists('title', $res);
    $this->assertEquals($res['event_type_id'], $this->_params[1]['event_type_id']);
  }

  /**
   * Test civicrm_event_search. Success expected.
   */
  public function testSearch() {
    $params = array(
      'event_type_id' => 1,
      'return.title' => 1,
      'return.id' => 1,
      'return.start_date' => 1,
    );
    $result = $this->callAPISuccess('event', 'get', $params);

    $this->assertEquals($result['values'][$this->_eventIds[0]]['id'], $this->_eventIds[0], 'In line ' . __LINE__);
    $this->assertEquals($result['values'][$this->_eventIds[0]]['title'], 'Annual CiviCRM meet', 'In line ' . __LINE__);
  }

  /**
   * Test civicrm_event_search.
   *
   * Success expected.
   *
   * return.offset and return.max_results test (CRM-5266)
   */
  public function testSearchWithOffsetAndMaxResults() {
    $maxEvents = 5;
    $events = array();
    while ($maxEvents > 0) {
      $params = array(
        'title' => 'Test Event' . $maxEvents,
        'event_type_id' => 2,
        'start_date' => 20081021,
      );

      $events[$maxEvents] = $this->callAPISuccess('event', 'create', $params);
      $maxEvents--;
    }
    $params = array(
      'event_type_id' => 2,
      'return.id' => 1,
      'return.title' => 1,
      'return.offset' => 2,
      'return.max_results' => 2,
    );
    $result = $this->callAPISuccess('event', 'get', $params);
    $this->assertAPISuccess($result);
    $this->assertEquals(2, $result['count'], ' 2 results returned In line ' . __LINE__);
  }

  public function testEventCreationPermissions() {
    $params = array(
      'event_type_id' => 1,
      'start_date' => '2010-10-03',
      'title' => 'le cake is a tie',
      'check_permissions' => TRUE,
    );
    $config = &CRM_Core_Config::singleton();
    $config->userPermissionClass->permissions = array('access CiviCRM');
    $result = $this->callAPIFailure('event', 'create', $params);
    $this->assertEquals('API permission check failed for Event/create call; insufficient permission: require access CiviCRM and access CiviEvent and edit all events', $result['error_message'], 'lacking permissions should not be enough to create an event');

    $config->userPermissionClass->permissions = array(
      'access CiviEvent',
      'edit all events',
      'access CiviCRM',
    );
    $result = $this->callAPISuccess('event', 'create', $params);
  }

  public function testgetfields() {
    $description = "Demonstrate use of getfields to interrogate api.";
    $params = array('action' => 'create');
    $result = $this->callAPISuccess('event', 'getfields', $params);
    $this->assertEquals(1, $result['values']['title']['api.required'], 'in line ' . __LINE__);
  }

  /**
   * Test api_action param also works.
   */
  public function testgetfieldsRest() {
    $description = "Demonstrate use of getfields to interrogate api.";
    $params = array('api_action' => 'create');
    $result = $this->callAPISuccess('event', 'getfields', $params);
    $this->assertEquals(1, $result['values']['title']['api.required'], 'in line ' . __LINE__);
  }

  public function testgetfieldsGet() {
    $description = "Demonstrate use of getfields to interrogate api.";
    $params = array('action' => 'get');
    $result = $this->callAPISuccess('event', 'getfields', $params);
    $this->assertEquals('title', $result['values']['event_title']['name'], 'in line ' . __LINE__);
  }

  public function testgetfieldsDelete() {
    $description = "Demonstrate use of getfields to interrogate api.";
    $params = array('action' => 'delete');
    $result = $this->callAPISuccess('event', 'getfields', $params);
    $this->assertEquals(1, $result['values']['id']['api.required']);
  }

}
