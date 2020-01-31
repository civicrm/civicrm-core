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
 * Class api_v3_PriceSetTest
 * @group headless
 */
class api_v3_PriceSetTest extends CiviUnitTestCase {
  protected $_apiversion = 3;
  protected $_params;
  protected $id = 0;
  protected $contactIds = [];
  protected $_entity = 'price_set';

  public $DBResetRequired = TRUE;

  /**
   * Set up for class.
   */
  public function setUp() {
    parent::setUp();
    $this->_params = [
      'name' => 'default_goat_priceset',
      'title' => 'Goat accessories',
      'is_active' => 1,
      'help_pre' => "Please describe your goat in detail",
      'help_post' => "thank you for your time",
      'extends' => 2,
      'financial_type_id' => 1,
      'is_quick_config' => 1,
      'is_reserved' => 1,
    ];
  }

  /**
   * Test create price set.
   */
  public function testCreatePriceSet() {
    $result = $this->callAPIAndDocument($this->_entity, 'create', $this->_params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
    $this->assertNotNull($result['values'][$result['id']]['id']);
    $this->getAndCheck($this->_params, $result['id'], $this->_entity);
  }

  /**
   * Test for creating price sets used for both events and contributions.
   */
  public function testCreatePriceSetForEventAndContribution() {
    // Create the price set
    $createParams = [
      'name' => 'some_price_set',
      'title' => 'Some Price Set',
      'is_active' => 1,
      'financial_type_id' => 1,
      'extends' => [1, 2],
    ];
    $createResult = $this->callAPIAndDocument($this->_entity, 'create', $createParams, __FUNCTION__, __FILE__);

    // Get priceset we just created.
    $result = $this->callAPISuccess($this->_entity, 'getSingle', [
      'id' => $createResult['id'],
    ]);

    // Count the number of items in 'extends'.
    $this->assertEquals(2, count($result['extends']));
  }

  /**
   * Check that no name doesn't cause failure.
   */
  public function testCreatePriceSetNoName() {
    $params = $this->_params;
    unset($params['name']);
    $this->callAPISuccess($this->_entity, 'create', $params);
  }

  /**
   */
  public function testGetBasicPriceSet() {
    $getParams = [
      'name' => 'default_contribution_amount',
    ];
    $getResult = $this->callAPIAndDocument($this->_entity, 'get', $getParams, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $getResult['count']);
  }

  public function testEventPriceSet() {
    $event = $this->callAPISuccess('event', 'create', [
      'title' => 'Event with Price Set',
      'event_type_id' => 1,
      'is_public' => 1,
      'start_date' => 20151021,
      'end_date' => 20151023,
      'is_active' => 1,
    ]);
    $createParams = [
      'entity_table' => 'civicrm_event',
      'entity_id' => $event['id'],
      'name' => 'event price',
      'title' => 'event price',
      'extends' => 1,
    ];
    $createResult = $this->callAPIAndDocument($this->_entity, 'create', $createParams, __FUNCTION__, __FILE__);
    $result = $this->callAPISuccess($this->_entity, 'get', [
      'id' => $createResult['id'],
    ]);
    $this->assertEquals(['civicrm_event' => [$event['id']]], $result['values'][$createResult['id']]['entity']);
  }

  public function testDeletePriceSet() {
    $startCount = $this->callAPISuccess($this->_entity, 'getcount', []);
    $createResult = $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $deleteParams = ['id' => $createResult['id']];
    $this->callAPIAndDocument($this->_entity, 'delete', $deleteParams, __FUNCTION__, __FILE__);
    $endCount = $this->callAPISuccess($this->_entity, 'getcount', []);
    $this->assertEquals($startCount, $endCount);
  }

  public function testGetFieldsPriceSet() {
    $result = $this->callAPISuccess($this->_entity, 'getfields', ['action' => 'create']);
    $this->assertEquals(16, $result['values']['is_quick_config']['type']);
  }

  public static function setUpBeforeClass() {
    // put stuff here that should happen before all tests in this unit
  }

  public static function tearDownAfterClass() {
    $tablesToTruncate = [
      'civicrm_contact',
      'civicrm_contribution',
    ];
    $unitTest = new CiviUnitTestCase();
    $unitTest->quickCleanup($tablesToTruncate);
  }

}
