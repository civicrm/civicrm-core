<?php

/**
 * @group headless
 */
class CRM_Admin_Form_Setting_LocalizationTest extends CiviUnitTestCase {

  /**
   * Test adding and removing a currency.
   */
  public function testUpdateCurrencies() {
    CRM_Admin_Form_Setting_Localization::updateEnabledCurrencies(['USD', 'CAD'], 'USD');
    CRM_Core_OptionGroup::flushAll();
    $currencies = array_keys(CRM_Core_OptionGroup::values('currencies_enabled'));
    $this->assertEquals(['CAD', 'USD'], $currencies, 'Unable to add a currency.');

    // Now try to remove it.
    CRM_Admin_Form_Setting_Localization::updateEnabledCurrencies(['USD'], 'USD');
    CRM_Core_OptionGroup::flushAll();
    $currencies = array_keys(CRM_Core_OptionGroup::values('currencies_enabled'));
    $this->assertEquals(['USD'], $currencies, 'Unable to remove a currency.');

    // Note in the form there's code to prevent removing the default. The
    // function we're testing here isn't the full form so we're not testing
    // that.
  }

}
