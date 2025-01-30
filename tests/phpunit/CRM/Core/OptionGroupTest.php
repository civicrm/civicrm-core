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

  use \Civi\Test\Api4TestTrait;

  /**
   * Test setup for every test.
   */
  public function setUp(): void {
    parent::setUp();
  }

  public function tearDown(): void {
    $this->deleteTestRecords();
    parent::tearDown();
  }

  /**
   * Ensure only one option value exists after calling ensureOptionValueExists.
   */
  public function testWeightOptionGroup(): void {
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

  public static function orderByCases(): array {
    return [
      ['weight', FALSE],
      ['id`; DELETE FROM contact; SELECT id FROM contact WHERE `id', TRUE],
    ];
  }

  /**
   * Test to ensure that OrderBy in CRM_Core_OptionGroup::values is sanitised
   * @dataProvider orderByCases
   */
  public function testOrderBy($case, $expectException): void {
    try {
      CRM_Core_OptionGroup::values('individual_prefix', FALSE, FALSE, FALSE, NULL, 'label', TRUE, FALSE, 'value', $case);
      $this->assertFalse($expectException);
    }
    catch (CRM_Core_Exception $e) {
      $this->assertTrue($expectException);
    }
  }

  /**
   * Tests legacy adapter for accessing SiteEmailAddress via the OptionValue api
   * @see SiteEmailLegacyOptionValueAdapter
   */
  public function testLegacyFromEmailAddressOptionGroup(): void {
    $this->saveTestRecords('SiteEmailAddress', [
      'records' => [
        [
          'email' => 'default_for_domain@example.com',
          'display_name' => 'Test User',
          'is_default' => TRUE,
        ],
        [
          'email' => 'test_this_domain@example.com',
          'display_name' => 'Test User',
        ],
        [
          'email' => 'test_this_domain_disabled@example.com',
          'display_name' => 'Test User',
          'is_active' => FALSE,
        ],
        [
          'email' => 'test_other_domain@example.com',
          'display_name' => 'Test User',
          'domain_id' => 2,
        ],
      ],
    ]);

    // By default, disabled options are excluded
    $optionGroups = CRM_Core_OptionGroup::values('from_email_address');
    $this->assertContains('"Test User" <default_for_domain@example.com>', $optionGroups);
    $this->assertContains('"Test User" <test_this_domain@example.com>', $optionGroups);
    $this->assertNotContains('"Test User" <test_this_domain_disabled@example.com>', $optionGroups);
    // Options from other domains are always excluded
    $this->assertNotContains('"Test User" <test_other_domain@example.com>', $optionGroups);

    // Include disabled
    $optionGroups = CRM_Core_OptionGroup::values('from_email_address', FALSE, FALSE, FALSE, NULL, 'label', FALSE);
    $this->assertContains('"Test User" <test_this_domain@example.com>', $optionGroups);
    $this->assertContains('"Test User" <test_this_domain_disabled@example.com>', $optionGroups);
    $this->assertNotContains('"Test User" <test_other_domain@example.com>', $optionGroups);

    // Test with a condition
    $testValue = array_search('"Test User" <test_this_domain@example.com>', $optionGroups);
    $result = CRM_Core_OptionGroup::values('from_email_address', FALSE, FALSE, FALSE, " AND value = $testValue");
    $this->assertCount(1, $result);
    $this->assertEquals('"Test User" <test_this_domain@example.com>', $result[$testValue]);

    // Test the getDefaultValue function which goes through the same adapter
    $defaultValue = array_search('"Test User" <default_for_domain@example.com>', $optionGroups);
    $this->assertEquals($defaultValue, CRM_Core_OptionGroup::getDefaultValue('from_email_address'));
  }

}
