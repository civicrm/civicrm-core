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
    $customGroup = $this->customGroupCreate(array('extends' => 'Individual', 'title' => 'text_test_group'));
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
    );

    $customField = $this->callAPIFailure('custom_field', 'create', $params);
    $this->assertEquals($customField['error_message'], 'Mandatory key(s) missing from params array: label');
  }

  /**
   * check with edit
   */
  function testCustomFieldCreateWithEdit() {
    $customGroup = $this->customGroupCreate(array('extends' => 'Individual', 'title' => 'text_test_group'));
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
    );

    $customField  = $this->callAPIAndDocument('custom_field', 'create', $params, __FUNCTION__, __FILE__);
    $params['id'] = $customField['id'];
    $customField  = $this->callAPISuccess('custom_field', 'create', $params);

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

    );

    $customField = $this->callAPIFailure('custom_field', 'create', $fieldParams);
    $this->assertEquals($customField['error_message'], 'Mandatory key(s) missing from params array: custom_group_id');
  }

  /**
   * Check for Each data type: loop through available form input types
   **/
  function testCustomFieldCreateAllAvailableFormInputs() {
    $gid = $this->customGroupCreate(array('extends' => 'Individual', 'title' => 'testAllFormInputs'));

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
    $customField = $this->callAPISuccess('custom_field', 'create', $params);
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
    $customGroup = $this->customGroupCreate(array('extends' => 'Contact', 'title' => 'select_test_group'));
    $params = array(
      'custom_group_id' => $customGroup['id'],
      'label' => 'Country',
      'html_type' => 'Select',
      'data_type' => 'String',
      'weight' => 4,
      'is_required' => 1,
      'is_searchable' => 0,
      'is_active' => 1,
    );

    $customField = $this->callAPISuccess('custom_field', 'create', $params);
    $this->assertNotNull($customField['id']);
    $optionGroupID = $this->callAPISuccess('custom_field', 'getvalue', array(
      'id' => $customField['id'],
      'return' => 'option_group_id',
    ));

    $this->assertTrue(is_numeric($optionGroupID) && ($optionGroupID > 0));
    $optionGroup = $this->callAPISuccess('option_group', 'getsingle', array(
      'id' => $optionGroupID));
    $this->assertEquals($optionGroup['title'],'Country');
    $optionValueCount = $this->callAPISuccess('option_value', 'getcount', array(
      'option_group_id' => $optionGroupID));
    $this->assertEquals(0, $optionValueCount);
  }


  /**
   * Test custom field get works & return param works
   */
  function testCustomFieldGetReturnOptions(){
    $customGroup = $this->customGroupCreate(array('extends' => 'Individual', 'title' => 'test_group'));
    $customField = $this->customFieldCreate($customGroup['id'], 'test_name');

    $result = $this->callAPISuccess('custom_field', 'getsingle', array(
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
    $customGroup = $this->customGroupCreate(array('extends' => 'Individual', 'title' => 'test_group'));
    $customField = $this->customFieldCreate($customGroup['id'], 'test_name');

    $result = $this->callAPISuccess('custom_field', 'getsingle', array(
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
    $customGroup = $this->customGroupCreate(array('extends' => 'Individual', 'test_group'));
    $customField = $this->customFieldCreate($customGroup['id'], 'test_name');

    $result = $this->callAPISuccess('custom_field', 'getsingle', array(
           'id' => $customField['id'],
      'return' => 'data_type, custom_group_id',
    ));
    $this->assertTrue(array_key_exists('data_type', $result));
    $this->assertTrue(array_key_exists('custom_group_id', $result));
    $this->assertFalse(array_key_exists('label', $result));
  }

  function testCustomFieldCreateWithOptionValues() {
    $customGroup = $this->customGroupCreate(array('extends' => 'Contact', 'title' => 'select_test_group'));

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

    );

    $customField = $this->callAPISuccess('custom_field', 'create', $params);

    $this->assertAPISuccess($customField);
    $this->assertNotNull($customField['id']);
    $getFieldsParams = array(
      'options' => array('get_options' => 'custom_' . $customField['id']),
           'action' => 'create',
    );
    $description  = "Demonstrate retrieving metadata with custom field options";
    $subfile = "GetFieldsOptions";
    $fields = $this->callAPIAndDocument('contact', 'getfields', $getFieldsParams, __FUNCTION__, 'ContactTest.php', $description,$subfile,'GetFields');
    $this->assertArrayHasKey('options', $fields['values']['custom_' . $customField['id']]);
    $this->assertEquals('Label1', $fields['values']['custom_' . $customField['id']]['options'][1]);
    $getOptionsArray = array(
      'field' => 'custom_' . $customField['id'],
         );
    $description = "Demonstrates retrieving options for a custom field";
    $subfile = "GetOptions";
    $result = $this->callAPIAndDocument('contact', 'getoptions', $getOptionsArray, __FUNCTION__, 'ContactTest.php', $description, '', 'getoptions');
    $this->assertEquals('Label1', $result['values'][1]);
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
    $params = array();
    $customField = $this->callAPIFailure('custom_field', 'delete', $params,
      'Mandatory key(s) missing from params array: id');
  }

  /**
   * check without valid array
   */
  function testCustomFieldDelete() {
    $customGroup = $this->customGroupCreate(array('extends' => 'Individual', 'title' => 'test_group'));
    $customField = $this->customFieldCreate($customGroup['id'], 'test_name');
    $this->assertNotNull($customField['id'], 'in line ' . __LINE__);

    $params = array(
      'id' => $customField['id'],
    );
    $result = $this->callAPIAndDocument('custom_field', 'delete', $params, __FUNCTION__, __FILE__);

    $this->assertAPISuccess($result, 'in line ' . __LINE__);
  }

  /**
   * check for Option Value
   */
  function testCustomFieldOptionValueDelete() {
    $customGroup = $this->customGroupCreate(array('extends' => 'Contact', 'title' => 'ABC'));
    $customOptionValueFields = $this->customFieldOptionValueCreate($customGroup, 'fieldABC');
    $params = array(
      'id' => $customOptionValueFields,
    );

    $customField = $this->callAPISuccess('custom_field', 'delete', $customOptionValueFields);
  }
}

