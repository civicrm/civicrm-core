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
 * Class api_v3_PriceSetTest
 * @group headless
 */
class api_v3_PriceSetTest extends CiviUnitTestCase {
  protected $_apiversion = 3;
  protected $_params;
  protected $id = 0;
  protected $contactIds = array();
  protected $_entity = 'price_set';

  public $DBResetRequired = TRUE;

  /**
   * Set up for class.
   */
  public function setUp() {
    parent::setUp();
    $this->_params = array(
      'name' => 'default_goat_priceset',
      'title' => 'Goat accessories',
      'is_active' => 1,
      'help_pre' => "Please describe your goat in detail",
      'help_post' => "thank you for your time",
      'extends' => 2,
      'financial_type_id' => 1,
      'is_quick_config' => 1,
      'is_reserved' => 1,
    );
  }

  public function tearDown() {
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
    $createParams = array(
      'name' => 'some_price_set',
      'title' => 'Some Price Set',
      'is_active' => 1,
      'financial_type_id' => 1,
      'extends' => array(1, 2),
    );
    $createResult = $this->callAPIAndDocument($this->_entity, 'create', $createParams, __FUNCTION__, __FILE__);

    // Get priceset we just created.
    $result = $this->callAPISuccess($this->_entity, 'getSingle', array(
      'id' => $createResult['id'],
    ));

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
    $getParams = array(
      'name' => 'default_contribution_amount',
    );
    $getResult = $this->callAPIAndDocument($this->_entity, 'get', $getParams, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $getResult['count']);
  }

  public function testEventPriceSet() {
    $event = $this->callAPISuccess('event', 'create', array(
      'title' => 'Event with Price Set',
      'event_type_id' => 1,
      'is_public' => 1,
      'start_date' => 20151021,
      'end_date' => 20151023,
      'is_active' => 1,
    ));
    $createParams = array(
      'entity_table' => 'civicrm_event',
      'entity_id' => $event['id'],
      'name' => 'event price',
      'title' => 'event price',
      'extends' => 1,
    );
    $createResult = $this->callAPIAndDocument($this->_entity, 'create', $createParams, __FUNCTION__, __FILE__);
    $result = $this->callAPISuccess($this->_entity, 'get', array(
      'id' => $createResult['id'],
    ));
    $this->assertEquals(array('civicrm_event' => array($event['id'])), $result['values'][$createResult['id']]['entity']);
  }

  public function testDeletePriceSet() {
    $startCount = $this->callAPISuccess($this->_entity, 'getcount', array());
    $createResult = $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $deleteParams = array('id' => $createResult['id']);
    $this->callAPIAndDocument($this->_entity, 'delete', $deleteParams, __FUNCTION__, __FILE__);
    $endCount = $this->callAPISuccess($this->_entity, 'getcount', array());
    $this->assertEquals($startCount, $endCount);
  }

  public function testGetFieldsPriceSet() {
    $result = $this->callAPISuccess($this->_entity, 'getfields', array('action' => 'create'));
    $this->assertEquals(16, $result['values']['is_quick_config']['type']);
  }

  public static function setUpBeforeClass() {
    // put stuff here that should happen before all tests in this unit
  }

  public static function tearDownAfterClass() {
    $tablesToTruncate = array(
      'civicrm_contact',
      'civicrm_contribution',
    );
    $unitTest = new CiviUnitTestCase();
    $unitTest->quickCleanup($tablesToTruncate);
  }

}
