<?php

/*
 +--------------------------------------------------------------------+
| CiviCRM version 4.3                                                |
+--------------------------------------------------------------------+
| Copyright CiviCRM LLC (c) 2004-2013                                |
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
 *  Include class definitions
 */
require_once 'tests/phpunit/CiviTest/CiviUnitTestCase.php';


/**
 *  Test APIv3 civicrm_create_custom_group
 *
 *  @package   CiviCRM
 */
class api_v3_CustomFieldTest extends CiviUnitTestCase {
  protected $_apiversion;
  public $_eNoticeCompliant = TRUE;
  function get_info() {
    return array(
      'name' => 'Custom Field Create',
      'description' => 'Test all Custom Field Create API methods.',
      'group' => 'CiviCRM API Tests',
    );
  }

  function setUp() {
    $this->_apiversion = 3;
    parent::setUp();
  }

  function tearDown() {
    $tablesToTruncate = array(
      'civicrm_custom_group', 'civicrm_custom_field',
    );
    // true tells quickCleanup to drop any tables that might have been created in the test
    $this->quickCleanup($tablesToTruncate, TRUE);
  }

  /**
   * check with no array
   */
  function testCustomFieldCreateNoArray() {
    $fieldParams = NULL;

    $customField = $this->callAPIFailure('custom_field', 'create', $fieldParams);
    $this->assertEquals($customField['error_message'], 'Input variable `params` is not an array');
  }

  /**
   * check with no label
   */
  function testCustomFieldCreateWithoutLabel() {
    $customGroup = $this->customGroupCreate('Individual', 'text_test_group', 3);
    $params = array(
      'custom_group_id' => $customGroup['id'],
      'name' => 'test_textfield2',
      'html_type' => 'Text',
      'data_type' => 'String',
      'default_value' => 'abc',
      'weight' => 4,
      'is_required' => 1,
      'is_searchable' => 0,
      'is_active' => 1,
      'version' => $this->_apiversion,
    );

    $customField = $this->callAPIFailure('custom_field', 'create', $params);
    $this->assertEquals($customField['error_message'], 'Mandatory key(s) missing from params array: label');
  }

  /**
   * check with edit
   */
  function testCustomFieldCreateWithEdit() {
    $customGroup = $this->customGroupCreate('Individual', 'text_test_group', 3);
    $params = array(
      'custom_group_id' => $customGroup['id'],
      'name' => 'test_textfield2',
      'label' => 'Name1',
      'html_type' => 'Text',
      'data_type' => 'String',
      'default_value' => 'abc',
      'weight' => 4,
      'is_required' => 1,
      'is_searchable' => 0,
      'is_active' => 1,
      'version' => $this->_apiversion,
    );

    $customField  = civicrm_api('custom_field', 'create', $params);
    $params['id'] = $customField['id'];
    $customField  = civicrm_api('custom_field', 'create', $params);

    $this->assertEquals($customField['is_error'], 0, 'in line ' . __LINE__);
    $this->assertNotNull($customField['id'], 'in line ' . __LINE__);
  }

  /**
   * check without groupId
   */
  function testCustomFieldCreateWithoutGroupID() {
    $fieldParams = array(
      'name' => 'test_textfield1',
      'label' => 'Name',
      'html_type' => 'Text',
      'data_type' => 'String',
      'default_value' => 'abc',
      'weight' => 4,
      'is_required' => 1,
      'is_searchable' => 0,
      'is_active' => 1,
      'version' => $this->_apiversion,
    );

    $customField = $this->callAPIFailure('custom_field', 'create', $fieldParams);
    $this->assertEquals($customField['error_message'], 'Mandatory key(s) missing from params array: custom_group_id');
  }

  /**
   * Check for Each data type: loop through available form input types
   **/
  function testCustomFieldCreateAllAvailableFormInputs() {
    $gid = $this->customGroupCreate('Individual', 'testAllFormInputs');

    $dtype = CRM_Core_BAO_CustomField::dataType();
    $htype = CRM_Core_BAO_CustomField::dataToHtml();

    $n = 0;
    foreach ($dtype as $dkey => $dvalue) {
      foreach ($htype[$n] as $hkey => $hvalue) {
        //echo $dkey."][".$hvalue."\n";
        $this->_loopingCustomFieldCreateTest($this->_buildParams($gid['id'], $hvalue, $dkey));
      }
      $n++;
    }
  }
/*
 * Can't figure out the point of this?
 */
  function _loopingCustomFieldCreateTest($params) {
    $customField = civicrm_api('custom_field', 'create', $params);
    $this->assertEquals(0, $customField['is_error'], var_export($customField, TRUE));
    $this->assertNotNull($customField['id']);
    $this->getAndCheck($params, $customField['id'], 'CustomField');
  }

  function _buildParams($gid, $htype, $dtype) {
    $params = $this->_buildBasicParams($gid, $htype, $dtype);
    /* //Not Working for any type. Maybe redundant with testCustomFieldCreateWithOptionValues()
        if ($htype == 'Multi-Select')
            $params = array_merge($params, array(
                         'option_label'    => array( 'Label1','Label2'),
                         'option_value'    => array( 'val1', 'val2' ),
                         'option_weight'   => array( 1, 2),
                         'option_status'   => array( 1, 1),
                         ));
*/



    return $params;
  }

  function _buildBasicParams($gid, $htype, $dtype) {
    return array(
      'custom_group_id' => $gid,
      'label' => $dtype . $htype,
      'html_type' => $htype,
      'data_type' => $dtype,
      'weight' => 4,
      'is_required' => 0,
      'is_searchable' => 0,
      'is_active' => 1,
      'version' => $this->_apiversion,
    );
  }

  /**
   *  Test  using example code
   */
  /*function testCustomFieldCreateExample( )
    {


        $customGroup = $this->customGroupCreate('Individual','date_test_group',3);
        require_once 'api/v3/examples/CustomFieldCreate.php';
        $result = custom_field_create_example();
        $expectedResult = custom_field_create_expectedresult();
        $this->assertEquals($result,$expectedResult);
    }*/

  /**
   * check with data type - Options with option_values
   */
  function testCustomFieldCreateWithEmptyOptionGroup() {
    $customGroup = $this->customGroupCreate('Contact', 'select_test_group', 3);
    $params = array(
      'custom_group_id' => $customGroup['id'],
      'label' => 'Country',
      'html_type' => 'Select',
      'data_type' => 'String',
      'weight' => 4,
      'is_required' => 1,
      'is_searchable' => 0,
      'is_active' => 1,
      'version' => $this->_apiversion,
    );

    $customField = civicrm_api('custom_field', 'create', $params);
    $this->assertAPISuccess($customField);
    $this->assertNotNull($customField['id']);
    $optionGroupID = civicrm_api('custom_field', 'getvalue', array(
      'version' => 3,
      'id' => $customField['id'],
      'return' => 'option_group_id',
    ));

    $this->assertTrue(is_numeric($optionGroupID) && ($optionGroupID > 0));
    $optionGroup = civicrm_api('option_group', 'getsingle', array(
      'version' => 3, 'id' => $optionGroupID));
    $this->assertEquals($optionGroup['title'],'Country');
    $optionValueCount = civicrm_api('option_value', 'getcount', array(
      'version' => 3, 'option_group_id' => $optionGroupID));
    $this->assertEquals(0, $optionValueCount);
  }


  /**
   * Test custom field get works & return param works
   */
  function testCustomFieldGetReturnOptions(){
    $customGroup = $this->customGroupCreate('Individual', 'test_group');
    $customField = $this->customFieldCreate($customGroup['id'], 'test_name');

    $result = civicrm_api('custom_field', 'getsingle', array(
      'version' => 3,
      'id' => $customField['id'],
      'return' => 'data_type',
    ));
    $this->assertTrue(array_key_exists('data_type', $result));
    $this->assertFalse(array_key_exists('custom_group_id', $result));
  }

  /**
   * Test custom field get works & return param works
   */
  function testCustomFieldGetReturnArray(){
    $customGroup = $this->customGroupCreate('Individual', 'test_group');
    $customField = $this->customFieldCreate($customGroup['id'], 'test_name');

    $result = civicrm_api('custom_field', 'getsingle', array(
      'version' => 3,
      'id' => $customField['id'],
      'return' => array('data_type'),
    ));
    $this->assertTrue(array_key_exists('data_type', $result));
    $this->assertFalse(array_key_exists('custom_group_id', $result));
  }

  /**
   * Test custom field get works & return param works
   */
  function testCustomFieldGetReturnTwoOptions(){
    $customGroup = $this->customGroupCreate('Individual', 'test_group');
    $customField = $this->customFieldCreate($customGroup['id'], 'test_name');

    $result = civicrm_api('custom_field', 'getsingle', array(
      'version' => 3,
      'id' => $customField['id'],
      'return' => 'data_type, custom_group_id',
    ));
    $this->assertTrue(array_key_exists('data_type', $result));
    $this->assertTrue(array_key_exists('custom_group_id', $result));
    $this->assertFalse(array_key_exists('label', $result));
  }

  function testCustomFieldCreateWithOptionValues() {
    $customGroup = $this->customGroupCreate('Contact', 'select_test_group', 3);

    $option_values = array(
      array('weight' => 1,
        'label' => 'Label1',
        'value' => 1,
        'is_active' => 1,
      ),
      array(
        'weight' => 2,
        'label' => 'Label2',
        'value' => 2,
        'is_active' => 1,
      ),
    );

    $params = array(
      'custom_group_id' => $customGroup['id'],
      'label' => 'Our special field',
      'html_type' => 'Select',
      'data_type' => 'String',
      'weight' => 4,
      'is_required' => 1,
      'is_searchable' => 0,
      'is_active' => 1,
      'option_values' => $option_values,
      'version' => $this->_apiversion,
    );

    $customField = civicrm_api('custom_field', 'create', $params);

    $this->assertAPISuccess($customField);
    $this->assertNotNull($customField['id']);
    $getFieldsParams = array(
      'options' => array('get_options' => 'custom_' . $customField['id']),
      'version' => 3,
      'action' => 'create',
    );
    $description  = "Demonstrate retrieving metadata with custom field options";
    $subfile = "GetFieldsOptions";
    $fields = civicrm_api('contact', 'getfields', $getFieldsParams);
    $this->documentMe($getFieldsParams, $fields, __FUNCTION__, 'ContactTest.php', $description,$subfile,'GetFields');
    $this->assertArrayHasKey('options', $fields['values']['custom_' . $customField['id']]);
    $this->assertEquals('Label1', $fields['values']['custom_' . $customField['id']]['options'][1]);
    $getOptionsArray = array(
      'field' => 'custom_' . $customField['id'],
      'version' => 3,
    );
    $description = "Demonstrates retrieving options for a custom field";
    $subfile = "GetOptions";
    $result = civicrm_api('contact', 'getoptions', $getOptionsArray);
    $this->assertEquals('Label1', $result['values'][1]);
    $this->documentMe($getOptionsArray, $result, __FUNCTION__, 'ContactTest.php', $description, '', 'getoptions');
  }

  ///////////////// civicrm_custom_field_delete methods

  /**
   * check with no array
   */
  function testCustomFieldDeleteNoArray() {
    $params = NULL;
    $customField = $this->callAPIFailure('custom_field', 'delete', $params);
    $this->assertEquals($customField['error_message'], 'Input variable `params` is not an array');
  }

  /**
   * check without Field ID
   */
  function testCustomFieldDeleteWithoutFieldID() {
    $params = array('version' => $this->_apiversion);
    $customField = $this->callAPIFailure('custom_field', 'delete', $params);
    $this->assertEquals($customField['error_message'], 'Mandatory key(s) missing from params array: id');
  }

  /**
   * check without valid array
   */
  function testCustomFieldDelete() {
    $customGroup = $this->customGroupCreate('Individual', 'test_group');
    $customField = $this->customFieldCreate($customGroup['id'], 'test_name');
    $this->assertNotNull($customField['id'], 'in line ' . __LINE__);

    $params = array(
      'version' => $this->_apiversion,
      'id' => $customField['id'],
    );
    $result = civicrm_api('custom_field', 'delete', $params);
    $this->documentMe($params, $result, __FUNCTION__, __FILE__);

    $this->assertAPISuccess($result, 'in line ' . __LINE__);
  }

  /**
   * check for Option Value
   */
  function testCustomFieldOptionValueDelete() {
    $customGroup = $this->customGroupCreate('Contact', 'ABC');
    $customOptionValueFields = $this->customFieldOptionValueCreate($customGroup, 'fieldABC');
    $customOptionValueFields['version'] = $this->_apiversion;
    $params = array(
      'version' => $this->_apiversion,
      'id' => $customOptionValueFields,
    );

    $customField = civicrm_api('custom_field', 'delete', $customOptionValueFields);
    $this->assertAPISuccess($customField);
  }
}

