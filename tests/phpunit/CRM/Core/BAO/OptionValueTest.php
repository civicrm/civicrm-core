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
 * Class CRM_Core_BAO_SchemaHandlerTest.
 *
 * These tests create and drop indexes on the civicrm_uf_join table. The indexes
 * being added and dropped we assume will never exist.
 * @group headless
 */
class CRM_Core_BAO_OptionValueTest extends CiviUnitTestCase {

  /**
   * Test setup for every test.
   */
  public function setUp() {
    parent::setUp();
    $this->useTransaction(TRUE);
  }

  /**
   * Ensure only one option value exists after calling ensureOptionValueExists.
   */
  public function testEnsureOptionValueExistsExistingValue() {
    CRM_Core_BAO_OptionValue::ensureOptionValueExists(['name' => 'Completed', 'option_group_id' => 'contribution_status']);
    $this->callAPISuccessGetSingle('OptionValue', ['name' => 'Completed', 'option_group_id' => 'contribution_status']);
  }

  /**
   * Ensure only one option value exists adds a new value.
   */
  public function testEnsureOptionValueExistsNewValue() {
    CRM_Core_BAO_OptionValue::ensureOptionValueExists(['name' => 'Bombed', 'option_group_id' => 'contribution_status']);
    $optionValues = $this->callAPISuccess('OptionValue', 'get', ['option_group_id' => 'contribution_status']);
    foreach ($optionValues['values'] as $value) {
      if ($value['name'] == 'Bombed') {
        return;
      }
    }
    $this->fail('Should not have gotten this far');
  }

  /**
   * Ensure only one option value copes with disabled.
   *
   * (Our expectation is no change - ie. currently we are respecting 'someone's
   * decision to disable it & leaving it in that state.
   */
  public function testEnsureOptionValueExistsDisabled() {
    $optionValue = CRM_Core_BAO_OptionValue::ensureOptionValueExists(['name' => 'Crashed', 'option_group_id' => 'contribution_status', 'is_active' => 0]);
    $value = $this->callAPISuccessGetSingle('OptionValue', ['name' => 'Crashed', 'option_group_id' => 'contribution_status']);
    $this->assertEquals(0, $value['is_active']);
    $this->assertEquals($value['id'], $optionValue['id']);

    $optionValue = CRM_Core_BAO_OptionValue::ensureOptionValueExists(['name' => 'Crashed', 'option_group_id' => 'contribution_status']);
    $value = $this->callAPISuccessGetSingle('OptionValue', ['name' => 'Crashed', 'option_group_id' => 'contribution_status']);
    $this->assertEquals(0, $value['is_active']);
    $this->assertEquals($value['id'], $optionValue['id']);
  }

}
