<?php

namespace Civi\Financialacls;

use Civi\Api4\FinancialAccount;

// I fought the Autoloader and the autoloader won.
require_once 'BaseTestClass.php';

/**
 * @group headless
 */
class FinancialAccountTest extends BaseTestClass {

  /**
   * Test only accounts with permitted income types can be retrieved.
   *
   * @throws \CRM_Core_Exception
   */
  public function testGetFinancialAccount(): void {
    $this->setupLoggedInUserWithLimitedFinancialTypeAccess();
    $financialAccounts = FinancialAccount::get(FALSE)->execute();
    $this->assertCount(14, $financialAccounts);
    $restrictedAccounts = FinancialAccount::get()->execute();
    $this->assertCount(1, $restrictedAccounts);
    $this->assertEquals('Donation', $restrictedAccounts[0]['name']);
  }

}
