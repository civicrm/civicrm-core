<?php

use Civi\Api4\OptionValue;
use Civi\Test\FormTrait;
use Civi\Test\Invasive;

/**
 * @group headless
 */
class CRM_Admin_Form_Preferences_AddressTest extends CiviUnitTestCase {
  use FormTrait;

  public function tearDown(): void {
    $this->relabelCounty('County');
    Civi::settings()->revert('address_options');
    parent::tearDown();
  }

  /**
   * Enabling counties without county data prompts to populate them, whatever the option is labelled.
   */
  public function testEnablingCountyPromptsForCountyDataWithRelabelledOption(): void {
    $this->relabelCounty('Landkreis');
    $this->assertLessThan(10, CRM_Core_DAO::singleValueQuery('SELECT COUNT(*) FROM civicrm_county'));
    $countyValue = CRM_Core_OptionGroup::getRowValues('address_options', 'county')['value'];

    $form = $this->getTestForm('CRM_Admin_Form_Preferences_Address', ['address_options' => [$countyValue => 1], 'address_standardization_provider' => '', 'address_standardization_key' => '', 'address_standardization_secret' => '']);
    Invasive::get([$form, 'form'])->urlPath = ['civicrm', 'admin', 'setting', 'preferences', 'address'];
    $form->processForm();

    $statusTitles = array_column(CRM_Core_Session::singleton()->getStatus(TRUE), 'title');
    $this->assertContains('Populate counties', $statusTitles);
  }

  /**
   * @param string $label
   */
  private function relabelCounty(string $label): void {
    OptionValue::update(FALSE)
      ->addWhere('option_group_id:name', '=', 'address_options')
      ->addWhere('name', '=', 'county')
      ->addValue('label', $label)
      ->execute();
  }

}
