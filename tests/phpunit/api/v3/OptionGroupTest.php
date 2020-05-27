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
 * Class api_v3_OptionGroupTest
 *
 * @group headless
 */
class api_v3_OptionGroupTest extends CiviUnitTestCase {

  protected $_apiversion = 3;

  protected $_entity = 'OptionGroup';

  public function setUp() {
    parent::setUp();
    $this->useTransaction(TRUE);
    $this->_params = [
      'name' => 'our test Option Group',
      'is_reserved' => 1,
      'is_active' => 1,
    ];
  }

  /**
   * Good to test option group as a representative on the Camel Case.
   */
  public function testGetOptionGroupGetFields() {
    $result = $this->callAPISuccess('option_group', 'getfields', []);
    $this->assertFalse(empty($result['values']));
  }

  public function testGetOptionGroupGetFieldsCreateAction() {
    $result = $this->callAPISuccess('option_group', 'getfields', ['action' => 'create']);
    $this->assertFalse(empty($result['values']));
    $this->assertEquals($result['values']['name']['api.unique'], 1);
  }

  public function testGetOptionGroupByID() {
    $result = $this->callAPISuccess('option_group', 'get', ['id' => 1]);
    $this->assertEquals(1, $result['count']);
    $this->assertEquals(1, $result['id']);
  }

  public function testGetOptionGroupByName() {
    $params = ['name' => 'preferred_communication_method'];
    $result = $this->callAPIAndDocument('option_group', 'get', $params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
    $this->assertEquals(1, $result['id']);
  }

  public function testGetOptionGroup() {
    $result = $this->callAPISuccess('option_group', 'get', []);
    $this->assertGreaterThan(1, $result['count']);
  }

  public function testGetOptionDoesNotExist() {
    $result = $this->callAPISuccess('option_group', 'get', ['name' => 'FSIGUBSFGOMUUBSFGMOOUUBSFGMOOBUFSGMOOIIB']);
    $this->assertEquals(0, $result['count']);
  }

  public function testGetOptionCreateSuccess() {
    $params = [
      'sequential' => 1,
      'name' => 'civicrm_event.amount.560',
      'is_reserved' => 1,
      'is_active' => 1,
      'api.OptionValue.create' => [
        'label' => 'workshop',
        'value' => 35,
        'is_default' => 1,
        'is_active' => 1,
        'format.only_id' => 1,
      ],
    ];
    $result = $this->callAPIAndDocument('OptionGroup', 'create', $params, __FUNCTION__, __FILE__);
    $this->assertEquals('civicrm_event.amount.560', $result['values'][0]['name']);
    $this->assertTrue(is_int($result['values'][0]['api.OptionValue.create']));
    $this->assertGreaterThan(0, $result['values'][0]['api.OptionValue.create']);
    $this->callAPISuccess('OptionGroup', 'delete', ['id' => $result['id']]);
  }

  /**
   * Test the error message when a failure is due to a key duplication issue.
   */
  public function testGetOptionCreateFailOnDuplicate() {
    $params = [
      'sequential' => 1,
      'name' => 'civicrm_dup_entry',
      'is_reserved' => 1,
      'is_active' => 1,
    ];
    $result1 = $this->callAPISuccess('OptionGroup', 'create', $params);
    $result = $this->callAPIFailure('OptionGroup', 'create', $params, "Field: `name` must be unique. An conflicting entity already exists - id: " . $result1['id']);
    $this->callAPISuccess('OptionGroup', 'delete', ['id' => $result1['id']]);
  }

  /**
   * Test that transaction is completely rolled back on fail.
   *
   * Check error returned.
   */
  public function testGetOptionCreateFailRollback() {
    $countFirst = $this->callAPISuccess('OptionGroup', 'getcount', ['options' => ['limit' => 5000]]);
    $params = [
      'sequential' => 1,
      'name' => 'civicrm_rolback_test',
      'is_reserved' => 1,
      'is_active' => 1,
      // executing within useTransactional() test case
      'is_transactional' => 'nest',
      'api.OptionValue.create' => [
        'label' => 'invalid entry',
        'value' => 35,
        'domain_id' => 999,
        'is_active' => '0',
        'debug' => 0,
      ],
    ];
    $result = $this->callAPIFailure('OptionGroup', 'create', $params);
    $countAfter = $this->callAPISuccess('OptionGroup', 'getcount', [
      'options' => ['limit' => 5000],
    ]);
    $this->assertEquals($countFirst, $countAfter,
      'Count of option groups should not have changed due to rollback triggered by option value In line ' . __LINE__
    );
  }

  /**
   * Success test for updating an existing Option Group.
   */
  public function testCreateUpdateOptionGroup() {
    $result = $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $params = array_merge($this->_params, ['id' => $result['id'], 'is_active' => 0]);
    $this->callAPISuccess($this->_entity, 'create', $params);
    $this->callAPISuccess('OptionGroup', 'delete', ['id' => $result['id']]);
  }

  /**
   * Success test for deleting an existing Option Group.
   */
  public function testDeleteOptionGroup() {
    $result = $this->callAPISuccess($this->_entity, 'create', $this->_params);
    $this->callAPIAndDocument('OptionGroup', 'delete', ['id' => $result['id']], __FUNCTION__, __FILE__);
  }

  /**
   * Ensure only one option value exists after calling ensureOptionValueExists.
   *
   * @throws \CRM_Core_Exception
   */
  public function testEnsureOptionGroupExistsExistingValue() {
    CRM_Core_BAO_OptionGroup::ensureOptionGroupExists(['name' => 'participant_role']);
    $this->callAPISuccessGetSingle('OptionGroup', ['name' => 'participant_role']);
  }

  /**
   * Ensure only one option value exists adds a new value.
   */
  public function testEnsureOptionGroupExistsNewValue() {
    $optionGroupID = CRM_Core_BAO_OptionGroup::ensureOptionGroupExists([
      'name' => 'Bombed',
      'title' => ts('Catastrophy'),
      'description' => ts('blah blah'),
      'is_reserved' => 1,
    ]);
    $optionGroup = $this->callAPISuccessGetSingle('OptionGroup', ['name' => 'Bombed']);
    $this->assertEquals($optionGroupID, $optionGroup['id']);
  }

}
