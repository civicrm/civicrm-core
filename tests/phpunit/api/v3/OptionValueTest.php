<?php
/*
 +--------------------------------------------------------------------+
| CiviCRM version 4.7                                                |
+--------------------------------------------------------------------+
| Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * Class api_v3_OptionValueTest
 */
class api_v3_OptionValueTest extends CiviUnitTestCase {
  protected $_apiversion = 3;

  public function setUp() {
    parent::setUp();
    $this->useTransaction(TRUE);
  }

  public function testGetCount() {
    $result = $this->callAPISuccess('option_value', 'getcount', array());
    $this->assertGreaterThan(100, $result);
  }

  public function testGetOptionValueByID() {
    $result = $this->callAPISuccess('option_value', 'get', array('id' => 1));
    $this->assertEquals(1, $result['count']);
    $this->assertEquals(1, $result['id']);
  }

  public function testGetOptionValueByValue() {
    $result = $this->callAPISuccess('option_value', 'get', array('option_group_id' => 1, 'value' => '1'));
    $this->assertEquals(1, $result['count']);
    $this->assertEquals(1, $result['id']);
  }

  /**
   * Test limit param.
   */
  public function testGetOptionValueLimit() {
    $params = array();
    $result = $this->callAPISuccess('option_value', 'get', $params);
    $this->assertGreaterThan(1, $result['count'], "Check more than one exists In line " . __LINE__);
    $params['options']['limit'] = 1;
    $result = $this->callAPISuccess('option_value', 'get', $params);
    $this->assertEquals(1, $result['count'], "Check only 1 retrieved " . __LINE__);
  }

  /**
   * Test offset param.
   */
  public function testGetOptionValueOffSet() {

    $result = $this->callAPISuccess('option_value', 'get', array(
      'option_group_id' => 1,
      'value' => '1',
    ));
    $result2 = $this->callAPISuccess('option_value', 'get', array(
      'option_group_id' => 1,
      'value' => '1',
      'options' => array('offset' => 1),
    ));
    $this->assertGreaterThan($result2['count'], $result['count']);
  }

  /**
   * Test offset param.
   */
  public function testGetSingleValueOptionValueSort() {
    $description = "Demonstrates use of Sort param (available in many api functions). Also, getsingle.";
    $subfile = 'SortOption';
    $result = $this->callAPISuccess('option_value', 'getsingle', array(
      'option_group_id' => 1,
      'options' => array(
        'sort' => 'label ASC',
        'limit' => 1,
      ),
    ));
    $params = array(
      'option_group_id' => 1,
      'options' => array(
        'sort' => 'label DESC',
        'limit' => 1,
      ),
    );
    $result2 = $this->callAPIAndDocument('option_value', 'getsingle', $params, __FUNCTION__, __FILE__, $description, $subfile);
    $this->assertGreaterThan($result['label'], $result2['label']);
  }

  /**
   * Try to emulate a pagination: fetch the first page of 10 options, then fetch the second page with an offset of 9 (instead of 10) and check the start of the second page is the end of the 1st one.
   */
  public function testGetValueOptionPagination() {
    $pageSize = 10;
    $page1 = $this->callAPISuccess('option_value', 'get', array('options' => array('limit' => $pageSize)));
    $page2 = $this->callAPISuccess('option_value', 'get', array(
      'options' => array(
        'limit' => $pageSize,
        // if you use it for pagination, option.offset=pageSize*pageNumber
        'offset' => $pageSize - 1,
      ),
    ));
    $this->assertEquals($pageSize, $page1['count'], "Check only 10 retrieved in the 1st page " . __LINE__);
    $this->assertEquals($pageSize, $page2['count'], "Check only 10 retrieved in the 2nd page " . __LINE__);

    $last = array_pop($page1['values']);
    $first = array_shift($page2['values']);

    $this->assertEquals($first, $last, "the first item of the second page should be the last of the 1st page" . __LINE__);
  }

  public function testGetOptionGroup() {
    $params = array('option_group_id' => 1);
    $result = $this->callAPIAndDocument('option_value', 'get', $params, __FUNCTION__, __FILE__);
    $this->assertGreaterThan(1, $result['count']);
  }

  /**
   * Test that using option_group_name returns more than 1 & less than all
   */
  public function testGetOptionGroupByName() {
    $activityTypesParams = array('option_group_name' => 'activity_type', 'option.limit' => 100);
    $params = array('option.limit' => 100);
    $activityTypes = $this->callAPISuccess('option_value', 'get', $activityTypesParams);
    $result = $this->callAPISuccess('option_value', 'get', $params);
    $this->assertGreaterThan(1, $activityTypes['count']);
    $this->assertGreaterThan($activityTypes['count'], $result['count']);
  }

  public function testGetOptionDoesNotExist() {
    $result = $this->callAPISuccess('option_value', 'get', array('label' => 'FSIGUBSFGOMUUBSFGMOOUUBSFGMOOBUFSGMOOIIB'));
    $this->assertEquals(0, $result['count']);
  }

  /**
   * Check that domain_id is honoured.
   */
  public function testCreateOptionSpecifyDomain() {
    $result = $this->callAPISuccess('option_group', 'get', array(
      'name' => 'from_email_address',
      'sequential' => 1,
      'api.option_value.create' => array('domain_id' => 2, 'name' => 'my@y.com'),
    ));

    $optionValueId = $result['values'][0]['api.option_value.create']['id'];
    $domain_id = $this->callAPISuccess('option_value', 'getvalue', array(
      'id' => $optionValueId,
      'return' => 'domain_id',
    ));
    $this->assertEquals(2, $domain_id);
  }

  /**
   * Check that component_id is honoured.
   */
  public function testCreateOptionSpecifyComponentID() {
    $result = $this->callAPISuccess('option_group', 'get', array(
      'name' => 'from_email_address',
      'sequential' => 1,
      'api.option_value.create' => array('component_id' => 2, 'name' => 'my@y.com'),
    ));
    $this->assertAPISuccess($result);
    $optionValueId = $result['values'][0]['api.option_value.create']['id'];
    $component_id = $this->callAPISuccess('option_value', 'getvalue', array(
      'id' => $optionValueId,
      'return' => 'component_id',
    ));
    $this->assertEquals(2, $component_id);
  }

  /**
   * Check that component  continues to be honoured.
   */
  public function testCreateOptionSpecifyComponent() {
    $result = $this->callAPISuccess('option_group', 'get', array(
      'name' => 'from_email_address',
      'sequential' => 1,
      'api.option_value.create' => array(
        'component_id' => 'CiviContribute',
        'name' => 'my@y.com',
      ),

    ));
    $this->assertAPISuccess($result);
    $optionValueId = $result['values'][0]['api.option_value.create']['id'];
    $component_id = $this->callAPISuccess('option_value', 'getvalue', array(
      'id' => $optionValueId,
      'return' => 'component_id',
    ));
    $this->assertEquals(2, $component_id);
  }

  /**
   * Check that component string is honoured.
   */
  public function testCreateOptionSpecifyComponentString() {
    $result = $this->callAPISuccess('option_group', 'get', array(
      'name' => 'from_email_address',
      'sequential' => 1,
      'api.option_value.create' => array(
        'component_id' => 'CiviContribute',
        'name' => 'my@y.com',
      ),

    ));
    $this->assertAPISuccess($result);
    $optionValueId = $result['values'][0]['api.option_value.create']['id'];
    $component_id = $this->callAPISuccess('option_value', 'getvalue', array(
      'id' => $optionValueId,
      'return' => 'component_id',
    ));
    $this->assertEquals(2, $component_id);
  }

  /**
   * Check that domain_id is honoured.
   */
  public function testCRM12133CreateOptionWeightNoValue() {
    $optionGroup = $this->callAPISuccess(
      'option_group', 'get', array(
        'name' => 'gender',
        'sequential' => 1,
      )
    );
    $this->assertAPISuccess($optionGroup);
    $params = array(
      'option_group_id' => $optionGroup['id'],
      'label' => 'my@y.com',
      'weight' => 3,
    );
    $optionValue = $this->callAPISuccess('option_value', 'create', $params);
    $this->assertAPISuccess($optionValue);
    $params['weight'] = 4;
    $optionValue2 = $this->callAPISuccess('option_value', 'create', $params);
    $this->assertAPISuccess($optionValue2);
    $options = $this->callAPISuccess('option_value', 'get', array('option_group_id' => $optionGroup['id']));
    $this->assertNotEquals($options['values'][$optionValue['id']]['value'], $options['values'][$optionValue2['id']]['value']);

    //cleanup
    $this->callAPISuccess('option_value', 'delete', array('id' => $optionValue['id']));
    $this->callAPISuccess('option_value', 'delete', array('id' => $optionValue2['id']));
  }

  /**
   * Check that domain_id is honoured.
   */
  public function testCreateOptionNoName() {
    $optionGroup = $this->callAPISuccess('option_group', 'get', array(
      'name' => 'gender',
      'sequential' => 1,
    ));

    $params = array('option_group_id' => $optionGroup['id'], 'label' => 'my@y.com');
    $optionValue = $this->callAPISuccess('option_value', 'create', $params);
    $this->assertAPISuccess($optionValue);
    $this->getAndCheck($params, $optionValue['id'], 'option_value');
  }

  /**
   * Check that pseudoconstant reflects new value added.
   */
  public function testCRM11876CreateOptionPseudoConstantUpdated() {
    $optionGroupID = $this->callAPISuccess('option_group', 'getvalue', array(
      'name' => 'payment_instrument',
      'return' => 'id',
    ));
    $newOption = (string) time();
    $apiResult = $this->callAPISuccess('option_value', 'create', array(
      'option_group_id' => $optionGroupID,
      'label' => $newOption,
    ));

    $fields = $this->callAPISuccess('contribution', 'getoptions', array('field' => 'payment_instrument_id'));
    $this->assertTrue(in_array($newOption, $fields['values']));

    $this->callAPISuccess('option_value', 'delete', array('id' => $apiResult['id']));

    $fields = $this->callAPISuccess('contribution', 'getoptions', array('field' => 'payment_instrument_id'));
    $this->assertFalse(in_array($newOption, $fields['values']));
  }


  /**
   * Update option value with 'id' parameter and the value to update
   * and not passing option group id
   */
  public function testUpdateOptionValueNoGroupId() {
    // create a option group
    $og = $this->callAPISuccess('option_group', 'create', array('name' => 'our test Option Group', 'is_active' => 1));
    // create a option value
    $ov = $this->callAPISuccess('option_value', 'create',
      array('option_group_id' => $og['id'], 'label' => 'test option value')
    );
    // update option value without 'option_group_id'
    $res = $this->callAPISuccess('option_value', 'create', array('id' => $ov['id'], 'is_active' => 0));
    $val = $this->callAPISuccess('option_value', 'getvalue', array(
      'id' => $ov['id'],
      'return' => 'is_active',
    ));
    $this->assertEquals($val, 0, "update with no group id is not proper" . __LINE__);
  }

  /**
   * Update option value with 'id' parameter and the value to update
   * and as well as option group id
   */
  public function testUpdateOptionValueWithGroupId() {
    // create a option group
    $og = $this->callAPISuccess('option_group', 'create', array(
      'name' => 'our test Option Group for with group id',
      'is_active' => 1,
    ));
    // create a option value
    $ov = $this->callAPISuccess('option_value', 'create',
      array('option_group_id' => $og['id'], 'label' => 'test option value')
    );
    // update option value without 'option_group_id'
    $this->callAPISuccess('option_value', 'create', array(
      'id' => $ov['id'],
      'option_group_id' => $og['id'],
      'is_active' => 0,
    ));
    $val = $this->callAPISuccess('option_value', 'getvalue', array(
      'id' => $ov['id'],
      'return' => 'is_active',
    ));
    $this->assertEquals($val, 0, "update with group id is not proper " . __LINE__);
  }

}
