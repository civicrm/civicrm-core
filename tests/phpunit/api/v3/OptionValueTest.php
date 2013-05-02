<?php


require_once 'CiviTest/CiviUnitTestCase.php';

class api_v3_OptionValueTest extends CiviUnitTestCase {
  protected $_apiversion;
  public $_eNoticeCompliant = TRUE;
  function setUp() {
    $this->_apiversion = 3;
    parent::setUp();
  }

  function tearDown() {}

  public function testGetOptionValueByID() {
    $result = civicrm_api('option_value', 'get', array('id' => 1, 'version' => $this->_apiversion));
    $this->assertEquals(0, $result['is_error'], 'In line ' . __LINE__);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertEquals(1, $result['id'], 'In line ' . __LINE__);
  }

  public function testGetOptionValueByValue() {
    $result = civicrm_api('option_value', 'get', array('option_group_id' => 1, 'value' => '1', 'version' => $this->_apiversion));
    $this->assertEquals(0, $result['is_error'], 'In line ' . __LINE__);
    $this->assertEquals(1, $result['count'], 'In line ' . __LINE__);
    $this->assertEquals(1, $result['id'], 'In line ' . __LINE__);
  }

  /**
   *  Test limit param
   */
  function testGetOptionValueLimit() {
    $params = array(
      'version' => $this->_apiversion,
    );
    $result = civicrm_api('option_value', 'getcount', $params);
    $this->assertGreaterThan(1, $result, "Check more than one exists In line " . __LINE__);
    $params['options']['limit'] = 1;
    $result = civicrm_api('option_value', 'getcount', $params);
    $this->assertEquals(1, $result, "Check only 1 retrieved " . __LINE__);
  }

  /**
   *  Test offset param
   */
  function testGetOptionValueOffSet() {

    $result = civicrm_api('option_value', 'getcount', array(
      'option_group_id' => 1,
        'value' => '1',
        'version' => $this->_apiversion,
      ));
    $result2 = civicrm_api('option_value', 'getcount', array(
      'option_group_id' => 1,
        'value' => '1',
        'version' => $this->_apiversion,
        'options' => array('offset' => 1),
      ));
    $this->assertGreaterThan($result2, $result);
  }

  /**
   *  Test offset param
   */
  function testGetSingleValueOptionValueSort() {
    $description = "demonstrates use of Sort param (available in many api functions). Also, getsingle";
    $subfile     = 'SortOption';
    $result      = civicrm_api('option_value', 'getsingle', array(
      'option_group_id' => 1,
        'version' => $this->_apiversion,
        'options' => array(
          'sort' => 'label ASC',
          'limit' => 1,
        ),
      ));
    $params = array(
      'option_group_id' => 1,
      'version' => $this->_apiversion,
      'options' => array(
        'sort' => 'label DESC',
        'limit' => 1,
      ),
    );
    $result2 = civicrm_api('option_value', 'getsingle', $params);
    $this->documentMe($params, $result2, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertGreaterThan($result['label'], $result2['label']);
  }

  /**
   * Try to emulate a pagination: fetch the first page of 10 options, then fetch the second page with an offset of 9 (instead of 10) and check the start of the second page is the end of the 1st one.
   */
  function testGetValueOptionPagination() {
    $pageSize = 10;
    $page1 = civicrm_api('option_value', 'get', array('options' => array('limit' => $pageSize),
        'version' => $this->_apiversion,
      ));
    $page2 = civicrm_api('option_value', 'get', array(
      'options' => array('limit' => $pageSize,
          // if you use it for pagination, option.offset=pageSize*pageNumber
          'offset' => $pageSize - 1,
        ),
        'version' => $this->_apiversion,
      ));
    $this->assertEquals($pageSize, $page1['count'], "Check only 10 retrieved in the 1st page " . __LINE__);
    $this->assertEquals($pageSize, $page2['count'], "Check only 10 retrieved in the 2nd page " . __LINE__);

    $last = array_pop($page1['values']);
    $first = array_shift($page2['values']);

    $this->assertEquals($first, $last, "the first item of the second page should be the last of the 1st page" . __LINE__);
  }

  public function testGetOptionGroup() {
    $params = array('option_group_id' => 1, 'version' => $this->_apiversion);
    $result = civicrm_api('option_value', 'get', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);
    $this->assertEquals(0, $result['is_error'], 'In line ' . __LINE__);
    $this->assertGreaterThan(1, $result['count'], 'In line ' . __LINE__);
  }
  /*
     * test that using option_group_name returns more than 1 & less than all
     */



  public function testGetOptionGroupByName() {
    $activityTypesParams = array('option_group_name' => 'activity_type', 'version' => $this->_apiversion, 'option.limit' => 100);
    $params              = array('version' => $this->_apiversion, 'option.limit' => 100);
    $activityTypes       = civicrm_api('option_value', 'get', $activityTypesParams);
    $result              = civicrm_api('option_value', 'get', $params);
    $this->assertEquals(0, $result['is_error'], 'In line ' . __LINE__);
    $this->assertGreaterThan(1, $activityTypes['count'], 'In line ' . __LINE__);
    $this->assertGreaterThan($activityTypes['count'], $result['count'], 'In line ' . __LINE__);
  }
  public function testGetOptionDoesNotExist() {
    $result = civicrm_api('option_value', 'get', array('label' => 'FSIGUBSFGOMUUBSFGMOOUUBSFGMOOBUFSGMOOIIB', 'version' => $this->_apiversion));
    $this->assertEquals(0, $result['is_error'], 'In line ' . __LINE__);
    $this->assertEquals(0, $result['count'], 'In line ' . __LINE__);
  }
/*
 * Check that domain_id is honoured
 */
  public function testCreateOptionSpecifyDomain() {
    $result = civicrm_api('option_group', 'get', array(
      'name' => 'from_email_address',
      'sequential' => 1,
      'version' => $this->_apiversion,
      'api.option_value.create' => array('domain_id' => 2, 'name' => 'my@y.com'),
      ));
    $this->assertAPISuccess($result);
    $optionValueId = $result['values'][0]['api.option_value.create']['id'];
    $domain_id = civicrm_api('option_value', 'getvalue', array(
      'id' => $optionValueId,
      'version' => $this->_apiversion,
      'return' => 'domain_id',
    ));
    $this->assertEquals(2, $domain_id);
  }
  /*
   * Check that component_id is honoured
  */
  public function testCreateOptionSpecifyComponentID() {
    $result = civicrm_api('option_group', 'get', array(
      'name' => 'from_email_address',
      'sequential' => 1,
      'version' => $this->_apiversion,
      'api.option_value.create' => array('component_id' => 2, 'name' => 'my@y.com'),
    ));
    $this->assertAPISuccess($result);
    $optionValueId = $result['values'][0]['api.option_value.create']['id'];
    $component_id = civicrm_api('option_value', 'getvalue', array(
      'id' => $optionValueId,
      'version' => $this->_apiversion,
      'return' => 'component_id',
    ));
    $this->assertEquals(2, $component_id);
  }
  /*
   * Check that component  continues to be honoured
  */
  public function testCreateOptionSpecifyComponent() {
    $result = civicrm_api('option_group', 'get', array(
      'name' => 'from_email_address',
      'sequential' => 1,
      'version' => $this->_apiversion,
      'api.option_value.create' => array(
        'component_id' => 'CiviContribute',
        'name' => 'my@y.com'
       ),

    ));
    $this->assertAPISuccess($result);
    $optionValueId = $result['values'][0]['api.option_value.create']['id'];
    $component_id = civicrm_api('option_value', 'getvalue', array(
      'id' => $optionValueId,
      'version' => $this->_apiversion,
      'return' => 'component_id',
    ));
    $this->assertEquals(2, $component_id);
  }
  /*
   * Check that component string is honoured
  */
  public function testCreateOptionSpecifyComponentString() {
    $result = civicrm_api('option_group', 'get', array(
      'name' => 'from_email_address',
      'sequential' => 1,
      'version' => $this->_apiversion,
      'api.option_value.create' => array(
        'component_id' => 'CiviContribute',
        'name' => 'my@y.com'),

    ));
    $this->assertAPISuccess($result);
    $optionValueId = $result['values'][0]['api.option_value.create']['id'];
    $component_id = civicrm_api('option_value', 'getvalue', array(
      'id' => $optionValueId,
      'version' => $this->_apiversion,
      'return' => 'component_id',
    ));
    $this->assertEquals(2, $component_id);
  }
  /*
   * Check that domain_id is honoured
  */
  public function testCRM12133CreateOptionWeightNoValue() {
    $optionGroup = civicrm_api(
      'option_group', 'get', array(
      'name' => 'gender',
      'sequential' => 1,
      'version' => $this->_apiversion,
    ));
    $this->assertAPISuccess($optionGroup);
    $params = array(
      'option_group_id' => $optionGroup['id'],
      'label' => 'my@y.com',
      'version' => $this->_apiversion,
      'weight' => 3,
    );
    $optionValue = civicrm_api('option_value', 'create',  $params);
    $this->assertAPISuccess($optionValue);
    $params['weight'] = 4;
    $optionValue2 = civicrm_api('option_value', 'create',  $params );
    $this->assertAPISuccess($optionValue2);
    $options = civicrm_api('option_value', 'get', array('version' => 3, 'option_group_id' => $optionGroup['id']));
    $this->assertNotEquals($options['values'][$optionValue['id']]['value'], $options['values'][$optionValue2['id']]['value']);

  //cleanup
    civicrm_api('option_value', 'delete', array('version' => 3, 'id' => $optionValue['id']));
    civicrm_api('option_value', 'delete', array('version' => 3, 'id' => $optionValue2['id']));
  }

  /*
   * Check that domain_id is honoured
  */
  public function testCreateOptionNoName() {
    $optionGroup = civicrm_api('option_group', 'get', array(
      'name' => 'gender',
      'sequential' => 1,
      'version' => $this->_apiversion,
    ));

    $params = array('option_group_id' => $optionGroup['id'], 'label' => 'my@y.com', 'version' => $this->_apiversion);
    $optionValue = civicrm_api('option_value', 'create',  $params);
    $this->assertAPISuccess($optionValue);
    $this->getAndCheck($params, $optionValue['id'], 'option_value');
  }
  /*
   * Check that pseudoconstant reflects new value added
  * and deleted
  */
  public function testCRM11876CreateOptionPseudoConstantUpdated() {
    $optionGroupID = civicrm_api('option_group', 'getvalue', array(
      'version' => $this->_apiversion,
      'name' => 'payment_instrument',
      'return' => 'id',)
    );
    $apiResult = civicrm_api('option_value', 'create', array(
      'option_group_id' =>  $optionGroupID,
      'label' => 'newest',
      'version' => $this->_apiversion,
    ));

    $this->assertAPISuccess($apiResult);
    $fields = civicrm_api('contribution', 'getoptions', array(
      'version' => $this->_apiversion,
      'field' => 'payment_instrument',
      )
    );
    $this->assertTrue(in_array('newest', $fields['values']));
    $deleteResult = civicrm_api('option_value', 'delete', array('id' => $apiResult['id'], 'version' => $this->_apiversion));
    $this->assertAPISuccess($deleteResult);
    $fields = civicrm_api('contribution', 'getoptions', array(
      'version' => $this->_apiversion,
      'field' => 'payment_instrument',
      )
    );
    $this->assertFalse(in_array('newest', $fields['values']));
  }
}

