<?php
// $Id$

require_once 'CiviTest/CiviUnitTestCase.php';
class api_v3_PriceFieldValueTest extends CiviUnitTestCase {
  protected $_apiversion = 3;
  protected $_params;
  protected $id = 0;
  protected $priceSetID = 0;
  protected $_entity = 'price_field_value';
  public $_eNoticeCompliant = TRUE;
  public $DBResetRequired = TRUE;

  public function setUp() {
    parent::setUp();
    // put stuff here that should happen before all tests in this unit
    $priceSetparams = array(
      'version' => 3,
      #     [domain_id] =>
      'name' => 'default_goat_priceset',
      'title' => 'Goat accomodation',
      'is_active' => 1,
      'help_pre' => "Where does your goat sleep",
      'help_post' => "thank you for your time",
      'extends' => 2,
      'financial_type_id' => 1,
      'is_quick_config' => 1,
      'is_reserved' => 1,
    );

    $price_set = civicrm_api('price_set', 'create',$priceSetparams);
    $this->priceSetID = $price_set['id'];

    $priceFieldparams = array(
      'version' => $this->_apiversion,
      'price_set_id' => $this->priceSetID,
      'name' => 'grassvariety',
      'label' => 'Grass Variety',
      'html_type' => 'Text',
      'is_enter_qty' => 1,
      'is_active' => 1,
    );
    $priceField = civicrm_api('price_field','create', $priceFieldparams);
    $this->priceFieldID = $priceField['id'];
    $this->_params = array(
      'version' => 3,
      'price_field_id' => $this->priceFieldID,
      'name' => 'ryegrass',
      'label' => 'juicy and healthy',
      'amount' => 1
     );
  
    $membershipOrgId = $this->organizationCreate(NULL);
    $this->_membershipTypeID = $this->membershipTypeCreate($membershipOrgId);
    $priceSetparams1 = array(
      'version' => $this->_apiversion,
      'name' => 'priceset',
      'title' => 'Priceset with Multiple Terms',
      'is_active' => 1,
      'extends' => 3,
      'financial_type_id' => 2,
      'is_quick_config' => 1,
      'is_reserved' => 1,
    );
    $price_set1 = civicrm_api('price_set', 'create',$priceSetparams1);
    $this->priceSetID1 = $price_set1['id'];
    $priceFieldparams1 = array(
      'version' => $this->_apiversion,
      'price_set_id' => $this->priceSetID1,
      'name' => 'memtype',
      'label' => 'memtype',
      'html_type' => 'Radio',
      'is_enter_qty' => 1,
      'is_active' => 1,
    );
    $priceField1 = civicrm_api('price_field','create', $priceFieldparams1);
    $this->priceFieldID1 = $priceField1['id'];
  }

  function tearDown() {
    $tablesToTruncate = array(
        'civicrm_contact',
        'civicrm_contribution',
    );
    $this->quickCleanup($tablesToTruncate);
    $this->membershipTypeDelete(array('id' => $this->_membershipTypeID));
    civicrm_api('PriceField','delete', array(
        'version' => 3,
        'id' => $this->priceFieldID1,
    ));
    civicrm_api('PriceSet','delete', array(
      'version' => 3,
      'id' => $this->priceSetID1,
    ));
    civicrm_api('PriceField','delete', array(
        'version' => 3,
        'id' => $this->priceFieldID,
    ));
    $delete = civicrm_api('PriceSet','delete', array(
      'version' => 3,
      'id' => $this->priceSetID,
    ));

    $this->assertAPISuccess($delete);
  }

  public function testCreatePriceFieldValue() {
    $result = civicrm_api($this->_entity, 'create', $this->_params);
    $this->id = $result['id'];
    $this->documentMe($this->_params, $result, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertNotNull($result['values'][$result['id']]['id'], 'In line ' . __LINE__);
    $this->getAndCheck($this->_params, $result['id'], $this->_entity);
  }

  public function testGetBasicPriceFieldValue() {
    $createResult = civicrm_api($this->_entity, 'create', $this->_params);
    $this->id = $createResult['id'];
    $this->assertAPISuccess($createResult);
    $getParams = array(
      'version' => $this->_apiversion,
      'name' => 'contribution_amount',
    );
    $getResult = civicrm_api($this->_entity, 'get', $getParams);
    $this->documentMe($getParams, $getResult, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($getResult, 'In line ' . __LINE__);
    $this->assertEquals(1, $getResult['count'], 'In line ' . __LINE__);
    civicrm_api('price_field_value','delete', array('version' => 3, 'id' => $createResult['id']));
  }

  public function testDeletePriceFieldValue() {
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

  public function testGetFieldsPriceFieldValue() {
    $result = civicrm_api($this->_entity, 'getfields', array('version' => $this->_apiversion, 'action' => 'create'));
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertEquals(1, $result['values']['max_value']['type']);
  }

  public function testCreatePriceFieldValuewithMultipleTerms() {
    $params = array(
      'version' => 3,
      'price_field_id' => $this->priceFieldID1,
      'membership_type_id' =>  $this->_membershipTypeID,
      'name' => 'memType1',
      'label' => 'memType1',
      'amount' => 90,
      'membership_num_terms' => 2,
      'is_active' => 1,
      'financial_type_id' => 2,
     );
    $result = civicrm_api($this->_entity, 'create', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertEquals($result['values'][$result['id']]['membership_num_terms'], 2);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    civicrm_api($this->_entity, 'delete', array('version' => 3, 'id' => $result['id']));
  }
  public function testGetPriceFieldValuewithMultipleTerms() {
    $params1 = array(
      'version' => 3,
      'price_field_id' => $this->priceFieldID1,
      'membership_type_id' =>  $this->_membershipTypeID,
      'name' => 'memType1',
      'label' => 'memType1',
      'amount' => 90,
      'membership_num_terms' => 2,
      'is_active' => 1,
      'financial_type_id' => 2,
     );
    $params2 = array(
      'version' => 3,
      'price_field_id' => $this->priceFieldID1,
      'membership_type_id' =>  $this->_membershipTypeID,
      'name' => 'memType2',
      'label' => 'memType2',
      'amount' => 120,
      'membership_num_terms' => 3,
      'is_active' => 1,
      'financial_type_id' => 2,
     );
    $result1 = civicrm_api($this->_entity, 'create', $params1);
    $result2 = civicrm_api($this->_entity, 'create', $params2);
    $result = civicrm_api($this->_entity, 'get', array( 'version' => 3, 'price_field_id' =>$this->priceFieldID1 ));
    $this->assertAPISuccess($result, 'In line ' . __LINE__);
    $this->assertEquals(2, $result['count'], 'In line ' . __LINE__);
    civicrm_api($this->_entity,'delete', array('version' => 3, 'id' => $result1['id']));
    civicrm_api($this->_entity,'delete', array('version' => 3, 'id' => $result2['id']));
  }
}

