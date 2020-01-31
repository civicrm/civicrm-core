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
    $values = [];
    $options1 = CRM_Core_OptionGroup::values('activity_type', FALSE, FALSE, FALSE, NULL, 'label', FALSE);
    $options2 = CRM_Core_OptionGroup::values('activity_type', FALSE, FALSE, FALSE, NULL, 'label', FALSE, FALSE, 'value', 'name');
    // Verify that arrays are equal.
    $this->assertTrue(($options1 == $options2), "The arrays retrieved should be the same");
    // Verify sequence is different.
    $this->assertFalse(($options1 === $options2), "The arrays retrieved should be the same, but in a different order");
    // Verify values.
    $sql = "SELECT v.value, v.label
      FROM civicrm_option_value v
      INNER JOIN civicrm_option_group g ON g.id = v.option_group_id
      AND g.name = 'activity_type'
      ORDER BY v.name";
    $dao = CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      $values[$dao->value] = $dao->label;
    }
    $this->assertTrue(($values === $options2), "The arrays retrieved should be the same and in the same order");
  }

  /**
   * optionGroupTests
   *
   * @return array
   */
  public function optionGroupTests() {
    $tests = [];
    $tests[] = ['event_type', 'Integer'];
    $tests[] = ['addressee', 'null'];
    $tests[] = ['activity_status', 'Integer'];
    return $tests;
  }

  /**
   * Test Returning DataType of Option Group
   *
   *
   * @dataProvider optionGroupTests
   */
  public function testsOptionGroupDataType($optionGroup, $expectedDataType) {
    $dataType = CRM_Admin_Form_Options::getOptionGroupDataType($optionGroup);
    if ($expectedDataType == 'null') {
      $this->assertNull($dataType);
    }
    else {
      $this->assertEquals($dataType, $expectedDataType);
    }
  }

  public function emailAddressTests() {
    $tests[] = ['"Name"<email@example.com>', '"Name" <email@example.com>'];
    $tests[] = ['"Name" <email@example.com>', '"Name" <email@example.com>'];
    $tests[] = ['"Name"  <email@example.com>', '"Name" <email@example.com>'];
    return $tests;
  }

  /**
   * @dataProvider emailAddressTests
   */
  public function testSanitizeFromEmailAddress($dirty, $clean) {
    $form = new CRM_Admin_Form_Options();
    $actual = $form->sanitizeFromEmailAddress($dirty);
    $this->assertEquals($actual, $clean);
  }

}
