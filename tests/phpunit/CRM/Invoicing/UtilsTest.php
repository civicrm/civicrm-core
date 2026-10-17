<?php

use Civi\Api4\OptionValue;

/**
 * @group headless
 */
class CRM_Invoicing_UtilsTest extends CiviUnitTestCase {

  protected function tearDown(): void {
    $this->relabelInvoicesOption('Invoices / Credit Notes');
    Civi::settings()->revert('invoicing');
    Civi::settings()->revert('user_dashboard_options');
    parent::tearDown();
  }

  /**
   * Toggling invoicing adds and removes the dashboard tab, whatever it is labelled.
   */
  public function testToggleInvoicingWithRelabelledDashboardOption(): void {
    $this->relabelInvoicesOption('Rechnungen / Gutschriften');
    $invoicesValue = CRM_Core_OptionGroup::getRowValues('user_dashboard_options', 'Invoices / Credit Notes')['value'];

    Civi::settings()->set('invoicing', TRUE);
    $this->assertContains($invoicesValue, $this->getDashboardOptions());
    $this->assertNotContains(FALSE, $this->getDashboardOptions());

    Civi::settings()->set('invoicing', FALSE);
    $this->assertNotContains($invoicesValue, $this->getDashboardOptions());
  }

  private function getDashboardOptions(): array {
    return $this->callAPISuccess('Setting', 'get', ['return' => 'user_dashboard_options'])['values'][CRM_Core_Config::domainID()]['user_dashboard_options'];
  }

  /**
   * @param string $label
   */
  private function relabelInvoicesOption(string $label): void {
    OptionValue::update(FALSE)
      ->addWhere('option_group_id:name', '=', 'user_dashboard_options')
      ->addWhere('name', '=', 'Invoices / Credit Notes')
      ->addValue('label', $label)
      ->execute();
  }

}
