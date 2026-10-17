<?php

use Civi\Api4\OptionValue;
use Civi\Test\FormTrait;
use Civi\Test\Invasive;
use Civi\Test\FormWrapper;

/**
 * @group headless
 */
class CRM_Group_Form_EditTest extends CiviUnitTestCase {
  use FormTrait;

  public function tearDown(): void {
    $this->relabelGroupType('Access Control', 'Access Control');
    $this->relabelGroupType('Mailing List', 'Mailing List');
    $this->quickCleanup(['civicrm_group', 'civicrm_saved_search']);
    parent::tearDown();
  }

  /**
   * A smart group cannot be made an access control group, whatever that type is labelled.
   */
  public function testSmartGroupDoesNotOfferAccessControlWithRelabelledType(): void {
    $this->relabelGroupType('Access Control', 'Zugangskontrolle');
    $savedSearchID = $this->callAPISuccess('SavedSearch', 'create', ['form_values' => ['contact_type' => 'Individual']])['id'];
    $groupID = $this->groupCreate(['saved_search_id' => $savedSearchID]);

    $formWrapper = $this->getTestForm('CRM_Group_Form_Edit', [], ['action' => 'update', 'id' => $groupID])->processForm(FormWrapper::BUILT);
    $form = Invasive::get([$formWrapper, 'form']);
    $offeredTypes = array_map(fn($checkbox) => $checkbox->getName(), $form->getElement('group_type')->getElements());

    $this->assertNotContains((string) CRM_Core_PseudoConstant::getKey('CRM_Contact_BAO_Group', 'group_type', 'Access Control'), $offeredTypes);
    $this->assertContains((string) CRM_Core_PseudoConstant::getKey('CRM_Contact_BAO_Group', 'group_type', 'Mailing List'), $offeredTypes);
  }

  /**
   * The mailing list type is hidden from users without mailing access, whatever it is labelled.
   */
  public function testMailingListHiddenWithoutMailingAccessWithRelabelledType(): void {
    $this->relabelGroupType('Mailing List', 'Mailingliste');
    $this->createLoggedInUser();
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'edit groups'];

    $form = $this->getTestForm('CRM_Group_Form_Edit', [], ['action' => 'add'])->processForm(FormWrapper::BUILT);
    $form->getDefaultValues();

    $this->assertEquals(CRM_Core_PseudoConstant::getKey('CRM_Contact_BAO_Group', 'group_type', 'Mailing List'), $form->getTemplateVariable('hideMailingList'));
  }

  /**
   * @param string $name
   * @param string $label
   */
  private function relabelGroupType(string $name, string $label): void {
    OptionValue::update(FALSE)
      ->addWhere('option_group_id:name', '=', 'group_type')
      ->addWhere('name', '=', $name)
      ->addValue('label', $label)
      ->execute();
  }

}
