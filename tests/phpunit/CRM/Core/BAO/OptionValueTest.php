<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
    CRM_Core_BAO_OptionValue::ensureOptionValueExists(array('name' => 'Completed', 'option_group_id' => 'contribution_status'));
    $this->callAPISuccessGetSingle('OptionValue', array('name' => 'Completed', 'option_group_id' => 'contribution_status'));
  }

  /**
   * Ensure only one option value exists adds a new value.
   */
  public function testEnsureOptionValueExistsNewValue() {
    CRM_Core_BAO_OptionValue::ensureOptionValueExists(array('name' => 'Bombed', 'option_group_id' => 'contribution_status'));
    $optionValues = $this->callAPISuccess('OptionValue', 'get', array('option_group_id' => 'contribution_status'));
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
    $optionValue = CRM_Core_BAO_OptionValue::ensureOptionValueExists(array('name' => 'Crashed', 'option_group_id' => 'contribution_status', 'is_active' => 0));
    $value = $this->callAPISuccessGetSingle('OptionValue', array('name' => 'Crashed', 'option_group_id' => 'contribution_status'));
    $this->assertEquals(0, $value['is_active']);
    $this->assertEquals($value['id'], $optionValue['id']);

    $optionValue = CRM_Core_BAO_OptionValue::ensureOptionValueExists(array('name' => 'Crashed', 'option_group_id' => 'contribution_status'));
    $value = $this->callAPISuccessGetSingle('OptionValue', array('name' => 'Crashed', 'option_group_id' => 'contribution_status'));
    $this->assertEquals(0, $value['is_active']);
    $this->assertEquals($value['id'], $optionValue['id']);
  }

}
