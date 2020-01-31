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
class CRM_Core_BAO_OptionGroupTest extends CiviUnitTestCase {

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
  public function testEnsureOptionGroupExistsExistingValue() {
    CRM_Core_BAO_OptionGroup::ensureOptionGroupExists(['name' => 'contribution_status']);
    $this->callAPISuccessGetSingle('OptionGroup', ['name' => 'contribution_status']);
  }

  /**
   * Ensure only one option value exists adds a new value.
   */
  public function testEnsureOptionGroupExistsNewValue() {
    CRM_Core_BAO_OptionGroup::ensureOptionGroupExists(['name' => 'Bombed']);
    $optionGroups = $this->callAPISuccess('OptionValue', 'getoptions', ['field' => 'option_group_id'])['values'];
    $this->assertTrue(in_array('bombed', $optionGroups));

    CRM_Core_BAO_OptionGroup::ensureOptionGroupExists(['name' => 'Bombed Again']);
    $optionGroups = $this->callAPISuccess('OptionValue', 'getoptions', ['field' => 'option_group_id'])['values'];
    $this->assertTrue(in_array('bombed_again', $optionGroups));
  }

}
