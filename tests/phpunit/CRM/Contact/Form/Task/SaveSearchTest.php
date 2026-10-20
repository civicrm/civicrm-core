<?php

use Civi\Api4\OptionValue;
use Civi\Test\FormTrait;
use Civi\Test\Invasive;

/**
 * @group headless
 */
class CRM_Contact_Form_Task_SaveSearchTest extends CiviUnitTestCase {
  use FormTrait;

  public function tearDown(): void {
    $this->relabelAccessControl('Access Control');
    $this->quickCleanup(['civicrm_group', 'civicrm_saved_search']);
    parent::tearDown();
  }

  /**
   * A smart group is not offered as access control group, whatever that type is labelled.
   *
   * @throws \CRM_Core_Exception
   */
  public function testSmartGroupDoesNotOfferAccessControl(): void {
    $this->relabelAccessControl('Zugangskontrolle');
    $this->individualCreate();
    $formWrapper = $this->getTestForm('CRM_Contact_Form_Search_Basic', ['radio_ts' => 'ts_all'])
      ->addSubsequentForm('CRM_Contact_Form_Task_SaveSearch', ['title' => 'Everyone', 'description' => '', 'parents' => ''])
      ->processForm();

    $form = Invasive::get([$formWrapper, 'subsequentForms'])['SaveSearch'];
    $offeredTypes = array_map(fn($checkbox) => $checkbox->getName(), $form->getElement('group_type')->getElements());
    $this->assertNotContains((string) CRM_Core_PseudoConstant::getKey('CRM_Contact_BAO_Group', 'group_type', 'Access Control'), $offeredTypes);
    $this->assertNotEmpty($offeredTypes);
  }

  /**
   * @param string $label
   */
  private function relabelAccessControl(string $label): void {
    OptionValue::update(FALSE)
      ->addWhere('option_group_id:name', '=', 'group_type')
      ->addWhere('name', '=', 'Access Control')
      ->addValue('label', $label)
      ->execute();
  }

}
