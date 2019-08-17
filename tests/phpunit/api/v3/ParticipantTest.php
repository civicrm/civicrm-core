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
 * Test class for Batch API - civicrm_participant_*
 *
 * @package CiviCRM_APIv3
 */

/**
 * Class api_v3_ParticipantTest
 * @group headless
 */
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

  public function setUp() {
    $this->_apiversion = 3;
    parent::setUp();
    $this->_entity = 'participant';
    $event = $this->eventCreate(NULL);
    $this->_eventID = $event['id'];

    $this->_contactID = $this->individualCreate();

    $this->_createdParticipants = [];
    $this->_individualId = $this->individualCreate();

    $this->_participantID = $this->participantCreate([
      'contact_id' => $this->_contactID,
      'event_id' => $this->_eventID,
    ]);
    $this->_contactID2 = $this->individualCreate();
    $this->_participantID2 = $this->participantCreate([
      'contact_id' => $this->_contactID2,
      'event_id' => $this->_eventID,
      'registered_by_id' => $this->_participantID,
    ]);
    $this->_participantID3 = $this->participantCreate([
      'contact_id' => $this->_contactID2,
      'event_id' => $this->_eventID,
    ]);
    $this->_params = [
      'contact_id' => $this->_contactID,
      'event_id' => $this->_eventID,
      'status_id' => 1,
      'role_id' => 1,
      // to ensure it matches later on
      'register_date' => '2007-07-21 00:00:00',
      'source' => 'Online Event Registration: API Testing',
    ];
  }

  public function tearDown() {
    $this->eventDelete($this->_eventID);
    $tablesToTruncate = [
      'civicrm_custom_group',
      'civicrm_custom_field',
      'civicrm_contact',
      'civicrm_participant',
    ];
    // true tells quickCleanup to drop any tables that might have been created in the test
    $this->quickCleanup($tablesToTruncate, TRUE);
  }

  /**
   * Check that getCount can count past 25.
   */
  public function testGetCountLimit() {
    $contactIDs = [];

    for ($count = $this->callAPISuccessGetCount('Participant', []); $count < 27; $count++) {
      $contactIDs[] = $contactID = $this->individualCreate();
      $this->participantCreate(['contact_id' => $contactID, 'event_id' => $this->_eventID]);
    }
    $this->callAPISuccessGetCount('Participant', [], 27);

    foreach ($contactIDs as $contactID) {
      $this->callAPISuccess('Contact', 'delete', ['id' => $contactID]);
    }
  }

  /**
   * Test get participants with role_id.
   */
  public function testGetParticipantWithRole() {
    $roleId = [1, 2, 3];
    foreach ($roleId as $role) {
      $this->participantCreate([
        'contact_id' => $this->individualCreate(),
        'role_id' => $role,
        'event_id' => $this->_eventID,
      ]);
    }

    $params = [
      'role_id' => 2,
    ];
    $result = $this->callAPISuccess('participant', 'get', $params);
    //Assert all the returned participants has a role_id of 2
    foreach ($result['values'] as $pid => $values) {
      $this->assertEquals($values['participant_role_id'], 2);
    }

    $this->participantCreate([
      'id' => $this->_participantID,
      'role_id' => NULL,
      'event_id' => $this->_eventID,
    ]);

    $params['role_id'] = [
      'IS NULL' => 1,
    ];
    $result = $this->callAPISuccess('participant', 'get', $params);
    foreach ($result['values'] as $pid => $values) {
      $this->assertEquals($values['participant_role_id'], NULL);
    }

  }

  /**
   * Check with complete array + custom field
   * Note that the test is written on purpose without any
   * variables specific to participant so it can be replicated into other entities
   * and / or moved to the automated test suite
   */
  public function testCreateWithCustom() {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);

    $params = $this->_params;
    $params['custom_' . $ids['custom_field_id']] = "custom string";

    $result = $this->callAPIAndDocument($this->_entity, 'create', $params, __FUNCTION__, __FILE__);

    $this->assertEquals($result['id'], $result['values'][$result['id']]['id']);

    $check = $this->callAPISuccess($this->_entity, 'get', ['id' => $result['id']]);
    $this->assertEquals("custom string", $check['values'][$check['id']]['custom_' . $ids['custom_field_id']], ' in line ' . __LINE__);

    $this->customFieldDelete($ids['custom_field_id']);
    $this->customGroupDelete($ids['custom_group_id']);
  }

  /**
   * Check with participant_id.
   */
  public function testGetParticipantIdOnly() {
    $params = [
      'participant_id' => $this->_participantID,
      'return' => [
        'participant_id',
        'event_id',
        'participant_register_date',
        'participant_source',
      ],
    ];
    $result = $this->callAPISuccess('participant', 'get', $params);
    $this->assertAPISuccess($result, " in line " . __LINE__);
    $this->assertEquals($result['values'][$this->_participantID]['event_id'], $this->_eventID);
    $this->assertEquals($result['values'][$this->_participantID]['participant_register_date'], '2007-02-19 00:00:00');
    $this->assertEquals($result['values'][$this->_participantID]['participant_source'], 'Wimbeldon');
    $params = [
      'id' => $this->_participantID,
      'return' => 'id,participant_register_date,event_id',

    ];
    $result = $this->callAPISuccess('participant', 'get', $params);
    $this->assertEquals($result['values'][$this->_participantID]['event_id'], $this->_eventID);
    $this->assertEquals($result['values'][$this->_participantID]['participant_register_date'], '2007-02-19 00:00:00');

  }

  /**
   * Test permission for participant get.
   */
  public function testGetParticipantWithPermission() {
    $config = CRM_Core_Config::singleton();
    $config->userPermissionClass->permissions = [];
    $params = [
      'event_id' => $this->_eventID,
      'check_permissions' => TRUE,
      'return' => [
        'participant_id',
        'event_id',
        'participant_register_date',
        'participant_source',
      ],
    ];
    $this->callAPIFailure('participant', 'get', $params);

    $params['check_permissions'] = FALSE;
    $result = $this->callAPISuccess('participant', 'get', $params);
    $this->assertEquals($result['is_error'], 0);
  }

  /**
   * Check with params id.
   */
  public function testGetParamsAsIdOnly() {
    $params = [
      'id' => $this->_participantID,
    ];
    $result = $this->callAPIAndDocument('participant', 'get', $params, __FUNCTION__, __FILE__);
    $this->assertEquals($result['values'][$this->_participantID]['event_id'], $this->_eventID);
    $this->assertEquals($result['values'][$this->_participantID]['participant_register_date'], '2007-02-19 00:00:00');
    $this->assertEquals($result['values'][$this->_participantID]['participant_source'], 'Wimbeldon');
    $this->assertEquals($result['id'], $result['values'][$this->_participantID]['id']);
  }

  /**
   * Check with params id.
   */
  public function testGetNestedEventGet() {
    //create a second event & add participant to it.
    $event = $this->eventCreate(NULL);
    $this->callAPISuccess('participant', 'create', [
      'event_id' => $event['id'],
      'contact_id' => $this->_contactID,
    ]);

    $description = "Demonstrates use of nested get to fetch event data with participant records.";
    $subfile = "NestedEventGet";
    $params = [
      'id' => $this->_participantID,
      'api.event.get' => 1,
    ];
    $result = $this->callAPIAndDocument('participant', 'get', $params, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertEquals($result['values'][$this->_participantID]['event_id'], $this->_eventID);
    $this->assertEquals($result['values'][$this->_participantID]['participant_register_date'], '2007-02-19 00:00:00');
    $this->assertEquals($result['values'][$this->_participantID]['participant_source'], 'Wimbeldon');
    $this->assertEquals($this->_eventID, $result['values'][$this->_participantID]['api.event.get']['id']);
  }

  /**
   * Check Participant Get respects return properties.
   */
  public function testGetWithReturnProperties() {
    $params = [
      'contact_id' => $this->_contactID,
      'return.status_id' => 1,
      'return.participant_status_id' => 1,
      'options' => ['limit' => 1],
    ];
    $result = $this->callAPISuccess('participant', 'get', $params);
    $this->assertArrayHasKey('participant_status_id', $result['values'][$result['id']]);
  }

  /**
   * Check with contact_id.
   */
  public function testGetContactIdOnly() {
    $params = [
      'contact_id' => $this->_contactID,
    ];
    $participant = $this->callAPISuccess('participant', 'get', $params);

    $this->assertEquals($this->_participantID, $participant['id']);
    $this->assertEquals($this->_eventID, $participant['values'][$participant['id']]['event_id']);
    $this->assertEquals('2007-02-19 00:00:00', $participant['values'][$participant['id']]['participant_register_date']);
    $this->assertEquals('Wimbeldon', $participant['values'][$participant['id']]['participant_source']);
    $this->assertEquals($participant['id'], $participant['values'][$participant['id']]['id']);
  }

  /**
   * Check with event_id.
   * fetch first record
   */
  public function testGetMultiMatchReturnFirst() {
    $params = [
      'event_id' => $this->_eventID,
      'rowCount' => 1,
    ];

    $participant = $this->callAPISuccess('participant', 'get', $params);
    $this->assertNotNull($participant['id']);
  }

  /**
   * Check with event_id.
   * in v3 this should return all participants
   */
  public function testGetMultiMatchNoReturnFirst() {
    $params = [
      'event_id' => $this->_eventID,
    ];
    $participant = $this->callAPISuccess('participant', 'get', $params);
    $this->assertNotNull($participant['count'], 3);
  }

  ///////////////// civicrm_participant_get methods

  /**
   * Test civicrm_participant_get with empty params.
   * In this case all the participant records are returned.
   */
  public function testSearchEmptyParams() {
    $result = $this->callAPISuccess('participant', 'get', []);
    // expecting 3 participant records
    $this->assertEquals($result['count'], 3);
  }

  /**
   * Check with participant_id.
   */
  public function testSearchParticipantIdOnly() {
    $params = [
      'participant_id' => $this->_participantID,
    ];
    $participant = $this->callAPISuccess('participant', 'get', $params);
    $this->assertEquals($participant['values'][$this->_participantID]['event_id'], $this->_eventID);
    $this->assertEquals($participant['values'][$this->_participantID]['participant_register_date'], '2007-02-19 00:00:00');
    $this->assertEquals($participant['values'][$this->_participantID]['participant_source'], 'Wimbeldon');
  }

  /**
   * Check with contact_id.
   */
  public function testSearchContactIdOnly() {
    // Should get 2 participant records for this contact.
    $params = [
      'contact_id' => $this->_contactID2,
    ];
    $participant = $this->callAPISuccess('participant', 'get', $params);

    $this->assertEquals($participant['count'], 2);
  }

  /**
   * Check with event_id.
   */
  public function testSearchByEvent() {
    // Should get >= 3 participant records for this event. Also testing that last_name and event_title are returned.
    $params = [
      'event_id' => $this->_eventID,
      'return.last_name' => 1,
      'return.event_title' => 1,
    ];
    $participant = $this->callAPISuccess('participant', 'get', $params);
    if ($participant['count'] < 3) {
      $this->fail("Event search returned less than expected miniumum of 3 records.");
    }

    $this->assertEquals($participant['values'][$this->_participantID]['last_name'], 'Anderson');
    $this->assertEquals($participant['values'][$this->_participantID]['event_title'], 'Annual CiviCRM meet');
  }

  /**
   * Check with event_id.
   * fetch with limit
   */
  public function testSearchByEventWithLimit() {
    // Should 2 participant records since we're passing rowCount = 2.
    $params = [
      'event_id' => $this->_eventID,
      'rowCount' => 2,
    ];
    $participant = $this->callAPISuccess('participant', 'get', $params);

    $this->assertEquals($participant['count'], 2);
  }

  /**
   * Test search by lead booker (registered by ID)
   */
  public function testSearchByRegisteredById() {
    $params = [
      'registered_by_id' => $this->_participantID,
    ];
    $participant = $this->callAPISuccess('participant', 'get', $params);

    $this->assertEquals($participant['count'], 1);
    $this->assertEquals($participant['id'], $this->_participantID2);
  }

  ///////////////// civicrm_participant_create methods

  /**
   * Test civicrm_participant_create with empty params.
   */
  public function testCreateEmptyParams() {
    $params = [];
    $result = $this->callAPIFailure('participant', 'create', $params);
  }

  /**
   * Check with event_id.
   */
  public function testCreateMissingContactID() {
    $this->callAPIFailure('participant', 'create', ['event_id' => $this->_eventID]);
  }

  /**
   * Check with contact_id.
   * without event_id
   */
  public function testCreateMissingEventID() {
    $this->callAPIFailure('participant', 'create', ['contact_id' => $this->_contactID]);
  }

  /**
   * Check with contact_id & event_id
   */
  public function testCreateEventIdOnly() {
    $params = [
      'contact_id' => $this->_contactID,
      'event_id' => $this->_eventID,
    ];
    $participant = $this->callAPISuccess('participant', 'create', $params);
    $this->getAndCheck($params, $participant['id'], 'participant');
  }

  /**
   * Check with complete array.
   */
  public function testCreateAllParams() {
    $participant = $this->callAPISuccess('participant', 'create', $this->_params);
    $this->_participantID = $participant['id'];
    $this->assertDBState('CRM_Event_DAO_Participant', $participant['id'], $this->_params);
  }

  /**
   * Test that an overlong source is handled.
   */
  public function testLongSource() {
    $params = array_merge($this->_params, [
      'source' => 'a string that is even longer than the 128 character limit that is allowed for this field because sometimes you want, you know, an essay',
    ]);
    $baoCreated = CRM_Event_BAO_Participant::create($params);
    $this->assertEquals('a string that is even longer than the 128 character limit that is allowed for this field because sometimes you want, you know...', $baoCreated->source);
    // @todo - currently the api will still reject the long string.
    //$this->callAPISuccess('participant', 'create', $params);
  }

  /**
   * Test to check if receive date is being changed per CRM-9763
   */
  public function testCreateUpdateReceiveDate() {
    $participant = $this->callAPISuccess('participant', 'create', $this->_params);
    $update = [
      'id' => $participant['id'],
      'status_id' => 2,
    ];
    $this->callAPISuccess('participant', 'create', $update);
    $this->getAndCheck(array_merge($this->_params, $update), $participant['id'], 'participant');
  }

  /**
   * Test to check if participant fee level is being changed per CRM-9781
   */
  public function testCreateUpdateParticipantFeeLevel() {
    $myParams = $this->_params + ['participant_fee_level' => CRM_Core_DAO::VALUE_SEPARATOR . "fee" . CRM_Core_DAO::VALUE_SEPARATOR];
    $participant = $this->callAPISuccess('participant', 'create', $myParams);
    $update = [
      'id' => $participant['id'],
      'status_id' => 2,
    ];
    $update = $this->callAPISuccess('participant', 'create', $update);

    $this->assertEquals($participant['values'][$participant['id']]['fee_level'],
      $update['values'][$participant['id']]['fee_level']
    );

    $this->callAPISuccess('participant', 'delete', ['id' => $participant['id']]);
  }

  /**
   * Test the line items for participant fee with multiple price field values.
   */
  public function testCreateParticipantLineItems() {
    // Create a price set for this event.

    $priceset = $this->callAPISuccess('PriceSet', 'create', [
      'name' => 'my_price_set',
      'title' => 'My Price Set',
      'is_active' => 1,
      'extends' => 1,
      'financial_type_id' => 4,
      // 'entity' => array('civicrm_event' => array($this->_eventID)),
    ]);

    // Add the price set to the event with another API call.
    // I tried to do this at once, but it did not work.

    $priceset = $this->callAPISuccess('PriceSet', 'create', [
      'entity_table' => 'civicrm_event',
      'entity_id' => $this->_eventID,
      'id' => $priceset['id'],
    ]);

    $pricefield = $this->callAPISuccess('PriceField', 'create', [
      'price_set_id' => $priceset['id'],
      'name' => 'mypricefield',
      'label' => 'My Price Field',
      'html_type' => 'Text',
      'is_enter_qty' => 1,
      'is_display_amounts' => 1,
      'is_active' => 1,
    ]);

    $pfv1 = $this->callAPISuccess('PriceFieldValue', 'create', [
      'price_field_id' => $pricefield['id'],
      'name' => 'pricefieldvalue1',
      'label' => 'pricefieldvalue1',
      'amount' => 20,
      'is_active' => 1,
      'financial_type_id' => 4,
    ]);

    $pfv2 = $this->callAPISuccess('PriceFieldValue', 'create', [
      'price_field_id' => $pricefield['id'],
      'name' => 'pricefieldvalue2',
      'label' => 'pricefieldvalue2',
      'amount' => 5,
      'is_active' => 1,
      'financial_type_id' => 4,
    ]);

    // pay 2 times price field value 1, and 2 times price field value 2.
    $myParams = $this->_params + ['participant_fee_level' => CRM_Core_DAO::VALUE_SEPARATOR . "pricefieldvalue1 - 2" . CRM_Core_DAO::VALUE_SEPARATOR . "pricefieldvalue2 - 2" . CRM_Core_DAO::VALUE_SEPARATOR];
    $participant = $this->callAPISuccess('participant', 'create', $myParams);

    // expect 2 line items.
    $lineItems = $this->callAPISuccess('LineItem', 'get', [
      'entity_id' => $participant['id'],
      'entity_table' => 'civicrm_participant',
    ]);

    $this->assertEquals(2, $lineItems['count']);

    // Check quantity, label and unit price of lines.
    // TODO: These assertions depend on the order of the line items, which is
    // technically incorrect.

    $lineItem = array_pop($lineItems['values']);
    $this->assertEquals(2, $lineItem['qty']);
    $this->assertEquals(5, $lineItem['unit_price']);
    $this->assertEquals('pricefieldvalue2', $lineItem['label']);

    $lineItem = array_pop($lineItems['values']);
    $this->assertEquals(2, $lineItem['qty']);
    $this->assertEquals(20, $lineItem['unit_price']);
    $this->assertEquals('pricefieldvalue1', $lineItem['label']);
    $this->callAPISuccess('PriceFieldValue', 'create', ['id' => $pfv2['id'], 'label' => 'Price FIeld Value 2 Label']);
    $participantGet = $this->callAPISuccess('Participant', 'get', ['id' => $participant['id']]);
    $this->assertEquals(["pricefieldvalue1 - 2", "pricefieldvalue2 - 2"], $participantGet['values'][$participant['id']]['participant_fee_level']);

    // Cleanup
    $this->callAPISuccess('participant', 'delete', ['id' => $participant['id']]);

    // TODO: I think the price set should be removed, but I don't know how
    // to decouple it properly from the event. For the moment, I'll just comment
    // out the lines below.

    /*
    $this->callAPISuccess('PriceFieldValue', 'delete', array('id' => $pfv1['id']));
    $this->callAPISuccess('PriceFieldValue', 'delete', array('id' => $pfv2['id']));
    $this->callAPISuccess('PriceField', 'delete', array('id' => $pricefield['id']));
    $this->callAPISuccess('PriceSet', 'delete', array('id' => $priceset['id']));
     */
  }

  /**
   * Check with complete array.
   */
  public function testUpdate() {
    $participantId = $this->participantCreate([
      'contactID' => $this->_individualId,
      'eventID' => $this->_eventID,
    ]);
    $params = [
      'id' => $participantId,
      'contact_id' => $this->_individualId,
      'event_id' => $this->_eventID,
      'status_id' => 3,
      'role_id' => 3,
      'register_date' => '2006-01-21',
      'source' => 'US Open',
    ];
    $participant = $this->callAPISuccess('participant', 'create', $params);
    $this->getAndCheck($params, $participant['id'], 'participant');
    $result = $this->participantDelete($params['id']);
  }

  /**
   * Test to check if participant fee level is being changed per CRM-9781
   * Try again  without a custom separater to check that one isn't added
   * (get & check won't accept an array)
   */
  public function testUpdateCreateParticipantFeeLevelNoSeparator() {

    $myParams = $this->_params + ['participant_fee_level' => "fee"];
    $participant = $this->callAPISuccess('participant', 'create', $myParams);
    $this->assertAPISuccess($participant);
    $update = [
      'id' => $participant['id'],
      'status_id' => 2,
    ];
    $this->callAPISuccess('participant', 'create', $update);
    $this->assertEquals($participant['values'][$participant['id']]['fee_level'],
      $myParams['participant_fee_level']
    );
    $this->getAndCheck($update, $participant['id'], 'participant');
  }

  ///////////////// civicrm_participant_update methods

  /**
   * Test civicrm_participant_update with wrong params type.
   */
  public function testUpdateWrongParamsType() {
    $params = 'a string';
    $result = $this->callAPIFailure('participant', 'create', $params);
    $this->assertEquals('Input variable `params` is not an array', $result['error_message']);
  }

  /**
   * Check with empty array.
   */
  public function testUpdateEmptyParams() {
    $params = [];
    $participant = $this->callAPIFailure('participant', 'create', $params);
    $this->assertEquals($participant['error_message'], 'Mandatory key(s) missing from params array: event_id, contact_id');
  }

  /**
   * Check without event_id.
   */
  public function testUpdateWithoutEventId() {
    $participantId = $this->participantCreate(['contactID' => $this->_individualId, 'eventID' => $this->_eventID]);
    $params = [
      'contact_id' => $this->_individualId,
      'status_id' => 3,
      'role_id' => 3,
      'register_date' => '2006-01-21',
      'source' => 'US Open',
      'event_level' => 'Donation',
    ];
    $participant = $this->callAPIFailure('participant', 'create', $params);
    $this->assertEquals($participant['error_message'], 'Mandatory key(s) missing from params array: event_id');
    // Cleanup created participant records.
    $result = $this->participantDelete($participantId);
  }

  /**
   * Check with Invalid participantId.
   */
  public function testUpdateWithWrongParticipantId() {
    $params = [
      'id' => 1234,
      'status_id' => 3,
      'role_id' => 3,
      'register_date' => '2006-01-21',
      'source' => 'US Open',
      'event_level' => 'Donation',
    ];
    $participant = $this->callAPIFailure('Participant', 'update', $params);
  }

  /**
   * Check with Invalid ContactId.
   */
  public function testUpdateWithWrongContactId() {
    $participantId = $this->participantCreate([
      'contactID' => $this->_individualId,
      'eventID' => $this->_eventID,
    ], $this->_apiversion);
    $params = [
      'id' => $participantId,
      'contact_id' => 12345,
      'status_id' => 3,
      'role_id' => 3,
      'register_date' => '2006-01-21',
      'source' => 'US Open',
      'event_level' => 'Donation',
    ];
    $participant = $this->callAPIFailure('participant', 'create', $params);
    $result = $this->participantDelete($participantId);
  }

  ///////////////// civicrm_participant_delete methods

  /**
   * Test civicrm_participant_delete with wrong params type.
   */
  public function testDeleteWrongParamsType() {
    $params = 'a string';
    $result = $this->callAPIFailure('participant', 'delete', $params);
  }

  /**
   * Test civicrm_participant_delete with empty params.
   */
  public function testDeleteEmptyParams() {
    $params = [];
    $result = $this->callAPIFailure('participant', 'delete', $params);
  }

  /**
   * Check with participant_id.
   */
  public function testParticipantDelete() {
    $params = [
      'id' => $this->_participantID,
    ];
    $participant = $this->callAPISuccess('participant', 'delete', $params);
    $this->assertAPISuccess($participant);
    $this->assertDBState('CRM_Event_DAO_Participant', $this->_participantID, NULL, TRUE);
  }

  /**
   * Check without participant_id.
   * and with event_id
   * This should return an error because required param is missing..
   */
  public function testParticipantDeleteMissingID() {
    $params = [
      'event_id' => $this->_eventID,
    ];
    $participant = $this->callAPIFailure('participant', 'delete', $params);
    $this->assertNotNull($participant['error_message']);
  }

  /**
   * Delete with a get - a 'criteria delete'
   */
  public function testNestedDelete() {
    $description = "Criteria delete by nesting a GET & a DELETE.";
    $subfile = "NestedDelete";
    $participants = $this->callAPISuccess('Participant', 'Get', []);
    $this->assertEquals($participants['count'], 3);
    $params = ['contact_id' => $this->_contactID2, 'api.participant.delete' => 1];
    $this->callAPIAndDocument('Participant', 'Get', $params, __FUNCTION__, __FILE__, $description, $subfile);
    $check = $this->callAPISuccess('participant', 'getcount', []);
    $this->assertEquals(1, $check, "only one participant should be left");
  }

  /**
   * Test creation of a participant with an associated contribution.
   */
  public function testCreateParticipantWithPayment() {
    $description = "Single function to create contact with partipation & contribution.
      Note that in the case of 'contribution' the 'create' is implied (api.contribution.create)";
    $subfile = "CreateParticipantPayment";
    $params = [
      'contact_type' => 'Individual',
      'display_name' => 'dlobo',
      'api.participant' => [
        'event_id' => $this->_eventID,
        'status_id' => 1,
        'role_id' => 1,
        'format.only_id' => 1,
      ],
      'api.contribution.create' => [
        'financial_type_id' => 1,
        'total_amount' => 100,
        'format.only_id' => 1,
      ],
      'api.participant_payment.create' => [
        'contribution_id' => '$value.api.contribution.create',
        'participant_id' => '$value.api.participant',
      ],
    ];

    $result = $this->callAPIAndDocument('contact', 'create', $params, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertEquals(1, $result['values'][$result['id']]['api.participant_payment.create']['count']);
    $this->callAPISuccess('contact', 'delete', ['id' => $result['id']]);
  }

  /**
   * Test participant invoke post hook after status update.
   */
  public function testPostHookForAdditionalParticipant() {
    $participantID = $this->participantCreate([
      'contact_id' => $this->_contactID,
      'status_id' => 5,
      'event_id' => $this->_eventID,
    ]);
    $participantID2 = $this->participantCreate([
      'contact_id' => $this->_contactID2,
      'event_id' => $this->_eventID,
      'status_id' => 5,
      'registered_by_id' => $participantID,
    ]);

    $this->hookClass->setHook('civicrm_post', [$this, 'onPost']);
    $params = [
      'id' => $participantID,
      'status_id' => 1,
    ];
    $this->callAPISuccess('Participant', 'create', $params);

    $result = $this->callAPISuccess('Participant', 'get', ['source' => 'Post Hook Update']);
    $this->assertEquals(2, $result['count']);

    $expected = [$participantID, $participantID2];
    $actual = array_keys($result['values']);
    $this->checkArrayEquals($expected, $actual);
  }

}
