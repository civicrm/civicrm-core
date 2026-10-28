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
    CRM_Core_Config::singleton()->userPermissionClass->permissions = NULL;
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

  /**
   * Test validation when creating a group without 'administer reserved groups'.
   */
  public function testCreateGroupValidationWithoutPermission(): void {
    $this->createLoggedInUser();

    CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'edit groups',
      'view all contacts',
    ];

    // Attempt to create a reserved group without permission
    $form = $this->getFormObject('CRM_Group_Form_Edit', [
      'title' => 'Reserved Group ' . uniqid(),
      'frontend_title' => 'Reserved Group Public',
      'visibility' => 'User and User Admin Only',
      'is_reserved' => 1,
    ]);
    $form->setAction(CRM_Core_Action::ADD);
    $form->buildForm();
    $this->assertFalse($form->validate());
    $this->assertArrayHasKey('is_reserved', $form->_errors);

    // Create a regular unreserved group without permission
    $form = $this->getFormObject('CRM_Group_Form_Edit', [
      'title' => 'Unreserved Group ' . uniqid(),
      'frontend_title' => 'Unreserved Group Public',
      'visibility' => 'User and User Admin Only',
      'is_reserved' => 0,
    ]);
    $form->setAction(CRM_Core_Action::ADD);
    $form->buildForm();
    $this->assertTrue($form->validate(), print_r($form->_errors, TRUE));
  }

  /**
   * Test validation when creating a group with 'administer reserved groups'.
   */
  public function testCreateGroupValidationWithPermission(): void {
    $this->createLoggedInUser();

    CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'edit groups',
      'view all contacts',
      'administer reserved groups',
    ];

    $form = $this->getFormObject('CRM_Group_Form_Edit', [
      'title' => 'Admin Reserved Group ' . uniqid(),
      'frontend_title' => 'Admin Reserved Group Public',
      'visibility' => 'User and User Admin Only',
      'is_reserved' => 1,
    ]);
    $form->setAction(CRM_Core_Action::ADD);
    $form->buildForm();
    $this->assertTrue($form->validate());
  }

  /**
   * Test validation when editing an existing group without permission.
   */
  public function testEditGroupValidationWithoutPermission(): void {
    $this->createLoggedInUser();

    $unreservedGroup = $this->groupCreate([
      'name' => 'unreserved_' . uniqid(),
      'title' => 'Regular Group ' . uniqid(),
      'is_reserved' => 0,
    ]);

    CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'edit groups',
      'view all contacts',
    ];

    // Attempting to make an unreserved group reserved should fail validation
    $form = $this->getFormObject('CRM_Group_Form_Edit', [
      'title' => 'Regular Group Updated',
      'frontend_title' => 'Regular Group Updated Public',
      'visibility' => 'User and User Admin Only',
      'is_reserved' => 1,
    ], ['id' => $unreservedGroup]);
    $form->setAction(CRM_Core_Action::UPDATE);
    $form->buildForm();
    $this->assertFalse($form->validate());
    $this->assertArrayHasKey('is_reserved', $form->_errors);

    // Updating unreserved group without setting is_reserved should succeed
    $form = $this->getFormObject('CRM_Group_Form_Edit', [
      'title' => 'Regular Group Legit Update',
      'frontend_title' => 'Regular Group Legit Update Public',
      'visibility' => 'User and User Admin Only',
      'is_reserved' => 0,
    ], ['id' => $unreservedGroup]);
    $form->setAction(CRM_Core_Action::UPDATE);
    $form->buildForm();
    $this->assertTrue($form->validate());
  }

}
