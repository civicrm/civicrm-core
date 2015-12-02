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

require_once 'CiviTest/CiviUnitTestCase.php';

/**
 * Tests for field options
 */
class CRM_Core_FieldOptionsTest extends CiviUnitTestCase {

  /** @var  CRM_Utils_Hook_UnitTests */
  public $hookClass;

  /** @var array */
  public $replaceOptions;

  /** @var array */
  public $appendOptions;

  /** @var string */
  public $targetField;

  public function setUp() {
    parent::setUp();
    $this->hookClass = CRM_Utils_Hook::singleton();
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
    $fields = array(
      'CRM_Core_BAO_Address' => array(
        array(
          'fieldName' => 'state_province_id',
          'sample' => 'California',
          'max' => 60,
          'props' => array('country_id' => 1228),
        ),
      ),
      'CRM_Contact_BAO_Contact' => array(
        array(
          'fieldName' => 'contact_sub_type',
          'sample' => 'Team',
          'exclude' => 'Organization',
          'props' => array('contact_type' => 'Organization'),
        ),
      ),
    );

    foreach ($fields as $baoName => $baoFields) {
      foreach ($baoFields as $field) {
        $message = "BAO name: '{$baoName}', field: '{$field['fieldName']}'";

        $props = CRM_Utils_Array::value('props', $field, array());
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
    $this->hookClass->setHook('civicrm_fieldOptions', array($this, 'hook_civicrm_fieldOptions'));
    CRM_Core_PseudoConstant::flush();

    // Test replacing all options with a hook
    $this->targetField = 'case_type_id';
    $this->replaceOptions = array('foo' => 'Foo', 'bar' => 'Bar');
    $result = $this->callAPISuccess('case', 'getoptions', array('field' => 'case_type_id'));
    $this->assertEquals($result['values'], $this->replaceOptions);

    // TargetField doesn't match - should get unmodified option list
    $originalGender = CRM_Contact_BAO_Contact::buildOptions('gender_id');
    $this->assertNotEquals($originalGender, $this->replaceOptions);

    // This time we should get foo bar appended to the list
    $this->targetField = 'gender_id';
    $this->appendOptions = array('foo' => 'Foo', 'bar' => 'Bar');
    $this->replaceOptions = NULL;
    CRM_Core_PseudoConstant::flush();
    $result = CRM_Contact_BAO_Contact::buildOptions('gender_id');
    $this->assertEquals($result, $originalGender + $this->appendOptions);
  }

  /**
   * Implements hook_civicrm_fieldOptions
   *
   * @param $entity
   * @param $field
   * @param $options
   * @param $params
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
