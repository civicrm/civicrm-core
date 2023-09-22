<?php

namespace Civi\Financialacls;

use Civi\Api4\EntityFinancialAccount;

// I fought the Autoloader and the autoloader won.
require_once 'BaseTestClass.php';

/**
 * @group headless
 */
class EntityFinancialAccountTest extends BaseTestClass {

  /**
   * Test only accounts with permitted income types can be retrieved.
   *
   * @throws \CRM_Core_Exception
   */
  public function testGetEntityFinancialAccount(): void {
    $this->setupLoggedInUserWithLimitedFinancialTypeAccess();
    $entityFinancialAccounts = EntityFinancialAccount::get(FALSE)->execute();
    $this->assertCount(23, $entityFinancialAccounts);
    $restrictedAccounts = EntityFinancialAccount::get()->execute();
    $this->assertCount(9, $restrictedAccounts);
    foreach ($restrictedAccounts as $restrictedAccount) {
      if ($restrictedAccount['entity_table'] === 'civicrm_financial_type') {
        $this->assertEquals(1, $restrictedAccount['entity_id']);
      }
    }
  }

}
