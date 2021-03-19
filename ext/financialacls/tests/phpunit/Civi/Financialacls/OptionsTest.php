<?php

namespace Civi\Financialacls;

// I fought the Autoloader and the autoloader won.
require_once 'BaseTestClass.php';

/**
 * @group headless
 */
class OptionsTest extends BaseTestClass {

  /**
   * Test buildMembershipTypes.
   */
  public function testBuildOptions(): void {
    $this->setupLoggedInUserWithLimitedFinancialTypeAccess();
    $options = \CRM_Financial_BAO_FinancialType::getAvailableFinancialTypes();
    $this->assertEquals(['Donation'], array_merge($options));
    $builtOptions = \CRM_Contribute_BAO_Contribution::buildOptions('financial_type_id', 'search');
    $this->assertEquals(['Donation'], array_merge($builtOptions));
  }

}
