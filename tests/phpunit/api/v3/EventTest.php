<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
 * Class api_v3_EventTest
 * @group headless
 */
class api_v3_EventTest extends CiviUnitTestCase {
  protected $_params;
  protected $_apiversion;
  protected $_entity;

  public function setUp() {
    parent::setUp();
    $this->_apiversion = 3;
    $this->_entity = 'event';
    $this->_params = [
      [
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
      ],
      [
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
      ],
    ];

    $params = [
      [
        'title' => 'Annual CiviCRM meet',
        'event_type_id' => 1,
        'start_date' => 20081021,
      ],
      [
        'title' => 'Annual CiviCRM meet 2',
        'event_type_id' => 1,
        'start_date' => 20101021,
      ],
    ];

    $this->events = [];
    $this->eventIds = [];
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
    $tablesToTruncate = [
      'civicrm_participant',
      'civicrm_event',
    ];
    $this->quickCleanup($tablesToTruncate, TRUE);
  }

  /**
   * civicrm_event_get methods.
   */
  public function testGetEventById() {
    $params = [
      'id' => $this->_events[1]['id'],
    ];
    $result = $this->callAPISuccess('event', 'get', $params);
    $this->assertEquals($result['values'][$this->_eventIds[1]]['event_title'], 'Annual CiviCRM meet 2');
  }

  /**
   * Test getLocationEvents() function invokes selectWhereClause() hook
   */
  public function testGetEventWithPermissionHook() {
    $address = $this->callAPISuccess('address', 'create', [
      'contact_id' => 'null',
      'location_type_id' => 1,
      'street_address' => '1234567',
    ]);
    $params = [
      'address_id' => $address['id'],
    ];
    $result = $this->callAPISuccess('loc_block', 'create', $params);
    $params = [
      'id' => $this->_events[1]['id'],
      'loc_block_id' => $result['id'],
    ];
    $this->callAPISuccess('Event', 'create', $params);
    $result = CRM_Event_BAO_Event::getLocationEvents();
    $this->assertEquals(1, count($result));

    $this->hookClass->setHook('civicrm_selectWhereClause', [$this, 'selectWhereClauseHook']);
    $result = CRM_Event_BAO_Event::getLocationEvents();
    $this->assertEquals(0, count($result));
  }

  public function testGetEventByEventTitle() {

    $params = [
      'event_title' => 'Annual CiviCRM meet',
      'sequential' => TRUE,
    ];

    $result = $this->callAPIAndDocument('event', 'get', $params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
    $this->assertEquals($result['values'][0]['id'], $this->_eventIds[0]);
  }

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testGetEventByWrongTitle($version) {
    $this->_apiversion = $version;
    $params = [
      'title' => 'No event with that title',
    ];
    $result = $this->callAPISuccess('Event', 'Get', $params);
    $this->assertEquals(0, $result['count']);
  }

  /**
   * Skip api4 - this api uses deprecated query syntax
   */
  public function testGetEventByIdSort() {
    $params = [
      'return.sort' => 'id ASC',
      'return.max_results' => 1,
    ];
    $result = $this->callAPISuccess('Event', 'Get', $params);
    $this->assertEquals(1, $result['id'], ' in line ' . __LINE__);
    $params = [
      'options' => [
        'sort' => 'id DESC',
        'limit' => 1,
      ],
    ];

    $result = $this->callAPISuccess('Event', 'Get', $params);
    $this->assertAPISuccess($result, ' in line ' . __LINE__);
    $this->assertEquals(2, $result['id'], ' in line ' . __LINE__);
    $params = [
      'options' => [
        'sort' => 'id ASC',
        'limit' => 1,
      ],
    ];
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
   * FIXME: Api4
   */
  public function testGetIsCurrent() {
    $params = [
      'isCurrent' => 1,
    ];
    $currentEventParams = [
      'start_date' => date('Y-m-d', strtotime('+ 1 day')),
      'end_date' => date('Y-m-d', strtotime('+ 1 week')),
    ];
    $currentEventParams = array_merge($this->_params[1], $currentEventParams);
    $currentEvent = $this->callAPISuccess('Event', 'Create', $currentEventParams);
    $description = "Demonstrates use of is.Current option.";
    $subfile = "IsCurrentOption";
    $result = $this->callAPIAndDocument('Event', 'Get', $params, __FUNCTION__, __FILE__, $description, $subfile);
    $allEvents = $this->callAPISuccess('Event', 'Get', []);
    $this->callAPISuccess('Event', 'Delete', ['id' => $currentEvent['id']]);
    $this->assertEquals(1, $result['count'], 'confirm only one event found in line ' . __LINE__);
    $this->assertEquals(3, $allEvents['count'], 'confirm three events exist (ie. two not found) ' . __LINE__);
    $this->assertEquals($currentEvent['id'], $result['id'], '');
  }

  /**
   * There has been a schema change & the api needs to buffer developers from it
   * FIXME: Api4
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

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testInvalidData($version) {
    $this->_apiversion = $version;
    $params = $this->_params[0];
    $params['sequential'] = 1;
    $params['loc_block_id'] = 100;
    $result = $this->callAPIFailure('event', 'create', $params);

  }

  /**
   * Test 'is_full' option.
   * FIXME: Api4
   */
  public function testGetSingleReturnIsFull() {
    $contactID = $this->individualCreate();
    $params = [
      'id' => $this->_eventIds[0],
      'max_participants' => 1,
    ];
    $result = $this->callAPISuccess('Event', 'Create', $params);

    $getEventParams = [
      'id' => $this->_eventIds[0],
      'return.is_full' => 1,
    ];

    $currentEvent = $this->callAPISuccess('Event', 'getsingle', $getEventParams);
    $description = "Demonstrates use of return is_full .";
    $subfile = "IsFullOption";
    $this->assertEquals(0, $currentEvent['is_full'], ' is full is set in line ' . __LINE__);
    $this->assertEquals(1, $currentEvent['available_places'], 'available places is set in line ' . __LINE__);
    $participant = $this->callAPISuccess('Participant', 'create', [
      'participant_status' => 1,
      'role_id' => 1,
      'contact_id' => $contactID,
      'event_id' => $this->_eventIds[0],
    ]);
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
    $contributionTypeArray = ['contribution_type_id' => 3];
    if (isset($this->_params[0]['financial_type_id'])) {
      //in case someone edits $this->_params & invalidates this test :-)
      unset($this->_params[0]['financial_type_id']);
    }
    $result = $this->callAPISuccess('event', 'create', $this->_params[0] + $contributionTypeArray);
    $getresult = $this->callAPISuccess('event', 'get', [] + $contributionTypeArray);
    $this->assertEquals($getresult['values'][$getresult['id']]['contribution_type_id'], 3);
    $this->assertEquals($result['id'], $getresult['id']);
    $this->callAPISuccess('event', 'delete', ['id' => $result['id']]);
  }

  /**
   * Chaining get event and loc block.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testChainingGetLocBlock($version) {
    $this->_apiversion = $version;
    // create a loc block and an event for that loc block.
    $eventParams = $this->_params[0];
    $eventParams['loc_bloc_id'] = '$value.id';
    $locBlockParams = [
      'address' => [
        'street_address' => 'Kipdorp 24',
        'postal_code' => '2000',
        'city' => 'Antwerpen',
        'country_id' => '1020',
        'location_type_id' => '1',
      ],
      'api.Event.create' => $eventParams,
      'sequential' => 1,
    ];
    $createResult = $this->callAPISuccess('LocBlock', 'create', $locBlockParams);
    $locBlockId = $createResult['id'];
    $eventId = $createResult['values'][0]['api.Event.create']['id'];

    // request the event with its loc block:
    $check = $this->callAPISuccess($this->_entity, 'getsingle', [
      'id' => $eventId,
      'api.LocBlock.get' => ['id' => '$value.loc_block_id'],
      'sequential' => 1,
    ]);

    // assert
    $this->assertEquals($eventId, $check['id'], ' in line ' . __LINE__);
    $this->assertEquals(1, $check['api.LocBlock.get']['count'], ' in line ' . __LINE__);
    $this->assertEquals($locBlockId, $check['api.LocBlock.get']['id'], ' in line ' . __LINE__);

    // cleanup
    $this->callAPISuccess($this->_entity, 'delete', ['id' => $eventId]);
  }

  /**
   * Chaining get event and non existing loc block.
   *
   * Even if there is no loc block, at least the event should be returned.
   * http://forum.civicrm.org/index.php/topic,36113.0.html
   */
  public function testChainingGetNonExistingLocBlock() {
    $params = $this->_params[0];
    $result = $this->callAPISuccess($this->_entity, 'create', $params);

    $check = $this->callAPISuccess($this->_entity, 'get', [
      'id' => $result['id'],
      // this chaining request should not break things:
      'api.LocBlock.get' => ['id' => '$value.loc_block_id'],
    ]);
    $this->assertEquals($result['id'], $check['id']);

    $this->callAPISuccess($this->_entity, 'Delete', ['id' => $result['id']]);
  }

  /**
   * Check with complete array + custom field.
   *
   * Note that the test is written on purpose without any
   * variables specific to participant so it can be replicated into other entities
   * and / or moved to the automated test suite.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testCreateWithCustom($version) {
    $this->_apiversion = $version;
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);

    $params = $this->_params[0];
    $params['custom_' . $ids['custom_field_id']] = "custom string";

    $result = $this->callAPIAndDocument($this->_entity, 'create', $params, __FUNCTION__, __FILE__);

    $check = $this->callAPISuccess($this->_entity, 'get', [
      'return.custom_' . $ids['custom_field_id'] => 1,
      'id' => $result['id'],
    ]);
    $this->assertEquals("custom string", $check['values'][$check['id']]['custom_' . $ids['custom_field_id']], ' in line ' . __LINE__);

    $this->customFieldDelete($ids['custom_field_id']);
    $this->customGroupDelete($ids['custom_group_id']);
    $this->callAPISuccess($this->_entity, 'Delete', ['id' => $result['id']]);
  }

  /**
   * Check searching on custom fields.
   *
   * https://issues.civicrm.org/jira/browse/CRM-16036
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testSearchCustomField($version) {
    $this->_apiversion = $version;
    // create custom group with custom field on event
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);

    // Search for events having CRM-16036 as the value for this custom
    // field. This should not return anything.
    $check = $this->callAPISuccess($this->_entity, 'get', [
      'custom_' . $ids['custom_field_id'] => 'CRM-16036',
    ]);

    $this->assertEquals(0, $check['count']);

    $this->customFieldDelete($ids['custom_field_id']);
    $this->customGroupDelete($ids['custom_group_id']);
  }

  /**
   * Check searching on custom fields with IS NULL.
   *
   * https://issues.civicrm.org/jira/browse/CRM-20740
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testSearchCustomFieldIsNull($version) {
    $this->_apiversion = $version;
    // create custom group with custom field on event
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);

    // Search for events having NULL as the value for this custom
    // field. This should return all events created in setUp.
    $check = $this->callAPISuccess($this->_entity, 'get', [
      'custom_' . $ids['custom_field_id'] => ['IS NULL' => 1],
    ]);

    $this->assertGreaterThan(0, $check['count']);

    $this->customFieldDelete($ids['custom_field_id']);
    $this->customGroupDelete($ids['custom_group_id']);
  }

  /**
   * Test searching on custom fields returning a contact reference.
   *
   * https://issues.civicrm.org/jira/browse/CRM-16036
   * FIXME: Api4
   */
  public function testEventGetCustomContactRefFieldCRM16036() {
    // Create some contact.
    $test_contact_name = 'Contact, Test';
    $contact_save_result = $this->callAPISuccess('contact', 'create', [
      'sort_name' => $test_contact_name,
      'contact_type' => 'Individual',
      'display_name' => $test_contact_name,
    ]);
    $contact_id = $contact_save_result['id'];

    // I have no clue what this $subfile is about. I just copied it from another
    // unit test.
    $subfile = 'ContactRefCustomField';
    $description = "Demonstrates get with Contact Reference Custom Field.";

    // Create a custom group, and add a custom contact reference field.
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);
    $params = [
      'custom_group_id' => $ids['custom_group_id'],
      'name' => 'Worker_Lookup',
      'label' => 'Worker Lookup',
      'html_type' => 'Autocomplete-Select',
      'data_type' => 'ContactReference',
      'weight' => 4,
      'is_searchable' => 1,
      'is_active' => 1,
    ];
    $customField = $this->callAPISuccess('custom_field', 'create', $params);

    // Create an event, and add the contact as custom value.
    $params = $this->_params;
    $params['title'] = "My test event.";
    $params['start_date'] = "2015-03-14";
    // Just assume that an event type 1 exists.
    $params['event_type_id'] = 1;
    $params['custom_' . $customField['id']] = "$contact_id";

    $this->callAPIAndDocument($this->_entity, 'create', $params, __FUNCTION__, __FILE__, $description, $subfile);

    // Retrieve the activity, search for the contact.
    $result = $this->callAPIAndDocument($this->_entity, 'get', [
      'return.custom_' . $customField['id'] => 1,
      'custom_' . $customField['id'] => $contact_id,
    ], __FUNCTION__, __FILE__, $description, $subfile);

    $this->assertEquals($test_contact_name, $result['values'][$result['id']]['custom_' . $customField['id']]);
    $this->assertEquals($contact_id, $result['values'][$result['id']]['custom_' . $customField['id'] . "_id"], ' in line ' . __LINE__);
    // Not sure whether I should test for custom_X_1 and custom_X_1_id as well.
    // (1 being the id of the record in the custom value table)

    $this->customFieldDelete($ids['custom_field_id']);
    $this->customGroupDelete($ids['custom_group_id']);
    $this->callAPISuccess('contact', 'delete', [
      'id' => $contact_id,
      'skip_undelete' => TRUE,
    ]);
  }

  /**
   * Test searching on custom fields with less than or equal.
   *
   * See CRM-17101.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testEventGetCustomFieldLte($version) {
    $this->_apiversion = $version;
    // create custom group with custom field on event
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);

    // Create an event, with a custom value.
    $params = $this->_params;
    $params['title'] = "My test event.";
    $params['start_date'] = "2015-03-14";
    // Just assume that an event type 1 exists.
    $params['event_type_id'] = 1;
    $params['custom_' . $ids['custom_field_id']] = "AAAA";

    $save_result = $this->callApiSuccess($this->_entity, 'create', $params);

    // Retrieve the activity, search for custom field < 'BBBB'
    $get_result = $this->callAPISuccess($this->_entity, 'get', [
      'return.custom_' . $ids['custom_field_id'] => 1,
      'custom_' . $ids['custom_field_id'] => ['<=' => 'BBBB'],
    ]);

    // Expect that we find the saved event.
    $this->assertArrayKeyExists($save_result['id'], $get_result['values']);

    $this->callAPISuccess($this->_entity, 'Delete', ['id' => $save_result['id']]);
  }

  /**
   * Test searching on custom fields with netsted call with id param.
   *
   * Search for an event on a custom field, and perform a chained call
   * to retrieve it's (non-existing) loc block, using $value-substitution.
   * This test just checks whether the event is found, because something
   * happened in CiviCRM 4.6.5 that broke my fix for CRM-16036, causing
   * CiviCRM to return 0 results.
   * Of course, CRM-16168 should also be fixed for this test to pass.
   */
  public function testEventSearchCustomFieldWithChainedCall() {
    // Create a custom group, and add a custom contact reference field.
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);
    $custom_field_id = $ids['custom_field_id'];

    // Create an event with a custom value.
    $params = $this->_params;
    $params['title'] = "My test event.";
    $params['start_date'] = "2015-03-14";
    // Just assume that an event type 1 exists.
    $params['event_type_id'] = 1;
    $params['custom_' . $custom_field_id] = "12345";

    $this->callAPISuccess($this->_entity, 'create', $params, __FUNCTION__, __FILE__);

    // Retrieve the activity, and chain loc block using $value.
    $result = $this->callAPISuccess($this->_entity, 'get', [
      'custom_' . $custom_field_id => "12345",
      'api.LocBlock.get' => ["id" => '$value.loc_block_id'],
    ]);

    $this->assertEquals(1, $result['count']);

    $this->customFieldDelete($ids['custom_field_id']);
    $this->customGroupDelete($ids['custom_group_id']);
    $this->callAPISuccess('event', 'delete', [
      'id' => $result['id'],
      'skip_undelete' => TRUE,
    ]);
  }

  /**
   * Test that an event with a price set can be created.
   * FIXME: Api4
   */
  public function testCreatePaidEvent() {
    //@todo alter API so that an integer is converted to an array
    $priceSetParams = ['price_set_id' => (array) 1, 'is_monetary' => 1];
    $result = $this->callAPISuccess('Event', 'Create', array_merge($this->_params[0], $priceSetParams));
    $event = $this->callAPISuccess('Event', 'getsingle', ['id' => $result['id'], 'return' => 'price_set_id']);
    $this->assertArrayKeyExists('price_set_id', $event);
  }

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testCreateEventEmptyParams($version) {
    $this->_apiversion = $version;
    $params = [];
    $result = $this->callAPIFailure('event', 'create', $params);
  }

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testCreateEventParamsWithoutTitle($version) {
    $this->_apiversion = $version;
    unset($this->_params['title']);
    $result = $this->callAPIFailure('event', 'create', $this->_params);
    $this->assertAPIFailure($result);
  }

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testCreateEventParamsWithoutEventTypeId($version) {
    $this->_apiversion = $version;
    unset($this->_params['event_type_id']);
    $result = $this->callAPIFailure('event', 'create', $this->_params);
  }

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testCreateEventParamsWithoutStartDate($version) {
    $this->_apiversion = $version;
    unset($this->_params['start_date']);
    $result = $this->callAPIFailure('event', 'create', $this->_params, 'start_date');
  }

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testCreateEventSuccess($version) {
    $this->_apiversion = $version;
    $result = $this->callAPIAndDocument('Event', 'Create', $this->_params[0], __FUNCTION__, __FILE__);
    $this->assertArrayHasKey('id', $result['values'][$result['id']]);
    $result = $this->callAPISuccess($this->_entity, 'Get', ['id' => $result['id']]);
    $this->callAPISuccess($this->_entity, 'Delete', ['id' => $result['id']]);
    $this->assertEquals('2008-10-21 00:00:00', $result['values'][$result['id']]['start_date'], 'start date is not set');
    $this->assertEquals('2008-10-23 00:00:00', $result['values'][$result['id']]['end_date'], 'end date is not set');
    $this->assertEquals('2008-06-01 00:00:00', $result['values'][$result['id']]['registration_start_date'], 'start date is not set');
    $this->assertEquals('2008-10-15 00:00:00', $result['values'][$result['id']]['registration_end_date'], 'end date is not set');
  }

  /**
   * Test that passing in Unique field names works.
   * Skip api4 which doesn't use unique names
   */
  public function testCreateEventSuccessUniqueFieldNames() {
    $this->_params[0]['event_start_date'] = $this->_params[0]['start_date'];
    unset($this->_params[1]['start_date']);
    $this->_params[0]['event_title'] = $this->_params[0]['title'];
    unset($this->_params[0]['title']);
    $result = $this->callAPISuccess('Event', 'Create', $this->_params[0]);
    $this->assertAPISuccess($result);
    $this->assertArrayHasKey('id', $result['values'][$result['id']]);
    $result = $this->callAPISuccess($this->_entity, 'Get', ['id' => $result['id']]);
    $this->callAPISuccess($this->_entity, 'Delete', ['id' => $result['id']]);

    $this->assertEquals('2008-10-21 00:00:00', $result['values'][$result['id']]['start_date'], 'start date is not set in line ' . __LINE__);
    $this->assertEquals('2008-10-23 00:00:00', $result['values'][$result['id']]['end_date'], 'end date is not set in line ' . __LINE__);
    $this->assertEquals('2008-06-01 00:00:00', $result['values'][$result['id']]['registration_start_date'], 'start date is not set in line ' . __LINE__);
    $this->assertEquals('2008-10-15 00:00:00', $result['values'][$result['id']]['registration_end_date'], 'end date is not set in line ' . __LINE__);
    $this->assertEquals($this->_params[0]['event_title'], $result['values'][$result['id']]['title'], 'end date is not set in line ' . __LINE__);
  }

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testUpdateEvent($version) {
    $this->_apiversion = $version;
    $result = $this->callAPISuccess('event', 'create', $this->_params[1]);

    $params = [
      'id' => $result['id'],
      'max_participants' => 150,
    ];
    $this->callAPISuccess('Event', 'Create', $params);
    $updated = $this->callAPISuccess('Event', 'Get', $params, __FUNCTION__, __FILE__);
    $this->assertEquals(150, $updated['values'][$result['id']]['max_participants']);
    $this->assertEquals('Annual CiviCRM meet 2', $updated['values'][$result['id']]['title']);
    $this->callAPISuccess($this->_entity, 'Delete', ['id' => $result['id']]);
  }

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testDeleteEmptyParams($version) {
    $this->_apiversion = $version;
    $result = $this->callAPIFailure('Event', 'Delete', []);
  }

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testDelete($version) {
    $this->_apiversion = $version;
    $params = [
      'id' => $this->_eventIds[0],
    ];
    $this->callAPIAndDocument('Event', 'Delete', $params, __FUNCTION__, __FILE__);
  }

  /**
   * Check event_id still supported for delete.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testDeleteWithEventId($version) {
    $this->_apiversion = $version;
    $params = [
      'event_id' => $this->_eventIds[0],
    ];
    $result = $this->callAPISuccess('Event', 'Delete', $params);
    $this->assertAPISuccess($result);
  }

  /**
   * Trying to delete an event with participants should return error.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testDeleteWithExistingParticipant($version) {
    $this->_apiversion = $version;
    $contactID = $this->individualCreate();
    $this->participantCreate(
      [
        'contactID' => $contactID,
        'eventID' => $this->_eventIds[0],
      ]
    );
    $this->callAPISuccess('Event', 'Delete', ['id' => $this->_eventIds[0]]);
  }

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testDeleteWithWrongEventId($version) {
    $this->_apiversion = $version;
    $params = ['event_id' => $this->_eventIds[0]];
    $result = $this->callAPISuccess('Event', 'Delete', $params);
    // try to delete again - there's no such event anymore
    $params = ['event_id' => $this->_eventIds[0]];
    $result = $this->callAPIFailure('Event', 'Delete', $params);
  }

  /**
   * Test civicrm_event_search with wrong params type.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testSearchWrongParamsType($version) {
    $this->_apiversion = $version;
    $params = 'a string';
    $result = $this->callAPIFailure('event', 'get', $params);
  }

  /**
   * Test civicrm_event_search with empty params.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testSearchEmptyParams($version) {
    $this->_apiversion = $version;
    $this->callAPISuccess('event', 'create', $this->_params[1]);

    $getParams = [
      'sequential' => 1,
    ];
    $result = $this->callAPISuccess('event', 'get', $getParams);
    $this->assertEquals($result['count'], 3);
    $res = $result['values'][0];
    $this->assertArrayKeyExists('title', $res);
    $this->assertEquals($res['event_type_id'], $this->_params[1]['event_type_id']);
  }

  /**
   * Test civicrm_event_search. Success expected.
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testSearch($version) {
    $this->_apiversion = $version;
    $params = [
      'event_type_id' => 1,
      'return.title' => 1,
      'return.id' => 1,
      'return.start_date' => 1,
    ];
    $result = $this->callAPISuccess('event', 'get', $params);

    $this->assertEquals($result['values'][$this->_eventIds[0]]['id'], $this->_eventIds[0]);
    $this->assertEquals($result['values'][$this->_eventIds[0]]['title'], 'Annual CiviCRM meet');
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
    $events = [];
    while ($maxEvents > 0) {
      $params = [
        'title' => 'Test Event' . $maxEvents,
        'event_type_id' => 2,
        'start_date' => 20081021,
      ];

      $events[$maxEvents] = $this->callAPISuccess('event', 'create', $params);
      $maxEvents--;
    }
    $params = [
      'event_type_id' => 2,
      'return.id' => 1,
      'return.title' => 1,
      'return.offset' => 2,
      'return.max_results' => 2,
    ];
    $result = $this->callAPISuccess('event', 'get', $params);
    $this->assertAPISuccess($result);
    $this->assertEquals(2, $result['count'], ' 2 results returned In line ' . __LINE__);
  }

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testEventCreationPermissions($version) {
    $this->_apiversion = $version;
    $params = [
      'event_type_id' => 1,
      'start_date' => '2010-10-03',
      'title' => 'le cake is a tie',
      'check_permissions' => TRUE,
    ];
    $config = CRM_Core_Config::singleton();
    $config->userPermissionClass->permissions = ['access CiviCRM'];
    $result = $this->callAPIFailure('event', 'create', $params, 'failed');

    $config->userPermissionClass->permissions = [
      'access CiviEvent',
      'edit all events',
      'access CiviCRM',
    ];
    $result = $this->callAPISuccess('event', 'create', $params);
  }

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testgetfields($version) {
    $this->_apiversion = $version;
    $description = "Demonstrate use of getfields to interrogate api.";
    $params = ['action' => 'create'];
    $result = $this->callAPISuccess('event', 'getfields', $params);
    $this->assertEquals('is_active', $result['values']['is_active']['name']);
  }

  /**
   * Test api_action param also works.
   */
  public function testgetfieldsRest() {
    $description = "Demonstrate use of getfields to interrogate api.";
    $params = ['api_action' => 'create'];
    $result = $this->callAPISuccess('event', 'getfields', $params);
    $this->assertEquals(1, $result['values']['is_active']['api.default']);
  }

  /**
   * Skip api4 - output is different
   */
  public function testgetfieldsGet() {
    $description = "Demonstrate use of getfields to interrogate api.";
    $params = ['action' => 'get'];
    $result = $this->callAPISuccess('event', 'getfields', $params);
    $this->assertEquals('title', $result['values']['event_title']['name']);
  }

  /**
   * Skip api4 - output is different
   */
  public function testgetfieldsDelete() {
    $description = "Demonstrate use of getfields to interrogate api.";
    $params = ['action' => 'delete'];
    $result = $this->callAPISuccess('event', 'getfields', $params);
    $this->assertEquals(1, $result['values']['id']['api.required']);
  }

  /**
   * @param int $version
   * @dataProvider versionThreeAndFour
   */
  public function testCreateFromTemplate($version) {
    $this->_apiversion = $version;
    $templateParams = [
      'summary' => 'Sign up now to learn the results of this unit test',
      'description' => 'This event is created from a template, so all the values should be the same as the original ones.',
      'event_type_id' => 1,
      'is_public' => 1,
      'end_date' => '2018-06-25 17:00:00',
      'is_online_registration' => 1,
      'registration_start_date' => '2017-06-25 17:00:00',
      'registration_end_date' => '2018-06-25 17:00:00',
      'max_participants' => 100,
      'event_full_text' => 'Sorry! We are already full',
    ];
    $templateResult = $this->callAPISuccess('Event', 'create', ['is_template' => 1, 'template_title' => 'Test tpl'] + $templateParams);
    $eventResult = $this->callAPISuccess('Event', 'create', [
      'template_id' => $templateResult['id'],
      'title' => 'Clone1',
      'start_date' => '2018-06-25 16:00:00',
    ]);
    $eventResult = $this->callAPISuccess('Event', 'getsingle', ['id' => $eventResult['id']]);
    foreach ($templateParams as $param => $value) {
      $this->assertEquals($value, $eventResult[$param], print_r($eventResult, 1));
    }
  }

}
