<?php

/**
 * @group headless
 */
class CRM_Admin_Form_Setting_LocalizationTest extends CiviUnitTestCase {

  /**
   * Test adding and removing a currency.
   *
   * @throws \CRM_Core_Exception
   */
  public function testUpdateCurrencies(): void {
    CRM_Admin_Form_Setting_Localization::updateEnabledCurrencies(['USD', 'CAD'], 'USD');

    $currencies = array_keys(CRM_Core_OptionGroup::values('currencies_enabled'));
    $this->assertEquals(['CAD', 'USD'], $currencies, 'Unable to add a currency.');

    // Now try to remove it.
    CRM_Admin_Form_Setting_Localization::updateEnabledCurrencies(['USD'], 'USD');

    $currencies = array_keys(CRM_Core_OptionGroup::values('currencies_enabled'));
    $this->assertEquals(['USD'], $currencies, 'Unable to remove a currency.');
  }

}
