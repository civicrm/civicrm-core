<?php
// $Id$

require_once 'CiviTest/CiviUnitTestCase.php';
class api_v3_PriceSetTest extends CiviUnitTestCase {
  protected $_apiversion = 3;
  protected $_params;
  protected $id = 0;
  protected $contactIds = array();
  protected $_entity = 'price_set';
  public $_eNoticeCompliant = TRUE;
  public $DBResetRequired = TRUE;
  public function setUp() {
    parent::setUp();
    $this->_params = array(
      'version' => $this->_apiversion,
#     [domain_id] =>
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

  function tearDown() {
  }

  public function testCreatePriceSet() {
    $result = civicrm_api($this->_entity, 'create', $this->_params);
    $this->id = $result['id'];
    $this->documentMe($this->_params, $result, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertNotNull($result['values'][$result['id']]['id'], 'In line ' . __LINE__);
    $this->getAndCheck($this->_params, $result['id'], $this->_entity);
  }

  public function testGetBasicPriceSet() {
    $getParams = array(
      'version' => $this->_apiversion,
      'name' => 'default_contribution_amount',
    );
    $getResult = civicrm_api($this->_entity, 'get', $getParams);
    $this->documentMe($getParams, $getResult, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($getResult, 'In line ' . __LINE__);
    $this->assertEquals(1, $getResult['count'], 'In line ' . __LINE__);
  }

  public function testEventPriceSet() {
    $event = civicrm_api('event', 'create', array(
      'version' => $this->_apiversion,
      'title' => 'Event with Price Set',
      'event_type_id' => 1,
      'is_public' => 1,
      'start_date' => 20151021,
      'end_date' => 20151023,
      'is_active' => 1,
    ));
    $this->assertAPISuccess($event);
    $createParams = array(
      'version' => $this->_apiversion,
      'entity_table' => 'civicrm_event',
      'entity_id' => $event['id'],
      'name' => 'event price',
      'title' => 'event price',
      'extends' => 1,
    );
    $createResult = civicrm_api($this->_entity, 'create', $createParams);
    $this->documentMe($createParams, $createResult, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($createResult, 'In line ' . __LINE__);
    $id = $createResult['id'];
    $result = civicrm_api($this->_entity, 'get', array(
      'version' => $this->_apiversion,
      'id' => $id,
    ));
    $this->assertEquals(array('civicrm_event' => array($event['id'])), $result['values'][$id]['entity'], 'In line ' . __LINE__);
  }

  public function testDeletePriceSet() {
    $startCount = civicrm_api($this->_entity, 'getcount', array(
      'version' => $this->_apiversion,
      ));
    $createResult = civicrm_api($this->_entity, 'create', $this->_params);
    $deleteParams = array('version' => $this->_apiversion, 'id' => $createResult['id']);
    $deleteResult = civicrm_api($this->_entity, 'delete', $deleteParams);
    $this->documentMe($deleteParams, $deleteResult, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($deleteResult, 'In line ' . __LINE__);
    $endCount = civicrm_api($this->_entity, 'getcount', array(
      'version' => $this->_apiversion,
      ));
    $this->assertEquals($startCount, $endCount, 'In line ' . __LINE__);
  }

  public function testGetFieldsPriceSet() {
    $result = civicrm_api($this->_entity, 'getfields', array('version' => $this->_apiversion, 'action' => 'create'));
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertEquals(16, $result['values']['is_quick_config']['type']);
  }

  public static function setUpBeforeClass() {
      // put stuff here that should happen before all tests in this unit
  }

  public static function tearDownAfterClass(){
    $tablesToTruncate = array(
      'civicrm_contact',
      'civicrm_contribution',
    );
    $unitTest = new CiviUnitTestCase();
    $unitTest->quickCleanup($tablesToTruncate);
  }
}

