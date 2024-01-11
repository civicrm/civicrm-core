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
 * Test class for Batch API - civicrm_participant_*
 *
 * @package CiviCRM_APIv3
 */

use Civi\Api4\Participant;

/**
 * Class api_v3_ParticipantTest
 * @group headless
 */
class api_v3_ParticipantTest extends CiviUnitTestCase {

  protected $_entity;
  protected $_contactID;
  protected $_contactID2;
  protected $_createdParticipants;
  protected $_eventID;
  protected $_individualId;
  protected $_params;

  public function setUp(): void {
    parent::setUp();
    $this->_entity = 'participant';
    $this->eventCreateUnpaid();

    $this->_contactID = $this->individualCreate();

    $this->_createdParticipants = [];
    $this->_individualId = $this->individualCreate();

    $this->ids['Participant']['primary'] = $this->participantCreate([
      'contact_id' => $this->_contactID,
      'event_id' => $this->getEventID(),
    ]);
    $this->_contactID2 = $this->individualCreate();
    $this->ids['Participant'][2] = $this->participantCreate([
      'contact_id' => $this->_contactID2,
      'event_id' => $this->getEventID(),
      'registered_by_id' => $this->ids['Participant']['primary'],
    ]);
    $this->participantCreate([
      'contact_id' => $this->_contactID2,
      'event_id' => $this->getEventID(),
    ]);
    $this->_params = [
      'contact_id' => $this->_contactID,
      'event_id' => $this->getEventID(),
      'status_id' => 1,
      'role_id' => 1,
      // to ensure it matches later on
      'register_date' => '2007-07-21 00:00:00',
      'source' => 'Online Event Registration: API Testing',
    ];
  }

  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    $this->quickCleanup(['civicrm_contact'], TRUE);
    parent::tearDown();
  }

  /**
   * Check that getCount can count past 25.
   */
  public function testGetCountLimit(): void {
    $contactIDs = [];

    for ($count = $this->callAPISuccessGetCount('Participant', []); $count < 27; $count++) {
      $contactIDs[] = $contactID = $this->individualCreate();
      $this->participantCreate(['contact_id' => $contactID, 'event_id' => $this->getEventID()]);
    }
    $this->callAPISuccessGetCount('Participant', [], 27);

    foreach ($contactIDs as $contactID) {
      $this->callAPISuccess('Contact', 'delete', ['id' => $contactID]);
    }
  }

  /**
   * Test get participants with role_id.
   */
  public function testGetParticipantWithRole(): void {
    $roleId = [1, 2, 3];
    foreach ($roleId as $role) {
      $this->participantCreate([
        'contact_id' => $this->individualCreate(),
        'role_id' => $role,
        'event_id' => $this->getEventID(),
      ]);
    }

    $params = [
      'role_id' => 2,
    ];
    $result = $this->callAPISuccess('participant', 'get', $params);
    //Assert all the returned participants has a role_id of 2
    foreach ($result['values'] as $values) {
      $this->assertEquals(2, $values['participant_role_id']);
    }

    $this->participantCreate([
      'id' => $this->ids['Participant']['primary'],
      'role_id' => NULL,
      'event_id' => $this->getEventID(),
    ]);

    $params['role_id'] = [
      'IS NULL' => 1,
    ];
    $result = $this->callAPISuccess('participant', 'get', $params);
    foreach ($result['values'] as $values) {
      $this->assertEquals(NULL, $values['participant_role_id']);
    }

  }

  /**
   * Check with complete array + custom field
   * Note that the test is written on purpose without any
   * variables specific to participant so it can be replicated into other entities
   * and / or moved to the automated test suite
   */
  public function testCreateWithCustom(): void {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);

    $params = $this->_params;
    $params['custom_' . $ids['custom_field_id']] = 'custom string';

    $result = $this->callAPISuccess($this->_entity, 'create', $params);

    $this->assertEquals($result['id'], $result['values'][$result['id']]['id']);

    $check = $this->callAPISuccess($this->_entity, 'get', ['id' => $result['id']]);
    $this->assertEquals('custom string', $check['values'][$check['id']]['custom_' . $ids['custom_field_id']], ' in line ' . __LINE__);

    $this->customFieldDelete($ids['custom_field_id']);
    $this->customGroupDelete($ids['custom_group_id']);
  }

  /**
   * Check with participant_id.
   */
  public function testGetParticipantIDOnly(): void {
    $params = [
      'participant_id' => $this->ids['Participant']['primary'],
      'return' => [
        'participant_id',
        'event_id',
        'participant_register_date',
        'participant_source',
      ],
    ];
    $result = $this->callAPISuccess('participant', 'get', $params);
    $this->assertEquals($result['values'][$this->ids['Participant']['primary']]['event_id'], $this->getEventID());
    $this->assertEquals('2007-02-19 00:00:00', $result['values'][$this->ids['Participant']['primary']]['participant_register_date']);
    $this->assertEquals('Wimbledon', $result['values'][$this->ids['Participant']['primary']]['participant_source']);
    $params = [
      'id' => $this->ids['Participant']['primary'],
      'return' => 'id,participant_register_date,event_id',

    ];
    $result = $this->callAPISuccess('participant', 'get', $params);
    $this->assertEquals($result['values'][$this->ids['Participant']['primary']]['event_id'], $this->getEventID());
    $this->assertEquals('2007-02-19 00:00:00', $result['values'][$this->ids['Participant']['primary']]['participant_register_date']);

  }

  /**
   * Test permission for participant get.
   */
  public function testGetParticipantWithPermission(): void {
    $config = CRM_Core_Config::singleton();
    $config->userPermissionClass->permissions = [];
    $params = [
      'event_id' => $this->getEventID(),
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
    $this->callAPISuccess('Participant', 'get', $params);
  }

  /**
   * Check with params id.
   */
  public function testGetParamsAsIDOnly(): void {
    $params = [
      'id' => $this->ids['Participant']['primary'],
    ];
    $result = $this->callAPISuccess('participant', 'get', $params);
    $this->assertEquals($result['values'][$this->ids['Participant']['primary']]['event_id'], $this->getEventID());
    $this->assertEquals('2007-02-19 00:00:00', $result['values'][$this->ids['Participant']['primary']]['participant_register_date']);
    $this->assertEquals('Wimbledon', $result['values'][$this->ids['Participant']['primary']]['participant_source']);
    $this->assertEquals($result['id'], $result['values'][$this->ids['Participant']['primary']]['id']);
  }

  /**
   * Test use of nested get to fetch event data with participant records.
   */
  public function testGetNestedEventGet(): void {
    // Create a second event & add participant to it.
    $event = $this->eventCreateUnpaid([], 'additional');
    $this->callAPISuccess('Participant', 'create', [
      'event_id' => $event['id'],
      'contact_id' => $this->_contactID,
    ]);

    $params = [
      'id' => $this->ids['Participant']['primary'],
      'api.event.get' => 1,
    ];
    $result = $this->callAPISuccess('Participant', 'get', $params)['values'];
    $this->assertEquals($this->getEventID(), $result[$this->ids['Participant']['primary']]['event_id']);
    $this->assertEquals('2007-02-19 00:00:00', $result[$this->ids['Participant']['primary']]['participant_register_date']);
    $this->assertEquals('Wimbledon', $result[$this->ids['Participant']['primary']]['participant_source']);
    $this->assertEquals($this->getEventID(), $result[$this->ids['Participant']['primary']]['api.event.get']['id']);
  }

  /**
   * Check Participant Get respects return properties.
   */
  public function testGetWithReturnProperties(): void {
    $params = [
      'contact_id' => $this->_contactID,
      'return.status_id' => 1,
      'return.participant_status_id' => 1,
      'options' => ['limit' => 1],
    ];
    $result = $this->callAPISuccess('Participant', 'get', $params);
    $this->assertArrayHasKey('participant_status_id', $result['values'][$result['id']]);
  }

  /**
   * Check with contact_id.
   */
  public function testGetContactIDOnly(): void {
    $params = [
      'contact_id' => $this->_contactID,
    ];
    $participant = $this->callAPISuccess('Participant', 'get', $params);

    $this->assertEquals($this->ids['Participant']['primary'], $participant['id']);
    $this->assertEquals($this->getEventID(), $participant['values'][$participant['id']]['event_id']);
    $this->assertEquals('2007-02-19 00:00:00', $participant['values'][$participant['id']]['participant_register_date']);
    $this->assertEquals('Wimbledon', $participant['values'][$participant['id']]['participant_source']);
    $this->assertEquals($participant['id'], $participant['values'][$participant['id']]['id']);
  }

  /**
   * Check with event_id.
   * fetch first record
   */
  public function testGetMultiMatchReturnFirst(): void {
    $params = [
      'event_id' => $this->getEventID(),
      'rowCount' => 1,
    ];

    $participant = $this->callAPISuccess('participant', 'get', $params);
    $this->assertNotNull($participant['id']);
  }

  /**
   * Check with event_id.
   * in v3 this should return all participants
   */
  public function testGetMultiMatchNoReturnFirst(): void {
    $params = [
      'event_id' => $this->getEventID(),
    ];
    $participant = $this->callAPISuccess('Participant', 'get', $params);
    $this->assertNotNull($participant['count'], 3);
  }

  /**
   * Test civicrm_participant_get with empty params.
   * In this case all the participant records are returned.
   */
  public function testSearchEmptyParams(): void {
    $result = $this->callAPISuccess('participant', 'get', []);
    // expecting 3 participant records
    $this->assertEquals(3, $result['count']);
  }

  /**
   * Check with participant_id.
   */
  public function testSearchParticipantIDOnly(): void {
    $params = [
      'participant_id' => $this->ids['Participant']['primary'],
    ];
    $participant = $this->callAPISuccess('Participant', 'get', $params);
    $this->assertEquals($participant['values'][$this->ids['Participant']['primary']]['event_id'], $this->getEventID());
    $this->assertEquals('2007-02-19 00:00:00', $participant['values'][$this->ids['Participant']['primary']]['participant_register_date']);
    $this->assertEquals('Wimbledon', $participant['values'][$this->ids['Participant']['primary']]['participant_source']);
  }

  /**
   * Check with contact_id.
   */
  public function testSearchContactIDOnly(): void {
    // Should get 2 participant records for this contact.
    $params = [
      'contact_id' => $this->_contactID2,
    ];
    $participant = $this->callAPISuccess('Participant', 'get', $params);

    $this->assertEquals(2, $participant['count']);
  }

  /**
   * Check with event_id.
   */
  public function testSearchByEvent(): void {
    // Should get >= 3 participant records for this event. Also testing that last_name and event_title are returned.
    $params = [
      'event_id' => $this->getEventID(),
      'return.last_name' => 1,
      'return.event_title' => 1,
    ];
    $participant = $this->callAPISuccess('participant', 'get', $params);
    if ($participant['count'] < 3) {
      $this->fail('Event search returned less than expected minimum of 3 records.');
    }

    $this->assertEquals('Anderson', $participant['values'][$this->ids['Participant']['primary']]['last_name']);
    $this->assertEquals('Annual CiviCRM meet', $participant['values'][$this->ids['Participant']['primary']]['event_title']);
  }

  /**
   * Check with event_id.
   * fetch with limit
   */
  public function testSearchByEventWithLimit(): void {
    // Should 2 participant records since we're passing rowCount = 2.
    $params = [
      'event_id' => $this->getEventID(),
      'rowCount' => 2,
    ];
    $participant = $this->callAPISuccess('participant', 'get', $params);

    $this->assertEquals(2, $participant['count']);
  }

  /**
   * Test search by lead booker (registered by ID)
   */
  public function testSearchByRegisteredByID(): void {
    $params = [
      'registered_by_id' => $this->ids['Participant']['primary'],
    ];
    $participant = $this->callAPISuccess('participant', 'get', $params);

    $this->assertEquals(1, $participant['count']);
    $this->assertEquals($this->ids['Participant'][2], $participant['id']);
  }

  /**
   * Check with contact_id & event_id
   */
  public function testCreateEventIDOnly(): void {
    $params = [
      'contact_id' => $this->_contactID,
      'event_id' => $this->getEventID(),
    ];
    $participant = $this->callAPISuccess('participant', 'create', $params);
    $this->getAndCheck($params, $participant['id'], 'participant');
  }

  /**
   * Check with complete array.
   */
  public function testCreateAllParams(): void {
    $participant = $this->callAPISuccess('participant', 'create', $this->_params);
    $this->ids['Participant']['primary'] = $participant['id'];
    $this->assertDBState('CRM_Event_DAO_Participant', $participant['id'], $this->_params);
  }

  /**
   * Test that an overlong source is handled.
   */
  public function testLongSource(): void {
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
  public function testCreateUpdateReceiveDate(): void {
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
  public function testCreateUpdateParticipantFeeLevel(): void {
    $myParams = $this->_params + ['participant_fee_level' => CRM_Core_DAO::VALUE_SEPARATOR . 'fee' . CRM_Core_DAO::VALUE_SEPARATOR];
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
   *
   */
  public function testCreateParticipantLineItems(): void {
    // Create a price set for this event.

    $priceSet = $this->callAPISuccess('PriceSet', 'create', [
      'name' => 'my_price_set',
      'title' => 'My Price Set',
      'is_active' => 1,
      'extends' => 1,
      'financial_type_id' => 4,
    ]);

    // Add the price set to the event with another API call.
    // I tried to do this at once, but it did not work.

    $priceSet = $this->callAPISuccess('PriceSet', 'create', [
      'entity_table' => 'civicrm_event',
      'entity_id' => $this->getEventID(),
      'id' => $priceSet['id'],
    ]);

    $priceField = $this->callAPISuccess('PriceField', 'create', [
      'price_set_id' => $priceSet['id'],
      'name' => 'my_price_field',
      'label' => 'My Price Field',
      'html_type' => 'Text',
      'is_enter_qty' => 1,
      'is_display_amounts' => 1,
      'is_active' => 1,
    ]);

    $this->callAPISuccess('PriceFieldValue', 'create', [
      'price_field_id' => $priceField['id'],
      'name' => 'price_field_value_1',
      'label' => 'price_field_value_1',
      'amount' => 20,
      'is_active' => 1,
      'financial_type_id' => 4,
    ]);

    $pfv2 = $this->callAPISuccess('PriceFieldValue', 'create', [
      'price_field_id' => $priceField['id'],
      'name' => 'price_field_value_2',
      'label' => 'price_field_value_2',
      'amount' => 5,
      'is_active' => 1,
      'financial_type_id' => 4,
    ]);

    // pay 2 times price field value 1, and 2 times price field value 2.
    $myParams = $this->_params + ['participant_fee_level' => CRM_Core_DAO::VALUE_SEPARATOR . 'price_field_value_1 - 2' . CRM_Core_DAO::VALUE_SEPARATOR . 'price_field_value_2 - 2' . CRM_Core_DAO::VALUE_SEPARATOR];
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
    $this->assertEquals('price_field_value_2', $lineItem['label']);

    $lineItem = array_pop($lineItems['values']);
    $this->assertEquals(2, $lineItem['qty']);
    $this->assertEquals(20, $lineItem['unit_price']);
    $this->assertEquals('price_field_value_1', $lineItem['label']);
    $this->callAPISuccess('PriceFieldValue', 'create', ['id' => $pfv2['id'], 'label' => 'Price Field Value 2 Label']);
    $participantGet = $this->callAPISuccess('Participant', 'get', ['id' => $participant['id']]);
    $this->assertEquals(['price_field_value_1 - 2', 'price_field_value_2 - 2'], $participantGet['values'][$participant['id']]['participant_fee_level']);
    $contactID4 = $this->individualCreate();
    $myParams['contact_id'] = $contactID4;
    $myParams['participant_fee_level'] = CRM_Core_DAO::VALUE_SEPARATOR . 'price_field_value_1 - 2' . CRM_Core_DAO::VALUE_SEPARATOR . 'Price Field Value 2 Label - 2' . CRM_Core_DAO::VALUE_SEPARATOR;
    $AdditionalParticipant = $this->callAPISuccess('Participant', 'create', $myParams);
    $this->assertEquals([
      'price_field_value_1 - 2',
      'Price Field Value 2 Label - 2',
    ], $AdditionalParticipant['values'][$AdditionalParticipant['id']]['fee_level']);
    $lineItems = $this->callAPISuccess('LineItem', 'get', [
      'entity_id' => $AdditionalParticipant['id'],
      'entity_table' => 'civicrm_participant',
    ]);
    $this->assertEquals(2, $lineItems['count']);

    // Check quantity, label and unit price of lines.
    // TODO: These assertions depend on the order of the line items, which is
    // technically incorrect.

    $lineItem = array_pop($lineItems['values']);
    $this->assertEquals(2, $lineItem['qty']);
    $this->assertEquals(5, $lineItem['unit_price']);
    $this->assertEquals('Price Field Value 2 Label', $lineItem['label']);

    $lineItem = array_pop($lineItems['values']);
    $this->assertEquals(2, $lineItem['qty']);
    $this->assertEquals(20, $lineItem['unit_price']);
    $this->assertEquals('price_field_value_1', $lineItem['label']);

    // Cleanup
    $this->callAPISuccess('Participant', 'delete', ['id' => $participant['id']]);
  }

  /**
   * Check with complete array.
   *
   * @throws \CRM_Core_Exception
   */
  public function testUpdate(): void {
    $participantId = $this->participantCreate([
      'contact_id' => $this->_individualId,
      'event_id' => $this->getEventID(),
    ]);
    $params = [
      'id' => $participantId,
      'contact_id' => $this->_individualId,
      'event_id' => $this->getEventID(),
      'status_id' => 3,
      'role_id' => [3],
      'register_date' => '2006-01-21 00:00:00',
      'source' => 'US Open',
    ];
    $participantID = $this->callAPISuccess('Participant', 'create', $params)['id'];
    $participant = Participant::get()
      ->setSelect(array_keys($params))
      ->addWhere('id', '=', $participantID)
      ->execute()->first();

    foreach ($params as $key => $value) {
      $this->assertEquals($value, $participant[$key], $key . ' mismatch');
    }
  }

  /**
   * Test to check if participant fee level is being changed per CRM-9781
   * Try again  without a custom separator to check that one isn't added
   * (get & check won't accept an array)
   */
  public function testUpdateCreateParticipantFeeLevelNoSeparator(): void {

    $myParams = $this->_params + ['participant_fee_level' => 'fee'];
    $participant = $this->callAPISuccess('Participant', 'create', $myParams);
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

  /**
   * Check with Invalid participantId.
   */
  public function testUpdateWithWrongParticipantID(): void {
    $params = [
      'id' => 1234,
      'status_id' => 3,
      'role_id' => 3,
      'register_date' => '2006-01-21',
      'source' => 'US Open',
      'event_level' => 'Donation',
    ];
    $this->callAPIFailure('Participant', 'update', $params);
  }

  /**
   * Check with Invalid ContactId.
   */
  public function testUpdateWithWrongContactID(): void {
    $participantID = $this->participantCreate([
      'contact_id' => $this->individualCreate(),
      'event_id' => $this->getEventID(),
    ]);
    $params = [
      'id' => $participantID,
      'contact_id' => 12345,
      'status_id' => 3,
      'role_id' => 3,
      'register_date' => '2006-01-21',
      'source' => 'US Open',
      'event_level' => 'Donation',
    ];
    $this->callAPIFailure('Participant', 'create', $params);
  }

  /**
   * Delete with a get - a 'criteria delete'
   */
  public function testNestedDelete(): void {
    $participants = $this->callAPISuccess('Participant', 'Get', []);
    $this->assertEquals(3, $participants['count']);
    $params = ['contact_id' => $this->_contactID2, 'api.participant.delete' => 1];
    $this->callAPISuccess('Participant', 'Get', $params);
    $check = $this->callAPISuccess('participant', 'getcount', []);
    $this->assertEquals(1, $check, 'only one participant should be left');
  }

  /**
   * Test creation of a participant with an associated contribution.
   */
  public function testCreateParticipantWithPayment(): void {
    $params = [
      'contact_type' => 'Individual',
      'display_name' => 'Guru',
      'api.participant' => [
        'event_id' => $this->getEventID(),
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

    $result = $this->callAPISuccess('contact', 'create', $params);
    $this->assertEquals(1, $result['values'][$result['id']]['api.participant_payment.create']['count']);
    $this->callAPISuccess('contact', 'delete', ['id' => $result['id']]);
  }

  /**
   * Test participant invoke post hook after status update.
   */
  public function testPostHookForAdditionalParticipant(): void {
    // @todo - figure out why validation tests don't pass
    $this->isValidateFinancialsOnPostAssert = FALSE;
    $participantID = $this->participantCreate([
      'contact_id' => $this->_contactID,
      'status_id' => 5,
      'event_id' => $this->getEventID(),
    ]);
    $participantID2 = $this->participantCreate([
      'contact_id' => $this->_contactID2,
      'event_id' => $this->getEventID(),
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
