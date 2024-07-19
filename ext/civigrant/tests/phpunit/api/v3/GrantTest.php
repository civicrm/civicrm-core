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

use Civi\Test;
use Civi\Test\Api3TestTrait;
use Civi\Test\CiviEnvBuilder;
use Civi\Test\ContactTestTrait;

/**
 *  Test APIv3 civicrm_grant* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Grant
 * @group headless
 */
class api_v3_GrantTest extends \PHPUnit\Framework\TestCase implements \Civi\Test\HeadlessInterface, \Civi\Test\TransactionalInterface {
  use Api3TestTrait;
  use ContactTestTrait;
  use Test\EntityTrait;

  protected $_apiversion = 3;
  protected $params;
  protected $ids = [];
  protected $_entity = 'Grant';

  /**
   * @throws \CRM_Extension_Exception_ParseException
   */
  public function setUpHeadless(): CiviEnvBuilder {
    return Test::headless()
      ->install(['org.civicrm.afform', 'org.civicrm.search_kit'])
      ->installMe(__DIR__)
      ->apply();
  }

  public function setUp(): void {
    parent::setUp();
    $this->ids['contact'][0] = $this->individualCreate();
    $this->params = [
      'contact_id' => $this->ids['contact'][0],
      'application_received_date' => 'now',
      'decision_date' => 'next Monday',
      'amount_total' => '500.00',
      'status_id' => 1,
      'rationale' => 'Just Because',
      'currency' => 'USD',
      'grant_type_id' => 1,
    ];
    // Create a sample grant type
    \CRM_Core_BAO_OptionValue::ensureOptionValueExists([
      'option_group_id' => 'grant_type',
      'label' => 'Emergency',
      'name' => 'Emergency',
      'value' => 1,
      'is_active' => 1,
    ]);
    // Currently Civi Contribute is always enabled but if that changes the get
    // test might become invalid so let's pick up any future change.
    $civiContribute = $this->callAPISuccess('Extension', 'get', [
      'sequential' => 1,
      'full_name' => "civi_contribute",
    ])['values'][0];
    $this->assertEquals('installed', $civiContribute['status']);
  }

  /**
   * Cleanup after test.
   *
   * @throws \Exception
   */
  public function tearDown(): void {
    foreach ($this->ids as $entity => $entities) {
      foreach ($entities as $id) {
        $this->callAPISuccess($entity, 'delete', ['id' => $id]);
      }
    }
    parent::tearDown();
  }

  public function testCreateGrant(): void {
    $result = $this->callAPISuccess('Grant', 'create', $this->params);
    $this->ids['Grant'][0] = $result['id'];
    $this->assertEquals(1, $result['count']);
    $this->assertNotNull($result['values'][$result['id']]['id']);
  }

  /**
   * Check checkbox type custom fields are created correctly.
   *
   * We want to ensure they are saved with separators as appropriate
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreateCustomCheckboxGrant(): void {
    $cg = $this->callAPISuccess('customGroup', 'create', [
      'title' => 'Grant custom group',
      'extends' => 'Grant',
    ]);
    $customTable = $cg['values'][$cg['id']]['table_name'];
    $cf = $this->callAPISuccess('CustomField', 'create', [
      'label' => 'Custom Field',
      'data_type' => 'String',
      'html_type' => 'CheckBox',
      'custom_group_id' => $cg['id'],
      'option_values' => [
        ['label' => 'my valley', 'value' => 'valley', 'is_active' => TRUE, 'weight' => 1],
        ['label' => 'my goat', 'value' => 'goat', 'is_active' => TRUE, 'weight' => 2],
        ['label' => 'mohair', 'value' => 'wool', 'is_active' => TRUE, 'weight' => 3],
        ['label' => 'hungry', 'value' => '', 'is_active' => TRUE, 'weight' => 3],
      ],
    ]);
    $columnName = $cf['values'][$cf['id']]['column_name'];
    $this->ids['custom_field'][] = $cf['id'];
    $this->ids['custom_group'][] = $cg['id'];
    $customFieldLabel = 'custom_' . $cf['id'];
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
    $this->params[$customFieldLabel] = ['valley', 'goat'];
    $result = $this->callAPISuccess($this->_entity, 'create', $this->params);
    $savedValue = CRM_Core_DAO::singleValueQuery("SELECT {$columnName} FROM $customTable WHERE entity_id = {$result['id']}");
    $this->assertEquals($expectedValue, $savedValue);
  }

  public function testGetGrant(): void {
    $result = $this->callAPISuccess('Grant', 'create', $this->params);
    $this->ids['grant'][0] = $result['id'];
    $result = $this->callAPISuccess($this->_entity, 'get', ['rationale' => 'Just Because']);
    $this->assertGreaterThanOrEqual(1, $result['count']);
  }

  public function testGrantGetDoesNotInterfereWithContributionGet() {
    $this->individualCreate();
    $this->createTestEntity('Contribution', [
      'contact_id' => $this->individualCreate(),
      'currency' => 'NZD',
      'total_amount' => 5,
      'financial_type_id:name' => 'Donation',
    ]);
    $this->createTestEntity('Grant', $this->params);
    $contribution = $this->callAPISuccessGetSingle('Contribution', []);
    $this->assertEquals('NZD', $contribution['currency']);
  }

  public function testDeleteGrant(): void {
    $result = $this->callAPISuccess($this->_entity, 'create', $this->params);
    $this->callAPISuccess($this->_entity, 'delete', ['id' => $result['id']]);
    $checkDeleted = $this->callAPISuccess($this->_entity, 'get', ['id' => $result['id']]);
    $this->assertEquals(0, $checkDeleted['count']);
  }

  /**
   * Test Grant status with `0` value.
   */
  public function testGrantWithZeroStatus(): void {
    $params = [
      'action' => 'create',
      'grant_type_id' => "Emergency",
      'amount_total' => 100,
      'contact_id' => "1",
      'status_id' => 0,
      'id' => 1,
    ];
    $validation = $this->callAPISuccess('Grant', 'validate', $params);

    $expectedOut = [
      'status_id' => [
        'message' => "'0' is not a valid option for field status_id",
        'code' => "incorrect_value",
      ],
    ];
    $this->assertEquals($validation['values'][0], $expectedOut);
  }

  /**
   * Test contact subtype filter on grant report.
   */
  public function testGrantReportSeparatedFilter(): void {
    $this->ids['contact'][] = $contactID = $this->individualCreate(['contact_sub_type' => ['Student', 'Parent']]);
    $this->ids['contact'][] = $contactID2 = $this->individualCreate();
    $this->ids['grant'][] = $this->callAPISuccess('Grant', 'create', ['contact_id' => $contactID, 'status_id' => 1, 'grant_type_id' => 1, 'amount_total' => 1])['id'];
    $this->ids['grant'][] = $this->callAPISuccess('Grant', 'create', ['contact_id' => $contactID2, 'status_id' => 1, 'grant_type_id' => 1, 'amount_total' => 1])['id'];
    $rows = $this->callAPISuccess('report_template', 'getrows', [
      'report_id' => 'grant/detail',
      'contact_sub_type_op' => 'in',
      'contact_sub_type_value' => ['Student'],
    ]);
    $this->assertEquals(1, $rows['count']);
  }

}
