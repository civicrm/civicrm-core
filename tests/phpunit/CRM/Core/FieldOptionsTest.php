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

require_once 'CiviTest/CiviUnitTestCase.php';

/**
 * Tests for field options
 */
class CRM_Core_FieldOptionsTest extends CiviUnitTestCase {
  function get_info() {
    return array(
      'name' => 'FieldOptions',
      'description' => 'Tests for field-specific option values',
      'group' => 'Core',
    );
  }

  function setUp() {
    parent::setUp();
  }

  /**
   * Assure CRM_Core_PseudoConstant::get() is working properly for a range of
   * DAO fields having a <pseudoconstant> tag in the XML schema.
   */
  function testOptionValues() {
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
}
