<?php
// $Id$

require_once 'CiviTest/CiviUnitTestCase.php';
class api_v3_LineItemTest extends CiviUnitTestCase {
  protected $_apiversion = 3;
  protected $testAmount = 34567;
  protected $params;
  protected $id = 0;
  protected $contactIds = array();
  protected $_entity = 'line_item';
  protected $contribution_result = null;
  public $_eNoticeCompliant = TRUE;
  public $DBResetRequired = TRUE;
  public function setUp() {
    parent::setUp();
    $this->_contributionTypeId = $this->contributionTypeCreate();
    $this->_individualId = $this->individualCreate();
    $contributionParams = array(
      'contact_id' => $this->_individualId,
      'receive_date' => '20120511',
      'total_amount' => 100.00,
      'financial_type_id' => $this->_contributionTypeId,
      'non_deductible_amount' => 10.00,
      'fee_amount' => 51.00,
      'net_amount' => 91.00,
      'source' => 'SSF',
      'contribution_status_id' => 1,
      'version' => $this->_apiversion,
    );
    $contribution = civicrm_api('contribution','create', $contributionParams);
    $this->params = array(
      'version' => $this->_apiversion,
      'price_field_value_id' => 1,
      'price_field_id' => 1,
      'entity_table' => 'civicrm_contribution',
      'entity_id' => $contribution['id'],
      'qty' => 1,
      'unit_price' => 50,
      'line_total' => 50,
    );
  }

  function tearDown() {

    foreach ($this->contactIds as $id) {
      civicrm_api('contact', 'delete', array('version' => $this->_apiversion, 'id' => $id));
    }
    $this->quickCleanup(
        array(
            'civicrm_contact',
            'civicrm_contribution',
            'civicrm_line_item',
        )
    );
    $this->contributionTypeDelete();
  }

  public function testCreateLineItem() {
    $this->quickCleanup(
        array(
            'civicrm_line_item',
        )
    );
    $result = civicrm_api($this->_entity, 'create', $this->params + array('debug' => 1));
    $this->id = $result['id'];
    $this->documentMe($this->params, $result, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertNotNull($result['values'][$result['id']]['id'], 'In line ' . __LINE__);
    $this->getAndCheck($this->params, $result['id'], $this->_entity);
  }

  public function testGetBasicLineItem() {
    $getParams = array(
      'version' => $this->_apiversion,
      'entity_table' => 'civicrm_contribution',
    );
    $getResult = civicrm_api($this->_entity, 'get', $getParams);
    $this->documentMe($getParams, $getResult, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($getResult, 'In line ' . __LINE__);
    $this->assertEquals(1, $getResult['count'], 'In line ' . __LINE__);
  }

  public function testDeleteLineItem() {
    $getParams = array(
        'version' => $this->_apiversion,
        'entity_table' => 'civicrm_contribution',
    );
    $getResult = civicrm_api($this->_entity, 'get', $getParams);
    $deleteParams = array('version' => $this->_apiversion, 'id' => $getResult['id']);
    $deleteResult = civicrm_api($this->_entity, 'delete', $deleteParams);
    $this->documentMe($deleteParams, $deleteResult, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($deleteResult, 'In line ' . __LINE__);
    $checkDeleted = civicrm_api($this->_entity, 'get', array(
      'version' => $this->_apiversion,
      ));
    $this->assertEquals(0, $checkDeleted['count'], 'In line ' . __LINE__);
  }

  public function testGetFieldsLineItem() {
    $result = civicrm_api($this->_entity, 'getfields', array('action' => 'create', 'version' => $this->_apiversion, 'action' => 'create'));
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertEquals(1, $result['values']['entity_id']['api.required']);
  }

  public static function setUpBeforeClass() {
      // put stuff here that should happen before all tests in this unit
  }

  public static function tearDownAfterClass(){
    $tablesToTruncate = array(
      'civicrm_contact',
      'civicrm_financial_type',
      'civicrm_contribution',
      'civicrm_line_item',
    );
    $unitTest = new CiviUnitTestCase();
    $unitTest->quickCleanup($tablesToTruncate);
  }
}

