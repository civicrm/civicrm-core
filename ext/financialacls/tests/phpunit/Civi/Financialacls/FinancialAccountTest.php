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
    $restrictedAccounts = FinancialAccount::get()->addOrderBy('id')->execute();
    $this->assertCount(4, $restrictedAccounts);
    $this->assertEquals('Donation', $restrictedAccounts[0]['name']);
    $this->assertEquals('Banking Fees', $restrictedAccounts[1]['name']);
    $this->assertEquals('Accounts Receivable', $restrictedAccounts[2]['name']);
    $this->assertEquals('Premiums', $restrictedAccounts[3]['name']);
  }

}
