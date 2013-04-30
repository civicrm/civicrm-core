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
 * Tests for linking to resource files
 */
class CRM_Core_PseudoConstantTest extends CiviUnitTestCase {
  function get_info() {
    return array(
      'name'    => 'PseudoConstant',
      'description' => 'Tests for pseudoconstant option values',
      'group'     => 'Core',
    );
  }

  function setUp() {
    parent::setUp();
  }

  function testOptionValues() {
    // We'll test these daoName/field combinations.
    // array[DAO Name] = properties, where properties can be:
    // - fieldName: the SQL column name within the DAO table.
    // - sample: Any one value which is expected in the list of option values.
    // - max: integer (default = 10) maximum number of option values expected.
    $fields = array(
      'CRM_Contact_DAO_Contact' => array(
        array(
          'fieldName' => 'prefix_id',
          'sample' => 'Mr.',
        ),
        array(
          'fieldName' => 'suffix_id',
          'sample' => 'Sr.',
        ),
        array(
          'fieldName' => 'gender_id',
          'sample' => 'Male',
        ),
      ),
      'CRM_Core_DAO_Phone' => array(
        array(
          'fieldName' => 'phone_type_id',
          'sample' => 'Phone',
        ),
        array(
          'fieldName' => 'location_type_id',
          'sample' => 'Home',
        ),
      ),
      'CRM_Core_DAO_Email' => array(
        array(
          'fieldName' => 'location_type_id',
          'sample' => 'Home',
        ),
      ),
      'CRM_Core_DAO_Address' => array(
        array(
          'fieldName' => 'location_type_id',
          'sample' => 'Home',
        ),
      ),
    );

    foreach ($fields as $daoName => $daoFields) {
      foreach ($daoFields as $field) {
        $message = "DAO name: '{$daoName}', field: '{$field['fieldName']}'";

        // Ensure sample value is contained in the returned optionValues.
        $optionValues = CRM_Core_PseudoConstant::get($daoName, $field['fieldName']);
        $this->assertContains($field['sample'], $optionValues, $message);

        // Ensure count of optionValues is not extraordinarily high.
        $max = CRM_Utils_Array::value('max', $field, 10);
        $this->assertLessThanOrEqual($max, count($optionValues), $message);
      }
    }
  }
}
