<?php

namespace Civi\Financialacls;

use Civi;
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
    Civi::settings()->set('acl_financial_type', TRUE);
    $type = $this->callAPISuccess('FinancialType', 'create', [
      'name' => 'my test',
    ]);
    $this->callAPISuccess('FinancialType', 'create', [
      'name' => 'your test',
      'id' => $type['id'],
    ]);
    $status = CRM_Core_Session::singleton()->getStatus(TRUE);
    $this->assertEquals('Changing the name', substr($status[0]['text'], 0, 17));
  }

  /**
   * Check method testPermissionedFinancialTypes()
   */
  public function testPermissionedFinancialTypes(): void {
    Civi::settings()->set('acl_financial_type', TRUE);
    $permissions = \CRM_Core_Permission::basicPermissions(FALSE, TRUE);
    $actions = [
      'add' => ts('add'),
      'view' => ts('view'),
      'edit' => ts('edit'),
      'delete' => ts('delete'),
    ];
    $financialTypes = \CRM_Contribute_BAO_Contribution::buildOptions('financial_type_id', 'validate');
    foreach ($financialTypes as $id => $type) {
      foreach ($actions as $action => $action_ts) {
        $this->assertEquals(
          [
            ts("CiviCRM: %1 contributions of type %2", [1 => $action_ts, 2 => $type]),
            ts('%1 contributions of type %2', [1 => $action_ts, 2 => $type]),
          ],
          $permissions[$action . ' contributions of type ' . $type]
        );
      }
    }
    $this->assertEquals([
      ts('CiviCRM: administer CiviCRM Financial Types'),
      ts('Administer access to Financial Types'),
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
   * Check method test buildPermissionedClause()
   */
  public function testBuildPermissionedClause(): void {
    Civi::settings()->set('acl_financial_type', 1);
    $this->setPermissions([
      'view contributions of type Donation',
      'view contributions of type Member Dues',
    ]);
    $whereClause = \CRM_Financial_BAO_FinancialType::buildPermissionedClause('contribution');
    $this->assertEquals('(`civicrm_contribution`.`financial_type_id` IS NULL OR (`civicrm_contribution`.`financial_type_id` IN (1,2)))', $whereClause);
    $this->setPermissions([
      'view contributions of type Donation',
      'view contributions of type Member Dues',
      'view contributions of type Event Fee',
    ]);

    $whereClause = \CRM_Financial_BAO_FinancialType::buildPermissionedClause('contribution');
    $this->assertEquals('(`civicrm_contribution`.`financial_type_id` IS NULL OR (`civicrm_contribution`.`financial_type_id` IN (1,4,2)))', $whereClause);
  }

}
