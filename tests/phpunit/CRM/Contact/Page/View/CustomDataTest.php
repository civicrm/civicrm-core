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
 * Test class for CRM_Contact_Page_View_CustomData and CRM_Contact_Form_CustomData
 *
 * @package CiviCRM
 * @group headless
 */
class CRM_Contact_Page_View_CustomDataTest extends CiviUnitTestCase implements HeadlessInterface {

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
   * Test that CRM_Contact_Page_View_CustomData denies access on edit actions
   * when the user lacks edit permission on the contact.
   */
  public function testCustomDataPageDeniedWithoutContactEditPermission(): void {
    $contact = $this->createTestEntity('Contact', [
      'contact_type' => 'Individual',
      'first_name' => 'Target',
      'last_name' => 'Contact',
    ], 'contact_page');
    $customGroup = $this->createTestEntity('CustomGroup', [
      'title' => 'Page Test Group',
      'extends' => 'Individual',
      'style' => 'Inline',
    ], 'group_page');

    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'view all contacts']);

    $_REQUEST = $_GET = [
      'cid' => $contact['id'],
      'gid' => $customGroup['id'],
      'action' => CRM_Core_Action::UPDATE,
    ];

    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('You do not have permission to access this page.');

    $page = new CRM_Contact_Page_View_CustomData();
    $page->run();
  }

  /**
   * Test that CRM_Contact_Form_CustomData denies access when the user
   * lacks edit permission on the contact.
   */
  public function testCustomDataFormDeniedWithoutContactEditPermission(): void {
    $contact = $this->createTestEntity('Contact', [
      'contact_type' => 'Individual',
      'first_name' => 'Target',
      'last_name' => 'Contact',
    ], 'contact_form');
    $customGroup = $this->createTestEntity('CustomGroup', [
      'title' => 'Form Test Group',
      'extends' => 'Individual',
      'style' => 'Inline',
    ], 'group_form');

    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'view all contacts']);

    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('You do not have permission to access this page.');

    $this->getTestForm('CRM_Contact_Form_CustomData', [], [
      'tableId' => $contact['id'],
      'groupID' => $customGroup['id'],
    ])->processForm(FormWrapper::PREPROCESSED);
  }

  /**
   * Test that multi-record CRM_Contact_Form_CustomData denies access when
   * user lacks edit permission on the contact.
   */
  public function testMultiRecordCustomDataFormDeniedWithoutContactEditPermission(): void {
    $contact = $this->createTestEntity('Contact', [
      'contact_type' => 'Individual',
      'first_name' => 'Target',
      'last_name' => 'Contact',
    ], 'contact_multi');
    $customGroup = $this->createTestEntity('CustomGroup', [
      'title' => 'Multi Group',
      'extends' => 'Individual',
      'is_multiple' => 1,
      'style' => 'Tab',
    ], 'group_multi');

    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'view all contacts']);

    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('You do not have permission to access this page.');

    $this->getTestForm('CRM_Contact_Form_CustomData', [], [
      'type' => 'Individual',
      'multiRecordDisplay' => 'single',
      'entityID' => $contact['id'],
      'groupID' => $customGroup['id'],
      'mode' => 'add',
    ])->processForm(FormWrapper::PREPROCESSED);
  }

}
