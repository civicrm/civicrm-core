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

use Civi\Test\FormWrapper;

/**
 * Test class for CRM_Contact_Form_Basic
 *
 * @package CiviCRM
 * @group headless
 */
class CRM_Contact_Form_Search_BasicTest extends CiviUnitTestCase {

  public function tearDown(): void {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = NULL;
    $this->quickCleanup(['civicrm_group']);
    parent::tearDown();
  }

  /**
   * Test the BasicSearch form.
   *
   * @todo - this test passes but it tests a scenario we would
   * ideally deprecate - ie coming up with a search that won't work,
   * trying it, logging an error, and then trying something that does work.
   */
  public function testBasicSearch(): void {
    /* @var CRM_Contact_Form_Search_Basic $form */
    $form = $this->getFormObject('CRM_Contact_Form_Search_Basic');
    $form->setAction(CRM_Core_Action::BASIC);
    // Order by the 5th field (ie country) descending.
    $_GET['crmSID'] = '5_d';
    $form->preProcess();
    $form->buildQuickForm();
    $form->postProcess();
  }

  /**
   * Test permission for Edit Group Settings in smog context.
   */
  public function testSmogGroupSettingsPermission(): void {
    $this->createLoggedInUser();

    $unreservedGroup = $this->createTestEntity('Group', ['title' => 'Unreserved Group', 'is_reserved' => 0], 'unreserved');
    $reservedGroup = $this->createTestEntity('Group', ['title' => 'Reserved Group', 'is_reserved' => 1], 'reserved');

    CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'edit groups',
      'view all contacts',
    ];

    $form = $this->getTestForm('CRM_Contact_Form_Search_Basic', [], ['context' => 'smog', 'gid' => $unreservedGroup['id']])
      ->processForm(FormWrapper::BUILT);
    $this->assertTrue($form->getTemplateVariable('permissionEditGroupSettings'));

    $form = $this->getTestForm('CRM_Contact_Form_Search_Basic', [], ['context' => 'smog', 'gid' => $reservedGroup['id']])
      ->processForm(FormWrapper::BUILT);
    $this->assertFalse($form->getTemplateVariable('permissionEditGroupSettings'));

    CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'edit groups',
      'view all contacts',
      'administer reserved groups',
    ];

    $form = $this->getTestForm('CRM_Contact_Form_Search_Basic', [], ['context' => 'smog', 'gid' => $reservedGroup['id']])
      ->processForm(FormWrapper::BUILT);
    $this->assertTrue($form->getTemplateVariable('permissionEditGroupSettings'));

    CRM_Core_Config::singleton()->userPermissionClass->permissions = [
      'access CiviCRM',
      'view all contacts',
      'administer reserved groups',
    ];

    $form = $this->getTestForm('CRM_Contact_Form_Search_Basic', [], ['context' => 'smog', 'gid' => $unreservedGroup['id']])
      ->processForm(FormWrapper::BUILT);
    $this->assertFalse($form->getTemplateVariable('permissionEditGroupSettings'));
  }

}
