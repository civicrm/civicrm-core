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
 *  Test APIv3 civicrm_create_custom_group
 *
 * @package   CiviCRM
 * @group headless
 */
class api_v3_CustomFieldTest extends CiviUnitTestCase {

  /**
   * Clean up after test.
   *
   * @throws \CRM_Core_Exception
   */
  public function tearDown() {
    $this->quickCleanup([
      'civicrm_contact',
      'civicrm_file',
      'civicrm_entity_file',
    ], TRUE);
    parent::tearDown();
  }

  /**
   * Check with no array.
   */
  public function testCustomFieldCreateNoArray() {
    $fieldParams = NULL;

    $customField = $this->callAPIFailure('custom_field', 'create', $fieldParams);
    $this->assertEquals($customField['error_message'], 'Input variable `params` is not an array');
  }

  /**
   * Check with no label.
   */
  public function testCustomFieldCreateWithoutLabel() {
    $customGroup = $this->customGroupCreate(['extends' => 'Individual', 'title' => 'text_test_group']);
    $params = [
      'custom_group_id' => $customGroup['id'],
      'name' => 'test_textfield2',
      'html_type' => 'Text',
      'data_type' => 'String',
      'default_value' => 'abc',
      'weight' => 4,
      'is_required' => 1,
      'is_searchable' => 0,
      'is_active' => 1,
    ];

    $customField = $this->callAPIFailure('custom_field', 'create', $params);
    $this->assertEquals($customField['error_message'], 'Mandatory key(s) missing from params array: label');
  }

  /**
   * Check with edit.
   */
  public function testCustomFieldCreateWithEdit() {
    $customGroup = $this->customGroupCreate(['extends' => 'Individual', 'title' => 'text_test_group']);
    $params = [
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
    ];

    $customField = $this->callAPIAndDocument('custom_field', 'create', $params, __FUNCTION__, __FILE__);
    $params['id'] = $customField['id'];
    $customField = $this->callAPISuccess('custom_field', 'create', $params);

    $this->assertNotNull($customField['id']);
  }

  /**
   * Check without groupId.
   */
  public function testCustomFieldCreateWithoutGroupID() {
    $fieldParams = [
      'name' => 'test_textfield1',
      'label' => 'Name',
      'html_type' => 'Text',
      'data_type' => 'String',
      'default_value' => 'abc',
      'weight' => 4,
      'is_required' => 1,
      'is_searchable' => 0,
      'is_active' => 1,

    ];

    $customField = $this->callAPIFailure('custom_field', 'create', $fieldParams);
    $this->assertEquals($customField['error_message'], 'Mandatory key(s) missing from params array: custom_group_id');
  }

  /**
   * Check for Each data type: loop through available form input types
   */
  public function testCustomFieldCreateAllAvailableFormInputs() {
    $gid = $this->customGroupCreate(['extends' => 'Individual', 'title' => 'testAllFormInputs']);

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

  /**
   * @param array $params
   */
  public function _loopingCustomFieldCreateTest($params) {
    $customField = $this->callAPISuccess('custom_field', 'create', $params);
    $this->assertNotNull($customField['id']);
    $this->getAndCheck($params, $customField['id'], 'CustomField');
  }

  /**
   * @param int $gid
   * @param $htype
   * @param $dtype
   *
   * @return array
   */
  public function _buildParams($gid, $htype, $dtype) {
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

  /**
   * @param int $gid
   * @param $htype
   * @param $dtype
   *
   * @return array
   */
  public function _buildBasicParams($gid, $htype, $dtype) {
    return [
      'custom_group_id' => $gid,
      'label' => $dtype . $htype,
      'html_type' => $htype,
      'data_type' => $dtype,
      'weight' => 4,
      'is_required' => 0,
      'is_searchable' => 0,
      'is_active' => 1,

    ];
  }

  /**
   * Test  using example code.
   */
  /*function testCustomFieldCreateExample( )
  {

  $customGroup = $this->customGroupCreate('Individual','date_test_group',3);
  require_once 'api/v3/examples/CustomField/Create.php';
  $result = custom_field_create_example();
  $expectedResult = custom_field_create_expectedresult();
  $this->assertEquals($result,$expectedResult);
  }*/

  /**
   * Check with data type - Options with option_values
   */
  public function testCustomFieldCreateWithEmptyOptionGroup() {
    $customGroup = $this->customGroupCreate(['extends' => 'Contact', 'title' => 'select_test_group']);
    $params = [
      'custom_group_id' => $customGroup['id'],
      'label' => 'Country',
      'html_type' => 'Select',
      'data_type' => 'String',
      'weight' => 4,
      'is_required' => 1,
      'is_searchable' => 0,
      'is_active' => 1,
    ];

    $customField = $this->callAPISuccess('custom_field', 'create', $params);
    $this->assertNotNull($customField['id']);
    $optionGroupID = $this->callAPISuccess('custom_field', 'getvalue', [
      'id' => $customField['id'],
      'return' => 'option_group_id',
    ]);

    $this->assertTrue(is_numeric($optionGroupID) && ($optionGroupID > 0));
    $optionGroup = $this->callAPISuccess('option_group', 'getsingle', [
      'id' => $optionGroupID,
    ]);
    $this->assertEquals($optionGroup['title'], 'Country');
    $optionValueCount = $this->callAPISuccess('option_value', 'getcount', [
      'option_group_id' => $optionGroupID,
    ]);
    $this->assertEquals(0, $optionValueCount);
  }

  /**
   * Check with non-ascii labels
   */
  public function testCustomFieldCreateWithNonAsciiLabel() {
    $customGroup = $this->customGroupCreate(['extends' => 'Contact', 'title' => 'select_test_group']);
    $params = [
      'custom_group_id' => $customGroup['id'],
      'label' => 'ôôôô',
      'html_type' => 'Select',
      'data_type' => 'String',
      'weight' => 4,
      'is_required' => 1,
      'is_searchable' => 0,
      'is_active' => 1,
    ];
    $customField = $this->callAPISuccess('custom_field', 'create', $params);
    $this->assertNotNull($customField['id']);
    $params['label'] = 'ààà';
    $customField = $this->callAPISuccess('custom_field', 'create', $params);
    $this->assertNotNull($customField['id']);
  }

  /**
   * Test custom field with existing option group.
   */
  public function testCustomFieldExistingOptionGroup() {
    $customGroup = $this->customGroupCreate(['extends' => 'Organization', 'title' => 'test_group']);
    $params = [
      'custom_group_id' => $customGroup['id'],
      // Just to say something:
      'label' => 'Organization Gender',
      'html_type' => 'Select',
      'data_type' => 'Int',
      'weight' => 4,
      'is_required' => 1,
      'is_searchable' => 0,
      'is_active' => 1,
      // Option group id 3: gender
      'option_group_id' => 3,
    ];

    $customField = $this->callAPISuccess('custom_field', 'create', $params);
    $this->assertNotNull($customField['id']);
    $optionGroupID = $this->callAPISuccess('custom_field', 'getvalue', [
      'id' => $customField['id'],
      'return' => 'option_group_id',
    ]);

    $this->assertEquals($optionGroupID, 3);
  }

  /**
   * Test adding an optionGroup to an existing field doesn't cause a fatal error.
   *
   * (this was happening due to a check running despite no existing option_group_id)
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function testUpdateCustomFieldAddOptionGroup() {
    $customGroup = $this->customGroupCreate(['extends' => 'Organization', 'title' => 'test_group']);
    $params = [
      'custom_group_id' => $customGroup['id'],
      'label' => 'Organization Gender',
      'html_type' => 'Text',
      'data_type' => 'Int',
    ];

    $customField = $this->callAPISuccess('custom_field', 'create', $params);
    $this->callAPISuccess('CustomField', 'create', [
      'option_group_id' => civicrm_api3('OptionGroup', 'getvalue', ['options' => ['limit' => 1], 'return' => 'id']),
      'id' => $customField['id'],
      'html_type' => 'Select',
    ]);
  }

  /**
   * Test custom field get works & return param works
   */
  public function testCustomFieldGetReturnOptions() {
    $customGroup = $this->customGroupCreate(['extends' => 'Individual', 'title' => 'test_group']);
    $customField = $this->customFieldCreate(['custom_group_id' => $customGroup['id']]);

    $result = $this->callAPISuccess('custom_field', 'getsingle', [
      'id' => $customField['id'],
      'return' => 'data_type',
    ]);
    $this->assertTrue(array_key_exists('data_type', $result));
    $this->assertFalse(array_key_exists('custom_group_id', $result));
  }

  /**
   * Test custom field get works & return param works
   */
  public function testCustomFieldGetReturnArray() {
    $customGroup = $this->customGroupCreate(['extends' => 'Individual', 'title' => 'test_group']);
    $customField = $this->customFieldCreate(['custom_group_id' => $customGroup['id']]);

    $result = $this->callAPISuccess('custom_field', 'getsingle', [
      'id' => $customField['id'],
      'return' => ['data_type'],
    ]);
    $this->assertTrue(array_key_exists('data_type', $result));
    $this->assertFalse(array_key_exists('custom_group_id', $result));
  }

  /**
   * Test custom field get works & return param works
   */
  public function testCustomFieldGetReturnTwoOptions() {
    $customGroup = $this->customGroupCreate(['extends' => 'Individual', 'test_group']);
    $customField = $this->customFieldCreate(['custom_group_id' => $customGroup['id']]);

    $result = $this->callAPISuccess('custom_field', 'getsingle', [
      'id' => $customField['id'],
      'return' => 'data_type, custom_group_id',
    ]);
    $this->assertTrue(array_key_exists('data_type', $result));
    $this->assertTrue(array_key_exists('custom_group_id', $result));
    $this->assertFalse(array_key_exists('label', $result));
  }

  public function testCustomFieldCreateWithOptionValues() {
    $customGroup = $this->customGroupCreate(['extends' => 'Contact', 'title' => 'select_test_group']);

    $option_values = [
      [
        'weight' => 1,
        'label' => 'Label1',
        'value' => 1,
        'is_active' => 1,
      ],
      [
        'weight' => 2,
        'label' => 'Label2',
        'value' => 2,
        'is_active' => 1,
      ],
    ];

    $params = [
      'custom_group_id' => $customGroup['id'],
      'label' => 'Our special field',
      'html_type' => 'Select',
      'data_type' => 'String',
      'weight' => 4,
      'is_required' => 1,
      'is_searchable' => 0,
      'is_active' => 1,
      'option_values' => $option_values,

    ];

    $customField = $this->callAPISuccess('custom_field', 'create', $params);

    $this->assertAPISuccess($customField);
    $this->assertNotNull($customField['id']);
    $getFieldsParams = [
      'options' => ['get_options' => 'custom_' . $customField['id']],
      'action' => 'create',
    ];
    $description = "Demonstrates retrieving metadata with custom field options.";
    $subfile = "GetFieldsOptions";
    $fields = $this->callAPIAndDocument('contact', 'getfields', $getFieldsParams, __FUNCTION__, 'ContactTest.php', $description, $subfile);
    $this->assertArrayHasKey('options', $fields['values']['custom_' . $customField['id']]);
    $this->assertEquals('Label1', $fields['values']['custom_' . $customField['id']]['options'][1]);
    $getOptionsArray = [
      'field' => 'custom_' . $customField['id'],
    ];
    $description = "Demonstrates retrieving options for a custom field.";
    $subfile = "GetOptions";
    $result = $this->callAPIAndDocument('contact', 'getoptions', $getOptionsArray, __FUNCTION__, 'ContactTest.php', $description, '');
    $this->assertEquals('Label1', $result['values'][1]);
  }

  ///////////////// civicrm_custom_field_delete methods

  /**
   * Check with no array.
   */
  public function testCustomFieldDeleteNoArray() {
    $params = NULL;
    $customField = $this->callAPIFailure('custom_field', 'delete', $params);
    $this->assertEquals($customField['error_message'], 'Input variable `params` is not an array');
  }

  /**
   * Check without Field ID.
   */
  public function testCustomFieldDeleteWithoutFieldID() {
    $params = [];
    $customField = $this->callAPIFailure('custom_field', 'delete', $params,
      'Mandatory key(s) missing from params array: id');
  }

  /**
   * Check without valid array.
   */
  public function testCustomFieldDelete() {
    $customGroup = $this->customGroupCreate(['extends' => 'Individual', 'title' => 'test_group']);
    $customField = $this->customFieldCreate(['custom_group_id' => $customGroup['id']]);
    $this->assertNotNull($customField['id']);

    $params = [
      'id' => $customField['id'],
    ];
    $result = $this->callAPIAndDocument('custom_field', 'delete', $params, __FUNCTION__, __FILE__);

    $this->assertAPISuccess($result);
  }

  /**
   * Check for Option Value.
   */
  public function testCustomFieldOptionValueDelete() {
    $customGroup = $this->customGroupCreate(['extends' => 'Contact', 'title' => 'ABC']);
    $customOptionValueFields = $this->customFieldOptionValueCreate($customGroup, 'fieldABC');
    $params = [
      'id' => $customOptionValueFields,
    ];

    $customField = $this->callAPISuccess('custom_field', 'delete', $customOptionValueFields);
  }

  /**
   * If there's one custom group for "Contact" and one for "Activity", then "Contact.getfields"
   * and "Activity.getfields" should return only their respective fields (not the other's fields),
   * and unrelated entities should return no custom fields.
   */
  public function testGetfields_CrossEntityPollution() {
    $auxEntities = ['Email', 'Address', 'LocBlock', 'Membership', 'ContributionPage', 'ReportInstance'];
    $allEntities = array_merge(['Contact', 'Activity'], $auxEntities);

    // Baseline - getfields doesn't reporting any customfields for any entities
    foreach ($allEntities as $entity) {
      $this->assertEquals(
        [],
        $this->getCustomFieldKeys($this->callAPISuccess($entity, 'getfields', [])),
        "Baseline custom fields for $entity should be empty"
      );
    }

    // Add some fields
    $contactGroup = $this->customGroupCreate(['extends' => 'Contact', 'title' => 'test_group_c']);
    $contactField = $this->customFieldCreate([
      'custom_group_id' => $contactGroup['id'],
      'label' => 'For Contacts',
    ]);
    $indivGroup = $this->customGroupCreate(['extends' => 'Individual', 'title' => 'test_group_i']);
    $indivField = $this->customFieldCreate(['custom_group_id' => $indivGroup['id'], 'label' => 'For Individuals']);
    $activityGroup = $this->customGroupCreate(['extends' => 'Activity', 'title' => 'test_group_a']);
    $activityField = $this->customFieldCreate([
      'custom_group_id' => $activityGroup['id'],
      'label' => 'For Activities',
    ]);

    // Check getfields
    $this->assertEquals(
      ['custom_' . $contactField['id'], 'custom_' . $indivField['id']],
      $this->getCustomFieldKeys($this->callAPISuccess('Contact', 'getfields', [])),
      'Contact custom fields'
    );
    $this->assertEquals(
      ['custom_' . $contactField['id'], 'custom_' . $indivField['id']],
      $this->getCustomFieldKeys($this->callAPISuccess('Individual', 'getfields', [])),
      'Individual custom fields'
    );
    $this->assertEquals(
      ['custom_' . $contactField['id']],
      $this->getCustomFieldKeys($this->callAPISuccess('Organization', 'getfields', [])),
      'Organization custom fields'
    );
    $this->assertEquals(
      ['custom_' . $activityField['id']],
      $this->getCustomFieldKeys($this->callAPISuccess('Activity', 'getfields', [])),
      'Activity custom fields'
    );
    foreach ($auxEntities as $entity) {
      $this->assertEquals(
        [],
        $this->getCustomFieldKeys($this->callAPISuccess($entity, 'getfields', [])),
        "Custom fields for $entity should be empty"
      );
    }
  }

  /**
   * Test setting and getting a custom file field value.
   *
   * Uses the "attachment" api for setting value.
   */
  public function testCustomFileField() {
    $customGroup = $this->customGroupCreate(['title' => 'attachment_test_group']);
    $params = [
      'custom_group_id' => $customGroup['id'],
      'name' => 'test_file_attachment',
      'label' => 'test_file_attachment',
      'html_type' => 'File',
      'data_type' => 'File',
      'is_active' => 1,
    ];
    $customField = $this->callAPISuccess('custom_field', 'create', $params);
    $cfId = 'custom_' . $customField['id'];

    $cid = $this->individualCreate();

    $attachment = $this->callAPISuccess('attachment', 'create', [
      'name' => CRM_Utils_String::createRandom(5, CRM_Utils_String::ALPHANUMERIC) . '_testCustomFileField.txt',
      'mime_type' => 'text/plain',
      'content' => 'My test content',
      'field_name' => $cfId,
      'entity_id' => $cid,
    ]);
    $this->assertAttachmentExistence(TRUE, $attachment);

    $result = $this->callAPISuccess('contact', 'getsingle', [
      'id' => $cid,
      'return' => $cfId,
    ]);

    $this->assertEquals($attachment['id'], $result[$cfId]);
  }

  public function testUpdateCustomField() {
    $customGroup = $this->customGroupCreate(['extends' => 'Individual']);
    $params = ['id' => $customGroup['id'], 'is_active' => 0];
    $result = $this->callAPISuccess('CustomGroup', 'create', $params);
    $result = array_shift($result['values']);

    $this->assertEquals(0, $result['is_active']);

    $this->customGroupDelete($customGroup['id']);
  }

  public function testCustomFieldCreateWithOptionGroupName() {
    $customGroup = $this->customGroupCreate(['extends' => 'Individual', 'title' => 'test_custom_group']);
    $params = [
      'custom_group_id' => $customGroup['id'],
      'name' => 'Activity type',
      'label' => 'Activity type',
      'data_type' => 'String',
      'html_type' => 'Select',
      'option_group_id' => 'activity_type',
    ];
    $result = $this->callAPISuccess('CustomField', 'create', $params);
  }

  /**
   * @param $getFieldsResult
   *
   * @return array
   */
  public function getCustomFieldKeys($getFieldsResult) {
    $isCustom = function ($key) {
      return preg_match('/^custom_/', $key);
    };
    $r = array_values(array_filter(array_keys($getFieldsResult['values']), $isCustom));
    sort($r);
    return $r;
  }

  public function testMakeSearchableContactReferenceFieldUnsearchable() {
    $customGroup = $this->customGroupCreate([
      'name' => 'testCustomGroup',
      'title' => 'testCustomGroup',
      'extends' => 'Individual',
    ]);
    $params = [
      'name' => 'testCustomField',
      'label' => 'testCustomField',
      'custom_group_id' => 'testCustomGroup',
      'data_type' => 'ContactReference',
      'html_type' => 'Autocomplete-Select',
      'is_searchable' => '1',
    ];
    $result = $this->callAPISuccess('CustomField', 'create', $params);
    $params = [
      'id' => $result['id'],
      'is_searchable' => 0,
    ];
    $result = $this->callAPISuccess('CustomField', 'create', $params);
  }

  public function testDisableSearchableContactReferenceField() {
    $customGroup = $this->customGroupCreate([
      'name' => 'testCustomGroup',
      'title' => 'testCustomGroup',
      'extends' => 'Individual',
    ]);
    $params = [
      'name' => 'testCustomField',
      'label' => 'testCustomField',
      'custom_group_id' => 'testCustomGroup',
      'data_type' => 'ContactReference',
      'html_type' => 'Autocomplete-Select',
      'is_searchable' => '1',
    ];
    $result = $this->callAPISuccess('CustomField', 'create', $params);
    $params = [
      'id' => $result['id'],
      'is_active' => 0,
    ];
    $result = $this->callAPISuccess('CustomField', 'create', $params);
  }

}
