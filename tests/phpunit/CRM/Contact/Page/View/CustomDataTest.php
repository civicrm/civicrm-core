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

use Civi\Test\HeadlessInterface;

/**
 * Test class for CRM_Contact_Page_View_CustomData
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
   * Test that CRM_Contact_Page_View_CustomData denies access when the user
   * lacks permission to view the contact.
   */
  public function testCustomDataPageDeniedWithoutContactViewPermission(): void {
    $contact = $this->createTestEntity('Contact', [
      'contact_type' => 'Individual',
      'first_name' => 'Target',
      'last_name' => 'Contact',
    ], 'contact_page');
    $customGroup = $this->createTestEntity('CustomGroup', [
      'title' => __FUNCTION__,
      'extends' => 'Individual',
      'style' => 'Inline',
    ], 'group_page');

    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM']);

    $_REQUEST = $_GET = [
      'cid' => $contact['id'],
      'gid' => $customGroup['id'],
    ];

    $this->expectException(CRM_Core_Exception_PrematureExitException::class);
    $this->expectExceptionMessage('You do not have the necessary permission to view this contact.');

    $page = new CRM_Contact_Page_View_CustomData();
    $page->run();
  }

  /**
   * Test that CRM_Contact_Page_View_CustomData renders when the user
   * has view permission on the contact.
   */
  public function testCustomDataPageAllowedWithContactViewPermission(): void {
    $contact = $this->createTestEntity('Contact', [
      'contact_type' => 'Individual',
      'first_name' => 'Target',
      'last_name' => 'Contact',
    ], 'contact_page_view');
    $customGroup = $this->createTestEntity('CustomGroup', [
      'title' => __FUNCTION__,
      'extends' => 'Individual',
      'style' => 'Inline',
    ], 'group_page_view');

    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'view all contacts']);

    $_REQUEST = $_GET = [
      'cid' => $contact['id'],
      'gid' => $customGroup['id'],
    ];

    $page = new CRM_Contact_Page_View_CustomData();
    ob_start();
    $page->run();
    $content = ob_get_clean();

    $this->assertSame((int) $customGroup['id'], (int) $page->getTemplateVars('groupId'));
    $this->assertSame((int) $contact['id'], (int) $page->getTemplateVars('contactId'));
  }

}
