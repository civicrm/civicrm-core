<?php

namespace Civi\Financialacls;

use Civi\Api4\PriceField;
use Civi\Api4\PriceFieldValue;
use Civi\Api4\PriceSet;
use CRM_Core_Session;

// I fought the Autoloader and the autoloader won.
require_once 'BaseTestClass.php';

/**
 * @group headless
 */
class FinancialTypeTest extends BaseTestClass {

  /**
   * Test that a message is put in session when changing the name of a
   * financial type.
   */
  public function testChangeFinancialTypeName(): void {
    $type = $this->callAPISuccess('FinancialType', 'create', [
      'name' => 'my test',
    ]);
    $this->callAPISuccess('FinancialType', 'create', [
      'name' => 'your test',
      'id' => $type['id'],
    ]);
    $statusMessages = CRM_Core_Session::singleton()->getStatus(TRUE);
    $financialTypeMessages = array_filter($statusMessages, function ($msg) {
        return strpos($msg['text'], 'Changing the name of a Financial Type') === 0;
    });
    $this->assertEquals(1, count($financialTypeMessages));
  }

  /**
   * Check method testPermissionedFinancialTypes()
   */
  public function testPermissionedFinancialTypes(): void {
    $permissions = \CRM_Core_Permission::basicPermissions(FALSE, TRUE);
    $actions = [
      'add' => ts('add'),
      'view' => ts('view'),
      'edit' => ts('edit'),
      'delete' => ts('delete'),
    ];
    $financialTypes = \CRM_Contribute_BAO_Contribution::buildOptions('financial_type_id', 'validate');
    foreach ($financialTypes as $type) {
      foreach ($actions as $action => $action_ts) {
        $this->assertEquals(
          [
            'label' => ts('CiviCRM: %1 contributions of type %2', [
              1 => $action_ts,
              2 => $type,
            ]),
            'description' => ts('%1 contributions of type %2', [1 => $action_ts, 2 => $type]),
            'implied_by' => [ts('%1 contributions of all types', [1 => $action_ts])],
            'parent' => $action_ts . ' contributions of all types',
          ],
          $permissions[$action . ' contributions of type ' . $type]
        );
      }
    }
    $this->assertEquals([
      'label' => ts('CiviCRM: administer CiviCRM Financial Types'),
      'description' => ts('Administer access to Financial Types'),
    ], $permissions['administer CiviCRM Financial Types']);
  }

  /**
   * Test income financial types are acl filtered.
   */
  public function testGetIncomeFinancialType(): void {
    $types = \CRM_Financial_BAO_FinancialType::getIncomeFinancialType();
    $this->assertCount(4, $types);
    $this->setupLoggedInUserWithLimitedFinancialTypeAccess();
    $type = \CRM_Financial_BAO_FinancialType::getIncomeFinancialType();
    $this->assertEquals([1 => 'Donation'], $type);
  }

  /**
   * Check method test_civicrm_financial_acls_check_permissioned_line_items()
   *
   * @throws \CRM_Core_Exception
   */
  public function testCheckPermissionedLineItems(): void {
    $priceSetID = PriceSet::create()->setValues([
      'title' => 'Price Set Financial ACLS',
      'name' => 'test_price_set',
      'extends' => 1,
      'financial_type_id:name' => 'Donation',
    ])->execute()->first()['id'];

    $paramsField = [
      'label' => 'Price Field',
      'name' => 'test_price_field',
      'html_type' => 'CheckBox',
      'is_display_amounts' => 1,
      'weight' => 1,
      'options_per_line' => 1,
      'price_set_id' => $priceSetID,
      'is_enter_qty' => 1,
      'financial_type_id:name' => 'Donation',
    ];
    $priceFieldID = PriceField::create()
      ->setValues($paramsField)
      ->execute()
      ->first()['id'];
    $priceFieldValueID = PriceFieldValue::create()->setValues([
      'price_field_id' => $priceFieldID,
      'amount' => 100,
      'name' => 'price_field_value',
      'label' => 'Price Field 1',
      'financial_type_id:name' => 'Donation',
      'weight' => 1,
    ])->execute()->first()['id'];
    $contributionParams = [
      'total_amount' => 300,
      'currency' => 'USD',
      'contact_id' => $this->individualCreate(),
      'financial_type_id' => 'Donation',
      'line_items' => [
        [
          'line_item' => [
            [
              'price_field_id' => $priceFieldID,
              'price_field_value_id' => $priceFieldValueID,
              'qty' => 3,
            ],
          ],
        ],
      ],
    ];

    $contribution = $this->callAPISuccess('Order', 'create', $contributionParams);

    $this->setPermissions([
      'view contributions of type Member Dues',
    ]);

    try {
      _civicrm_financial_acls_check_permissioned_line_items($contribution['id'], 'view');
      $this->fail('Missed expected exception');
    }
    catch (\CRM_Core_Exception $e) {
      $this->assertEquals('You do not have permission to access this page.', $e->getMessage());
    }

    $this->setPermissions([
      'view contributions of type Donation',
    ]);
    try {
      _civicrm_financial_acls_check_permissioned_line_items($contribution['id'], 'view');
    }
    catch (\CRM_Core_Exception $e) {
      $this->fail('permissions should be established');
    }
  }

}
