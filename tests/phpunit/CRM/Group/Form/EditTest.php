<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 * Test class for CRM_Group_Form_Edit.
 * @group headless
 */
class CRM_Group_Form_EditTest extends CiviUnitTestCase {

  public function tearDown(): void {
    $this->quickCleanup(['civicrm_group']);
    CRM_Core_Config::singleton()->userPermissionClass->permissions = NULL;
    parent::tearDown();
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
