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
 *  Test APIv3 civicrm_grant* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Grant
 * @group headless
 */
class api_v3_GrantTest extends CiviUnitTestCase {
  protected $_apiversion = 3;
  protected $params;
  protected $ids = array();
  protected $_entity = 'Grant';

  public $DBResetRequired = FALSE;

  public function setUp() {
    parent::setUp();
    $this->ids['contact'][0] = $this->individualCreate();
    $this->params = array(
      'contact_id' => $this->ids['contact'][0],
      'application_received_date' => 'now',
      'decision_date' => 'next Monday',
      'amount_total' => '500',
      'status_id' => 1,
      'rationale' => 'Just Because',
      'currency' => 'USD',
      'grant_type_id' => 1,
    );
  }

  public function tearDown() {
    foreach ($this->ids as $entity => $entities) {
      foreach ($entities as $id) {
        $this->callAPISuccess($entity, 'delete', array('id' => $id));
      }
    }
    $this->quickCleanup(array('civicrm_grant'));
  }

  public function testCreateGrant() {
    $result = $this->callAPIAndDocument($this->_entity, 'create', $this->params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $result['count']);
    $this->assertNotNull($result['values'][$result['id']]['id']);
    $this->getAndCheck($this->params, $result['id'], $this->_entity);
  }

  /**
   * Check checkbox type custom fields are created correctly.
   *
   * We want to ensure they are saved with separators as appropriate
   */
  public function testCreateCustomCheckboxGrant() {
    $ids = array();
    $result = $this->customGroupCreate(array('extends' => 'Grant'));
    $ids['custom_group_id'] = $result['id'];
    $customTable = $result['values'][$result['id']]['table_name'];
    $result = $this->customFieldCreate(array(
      'html_type' => 'CheckBox',
      'custom_group_id' => $ids['custom_group_id'],
      'option_values' => array(
        array('label' => 'my valley', 'value' => 'valley', 'is_active' => TRUE, 'weight' => 1),
        array('label' => 'my goat', 'value' => 'goat', 'is_active' => TRUE, 'weight' => 2),
        array('label' => 'mohair', 'value' => 'wool', 'is_active' => TRUE, 'weight' => 3),
        array('label' => 'hungry', 'value' => '', 'is_active' => TRUE, 'weight' => 3),
      ),
    ));
    $columnName = $result['values'][$result['id']]['column_name'];
    $ids['custom_field_id'] = $result['id'];
    $customFieldLabel = 'custom_' . $ids['custom_field_id'];
    $expectedValue = CRM_Core_DAO::VALUE_SEPARATOR . 'valley' . CRM_Core_DAO::VALUE_SEPARATOR;
    //first we pass in the core separators ourselves
    $this->params[$customFieldLabel] = $expectedValue;
    $result = $this->callAPISuccess($this->_entity, 'create', $this->params);
    $this->params['id'] = $result['id'];

    $savedValue = CRM_Core_DAO::singleValueQuery("SELECT {$columnName} FROM $customTable WHERE entity_id = {$result['id']}");

    $this->assertEquals($expectedValue, $savedValue);

    // now we ask CiviCRM to add the separators
    $this->params[$customFieldLabel] = "valley";
    $result = $this->callAPISuccess($this->_entity, 'create', $this->params);
    $savedValue = CRM_Core_DAO::singleValueQuery("SELECT {$columnName} FROM $customTable WHERE entity_id = {$result['id']}");
    $this->assertEquals($expectedValue, $savedValue);

    //let's try with 2 params already separated
    $expectedValue = CRM_Core_DAO::VALUE_SEPARATOR . 'valley' . CRM_Core_DAO::VALUE_SEPARATOR . 'goat' . CRM_Core_DAO::VALUE_SEPARATOR;
    $this->params[$customFieldLabel] = $expectedValue;
    $result = $this->callAPISuccess($this->_entity, 'create', $this->params);
    $savedValue = CRM_Core_DAO::singleValueQuery("SELECT {$columnName} FROM $customTable WHERE entity_id = {$result['id']}");
    $this->assertEquals($expectedValue, $savedValue);

    //& an array for good measure
    $this->params[$customFieldLabel] = array('valley', 'goat');
    $result = $this->callAPISuccess($this->_entity, 'create', $this->params);
    $savedValue = CRM_Core_DAO::singleValueQuery("SELECT {$columnName} FROM $customTable WHERE entity_id = {$result['id']}");
    $this->assertEquals($expectedValue, $savedValue);

    $this->customFieldDelete($ids['custom_field_id']);
    $this->customGroupDelete($ids['custom_group_id']);
  }

  public function testGetGrant() {
    $result = $this->callAPISuccess($this->_entity, 'create', $this->params);
    $this->ids['grant'][0] = $result['id'];
    $result = $this->callAPIAndDocument($this->_entity, 'get', array('rationale' => 'Just Because'), __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result);
    $this->assertEquals(1, $result['count']);
  }

  public function testDeleteGrant() {
    $result = $this->callAPISuccess($this->_entity, 'create', $this->params);
    $result = $this->callAPIAndDocument($this->_entity, 'delete', array('id' => $result['id']), __FUNCTION__, __FILE__);
    $this->assertAPISuccess($result);
    $checkDeleted = $this->callAPISuccess($this->_entity, 'get', array());
    $this->assertEquals(0, $checkDeleted['count']);
  }

  /**
   * Test Grant status with `0` value.
   */
  public function testGrantWithZeroStatus() {
    $params = array(
      'action' => 'create',
      'grant_type_id' => "Emergency",
      'amount_total' => 100,
      'contact_id' => "1",
      'status_id' => 0,
      'id' => 1,
    );
    $validation = $this->callAPISuccess('Grant', 'validate', $params);

    $expectedOut = array(
      'status_id' => array(
        'message' => "'0' is not a valid option for field status_id",
        'code' => "incorrect_value",
      ),
    );
    $this->assertEquals($validation['values'][0], $expectedOut);
  }

}
