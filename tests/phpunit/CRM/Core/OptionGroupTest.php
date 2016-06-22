<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * Tests for field options
 * @group headless
 */
class CRM_Core_OptionGroupTest extends CiviUnitTestCase {

  /**
   * Test setup for every test.
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * Ensure only one option value exists after calling ensureOptionValueExists.
   */
  public function testWeightOptionGroup() {
    $values = array();
    $options1 = CRM_Core_OptionGroup::values('activity_type');
    $options2 = CRM_Core_OptionGroup::values('activity_type', FALSE, FALSE, FALSE, NULL, 'label', TRUE, TRUE, 'value', 'name');
    // Verify that arrays are equal.
    $this->assertTrue(($options1 == $options2), "The arrays retrieved should be the same");
    // Verify sequence is different.
    $this->assertFalse(($options1 === $options2), "The arrays retrieved should be the same, but in a different order");
    // Verify values.
    $sql = "SELECT v.value, v.label
      FROM civicrm_option_value v
      INNER JOIN civicrm_option_group g ON g.id = v.option_group_id
      AND g.name = 'activity_type'
      WHERE g.is_active = 1 AND v.is_active = 1
      ORDER BY v.name";
    $dao = CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      $values[$dao->value] = $dao->label;
    }
    $this->assertTrue(($values === $options2), "The arrays retrieved should be the same and in the same order");
  }

}
