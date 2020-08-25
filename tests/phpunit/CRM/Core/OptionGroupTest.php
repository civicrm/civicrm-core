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

  public function testDomainSpecificValueCache() {
    $original_domain = \CRM_Core_Config::domainID();
    $domainIDs = [];
    $optionValues = [];

    // Create domains
    $domainIDs[] = $this->callAPISuccess('Domain', 'create', [
      'name' => "Test extra domain 1",
      'domain_version' => CRM_Utils_System::version(),
    ])['id'];
    $domainIDs[] = $this->callAPISuccess('Domain', 'create', [
      'name' => "Test extra domain 2",
      'domain_version' => CRM_Utils_System::version(),
    ])['id'];

    // Create 'from' email addresses
    foreach ($domainIDs as $domainID) {
      $result = $this->callAPISuccess('option_value', 'create', [
        'option_group_id' => 'from_email_address',
        'name' => '"Test ' . $domainID . '" <test@example.com>',
        'label' => '"Test ' . $domainID . '" <test@example.com>',
        'value' => 'test' . $domainID,
        'is_active' => 1,
        'domain_id' => $domainID,
      ]);
      $optionValues[] = $result['id'];
    }

    // Check values are as expected for each domain
    foreach ($domainIDs as $domainID) {
      \CRM_Core_Config::domainID($domainID);
      $result = CRM_Core_OptionGroup::values('from_email_address');
      $this->assertEquals(array_keys($result)[0], 'test' . $domainID);
    }

    // Clean up
    \CRM_Core_Config::domainID($original_domain);
    foreach ($optionValues as $id) {
      $this->callAPISuccess('option_value', 'delete', ['id' => $id]);
    }
    // @todo There is no domain delete API
    foreach ($domainIDs as $domainID) {
      CRM_Core_DAO::executeQuery('DELETE FROM civicrm_domain where id = %1', [1 => [$domainID, 'Int']]);
    }
    unset($original_domain, $domainIDs, $optionValues);

  }

}
