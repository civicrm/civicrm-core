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
use Civi\Test\HeadlessInterface;

/**
 * Test class for Group and Tag permission enforcement on contact view pages/forms
 *
 * @package CiviCRM
 * @group headless
 */
class CRM_Contact_Page_View_GroupAndTagPermissionTest extends CiviUnitTestCase implements HeadlessInterface {

  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->apply();
  }

  public function tearDown(): void {
    CRM_Utils_Hook::singleton()->reset();
    CRM_Core_Session::singleton()->reset();
    $_REQUEST = $_GET = $_POST = [];
    parent::tearDown();
  }

  /**
   * Test that CRM_Contact_Page_View_GroupContact::del denies deleting or re-joining
   * using a groupContactId that belongs to another contact.
   */
  public function testGroupContactDelRejectsOtherContactsGcid(): void {
    $contactA = $this->createTestEntity('Contact', ['contact_type' => 'Individual'], 'contact_a');
    $contactB = $this->createTestEntity('Contact', ['contact_type' => 'Individual'], 'contact_b');
    $group = $this->createTestEntity('Group', ['title' => 'Test Group ' . rand(1000, 9999)], 'group_a');

    $groupContactB = $this->createTestEntity('GroupContact', [
      'contact_id' => $contactB['id'],
      'group_id' => $group['id'],
      'status' => 'Added',
    ], 'gc_b');

    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'edit all contacts']);

    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('You do not have permission to access this page.');

    CRM_Contact_Page_View_GroupContact::del($groupContactB['id'], 'o', $contactA['id']);
  }

  /**
   * Test that CRM_Contact_Page_View_GroupContact denies delete action
   * when the user lacks edit permission on the contact.
   */
  public function testGroupContactPageDeniedWithoutContactEditPermission(): void {
    $contact = $this->createTestEntity('Contact', ['contact_type' => 'Individual'], 'contact_noperm');
    $group = $this->createTestEntity('Group', ['title' => 'Test Group ' . rand(1000, 9999)], 'group_noperm');
    $gc = $this->createTestEntity('GroupContact', [
      'contact_id' => $contact['id'],
      'group_id' => $group['id'],
      'status' => 'Added',
    ], 'gc_noperm');

    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'view all contacts']);

    $_REQUEST = $_GET = [
      'cid' => $contact['id'],
      'gcid' => $gc['id'],
      'action' => CRM_Core_Action::DELETE,
      'st' => 'o',
    ];

    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('You do not have permission to access this page.');

    $page = new CRM_Contact_Page_View_GroupContact();
    $page->run();
  }

  /**
   * Test that CRM_Contact_Form_GroupContact denies adding a group
   * when the user lacks edit permission on the contact.
   */
  public function testGroupContactFormDeniedWithoutContactEditPermission(): void {
    $contact = $this->createTestEntity('Contact', ['contact_type' => 'Individual'], 'contact_form_noperm');
    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'view all contacts']);

    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('You do not have permission to access this page.');

    $this->getTestForm('CRM_Contact_Form_GroupContact', [], [
      'cid' => $contact['id'],
    ])->processForm(FormWrapper::PREPROCESSED);
  }

  /**
   * Test that self-service on user dashboard cannot join an admin/private group.
   */
  public function testGroupContactFormUserContextCannotJoinPrivateGroup(): void {
    $userId = $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'access Contact Dashboard']);

    $adminGroup = $this->createTestEntity('Group', [
      'title' => 'Admin Only Group ' . rand(1000, 9999),
      'visibility' => 'User and User Admin Only',
    ], 'admin_group');

    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('You do not have permission to access this page.');

    $this->getTestForm('CRM_Contact_Form_GroupContact', [
      'group_id' => $adminGroup['id'],
    ], [
      'cid' => $userId,
      'context' => 'user',
    ])->processForm(FormWrapper::SUBMITTED);
  }

  /**
   * Test that CRM_Tag_Form_Tag freezes when user lacks edit permission on contact,
   * and rejects postProcess.
   */
  public function testTagFormDeniedWithoutContactEditPermission(): void {
    $contact = $this->createTestEntity('Contact', ['contact_type' => 'Individual'], 'contact_tag');
    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'view all contacts']);

    $form = $this->getTestForm('CRM_Tag_Form_Tag', [], [
      'cid' => $contact['id'],
    ])->processForm(FormWrapper::BUILT);

    $this->assertTrue($form->getTemplateVariable('readOnly'));

    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('You do not have permission to access this page.');

    $this->getTestForm('CRM_Tag_Form_Tag', [
      'tagList' => [1 => 1],
    ], [
      'cid' => $contact['id'],
    ])->processForm(FormWrapper::SUBMITTED);
  }

}
