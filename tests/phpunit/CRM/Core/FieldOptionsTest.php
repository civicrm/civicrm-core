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
 * Tests for field options
 * @group headless
 */
class CRM_Core_FieldOptionsTest extends CiviUnitTestCase {

  /**
   * @var array
   */
  public $replaceOptions;

  /**
   * @var array
   */
  public $appendOptions;

  /**
   * @var string
   */
  public $targetField;

  public function setUp() {
    parent::setUp();
    CRM_Utils_Hook::singleton()->setHook('civicrm_fieldOptions', [$this, 'hook_civicrm_fieldOptions']);
  }

  public function tearDown() {
    parent::tearDown();
    $this->quickCleanup(['civicrm_custom_field', 'civicrm_custom_group']);
  }

  /**
   * Assure CRM_Core_PseudoConstant::get() is working properly for a range of
   * DAO fields having a <pseudoconstant> tag in the XML schema.
   */
  public function testOptionValues() {
    /**
     * baoName/field combinations to test
     * Format: array[BAO Name] = $properties, where properties is an array whose
     * named members can be:
     * - fieldName: the SQL column name within the DAO table.
     * - sample: Any one value which is expected in the list of option values.
     * - context: Context to pass
     * - props: Object properties to pass
     * - exclude: Any one value which should not be in the list.
     * - max: integer (default = 10) maximum number of option values expected.
     */
    $fields = [
      'CRM_Core_BAO_Address' => [
        [
          'fieldName' => 'state_province_id',
          'sample' => 'California',
          'max' => 60,
          'props' => ['country_id' => 1228],
        ],
      ],
      'CRM_Contact_BAO_Contact' => [
        [
          'fieldName' => 'contact_sub_type',
          'sample' => 'Team',
          'exclude' => 'Organization',
          'props' => ['contact_type' => 'Organization'],
        ],
      ],
    ];

    foreach ($fields as $baoName => $baoFields) {
      foreach ($baoFields as $field) {
        $message = "BAO name: '{$baoName}', field: '{$field['fieldName']}'";

        $props = CRM_Utils_Array::value('props', $field, []);
        $optionValues = $baoName::buildOptions($field['fieldName'], 'create', $props);
        $this->assertNotEmpty($optionValues, $message);

        // Ensure sample value is contained in the returned optionValues.
        $this->assertContains($field['sample'], $optionValues, $message);

        // Exclude test
        if (!empty($field['exclude'])) {
          $this->assertNotContains($field['exclude'], $optionValues, $message);
        }

        // Ensure count of optionValues is not extraordinarily high.
        $max = CRM_Utils_Array::value('max', $field, 10);
        $this->assertLessThanOrEqual($max, count($optionValues), $message);
      }
    }
  }

  /**
   * Ensure hook_civicrm_fieldOptions is working
   */
  public function testHookFieldOptions() {
    CRM_Core_PseudoConstant::flush();

    // Test replacing all options with a hook
    $this->targetField = 'case_type_id';
    $this->replaceOptions = ['foo' => 'Foo', 'bar' => 'Bar'];
    $result = $this->callAPISuccess('case', 'getoptions', ['field' => 'case_type_id']);
    $this->assertEquals($result['values'], $this->replaceOptions);

    // TargetField doesn't match - should get unmodified option list
    $originalGender = CRM_Contact_BAO_Contact::buildOptions('gender_id');
    $this->assertNotEquals($originalGender, $this->replaceOptions);

    // This time we should get foo bar appended to the list
    $this->targetField = 'gender_id';
    $this->appendOptions = ['foo' => 'Foo', 'bar' => 'Bar'];
    $this->replaceOptions = NULL;
    CRM_Core_PseudoConstant::flush();
    $result = CRM_Contact_BAO_Contact::buildOptions('gender_id');
    $this->assertEquals($result, $originalGender + $this->appendOptions);
  }

  /**
   * Ensure hook_civicrm_fieldOptions works with custom fields
   */
  public function testHookFieldOptionsWithCustomFields() {
    // Create a custom field group for testing.
    $custom_group_name = md5(microtime());
    $api_params = [
      'title' => $custom_group_name,
      'extends' => 'Individual',
      'is_active' => TRUE,
    ];
    $customGroup = $this->callAPISuccess('customGroup', 'create', $api_params);

    // Add a custom select field.
    $api_params = [
      'custom_group_id' => $customGroup['id'],
      'label' => $custom_group_name . 1,
      'html_type' => 'Select',
      'data_type' => 'String',
      'option_values' => [
        'foo' => 'Foo',
        'bar' => 'Bar',
      ],
    ];
    $result = $this->callAPISuccess('custom_field', 'create', $api_params);
    $customField1 = $result['id'];

    // Add a custom country field.
    $api_params = [
      'custom_group_id' => $customGroup['id'],
      'label' => $custom_group_name . 2,
      'html_type' => 'Select Country',
      'data_type' => 'Country',
    ];
    $result = $this->callAPISuccess('custom_field', 'create', $api_params);
    $customField2 = $result['id'];

    // Add a custom boolean field.
    $api_params = [
      'custom_group_id' => $customGroup['id'],
      'label' => $custom_group_name . 3,
      'html_type' => 'Radio',
      'data_type' => 'Boolean',
    ];
    $result = $this->callAPISuccess('custom_field', 'create', $api_params);
    $customField3 = $result['id'];

    $this->targetField = 'custom_' . $customField1;
    $this->replaceOptions = NULL;
    $this->appendOptions = ['baz' => 'Baz'];
    $field = new CRM_Core_BAO_CustomField();
    $field->id = $customField1;
    $this->assertEquals(['foo' => 'Foo', 'bar' => 'Bar', 'baz' => 'Baz'], $field->getOptions());

    $this->targetField = 'custom_' . $customField2;
    $this->replaceOptions = ['nowhere' => 'Nowhere'];
    $field = new CRM_Core_BAO_CustomField();
    $field->id = $customField2;
    $this->assertEquals($this->replaceOptions + $this->appendOptions, $field->getOptions());

    $this->targetField = 'custom_' . $customField3;
    $this->replaceOptions = NULL;
    $this->appendOptions = [2 => 'Maybe'];
    $options = CRM_Core_PseudoConstant::get('CRM_Core_BAO_CustomField', $this->targetField);
    $this->assertEquals([1 => 'Yes', 0 => 'No', 2 => 'Maybe'], $options);
  }

  /**
   * Implements hook_civicrm_fieldOptions().
   */
  public function hook_civicrm_fieldOptions($entity, $field, &$options, $params) {
    if ($field == $this->targetField) {
      if (is_array($this->replaceOptions)) {
        $options = $this->replaceOptions;
      }
      if ($this->appendOptions) {
        $options += $this->appendOptions;
      }
    }
  }

}
