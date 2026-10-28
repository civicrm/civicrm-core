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
 * Test class for CRM_Mailing_Page_AJAX.
 *
 * @group headless
 */
class CRM_Mailing_Page_AJAXTest extends CiviUnitTestCase {

  public function setUp(): void {
    parent::setUp();
    $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
  }

  public function tearDown(): void {
    unset($_SERVER['HTTP_X_REQUESTED_WITH']);
    $_REQUEST = [];
    $_GET = [];
    $_POST = [];
    $this->quickCleanUp(['civicrm_msg_template']);
    parent::tearDown();
  }

  /**
   * Test template is denied for invalid or missing template ID.
   */
  public function testTemplateInvalidOrMissingIdDenied(): void {
    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM']);

    $_POST = $_REQUEST = ['tid' => 999999];

    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('You do not have permission to access this page.');
    CRM_Mailing_Page_AJAX::template();
  }

  /**
   * Test user-driven template retrieval succeeds for user with 'access CiviCRM'.
   */
  public function testTemplateUserDrivenSuccess(): void {
    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM']);

    $template = $this->createTestEntity('MessageTemplate', [
      'msg_title' => 'Standard Email Template',
      'msg_subject' => 'Hello {contact.display_name}',
      'msg_text' => 'This is test text body.',
      'msg_html' => '<p>This is test HTML body.</p>',
    ]);

    $_POST = $_REQUEST = ['tid' => $template['id']];

    try {
      CRM_Mailing_Page_AJAX::template();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $this->assertEquals('Hello {contact.display_name}', $e->errorData['subject']);
      $this->assertEquals('This is test text body.', $e->errorData['msg_text']);
      $this->assertEquals('<p>This is test HTML body.</p>', $e->errorData['msg_html']);
    }
  }

  /**
   * Test system workflow template retrieval is denied without workflow template permission.
   */
  public function testTemplateSystemWorkflowDeniedWithoutPermission(): void {
    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM']);

    $template = $this->createTestEntity('MessageTemplate', [
      'msg_title' => 'Receipt Workflow Template',
      'msg_subject' => 'Thank you for your contribution',
      'msg_text' => 'Receipt body',
      'msg_html' => '<p>Receipt body</p>',
      'workflow_name' => 'contribution_online_receipt',
    ]);

    $_POST = $_REQUEST = ['tid' => $template['id']];

    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('You do not have permission to access this page.');
    CRM_Mailing_Page_AJAX::template();
  }

  /**
   * Test system workflow template retrieval succeeds when user has workflow template permission.
   */
  public function testTemplateSystemWorkflowAllowedWithPermission(): void {
    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'edit system workflow message templates']);

    $template = $this->createTestEntity('MessageTemplate', [
      'msg_title' => 'Permitted Receipt Template',
      'msg_subject' => 'Workflow Subject',
      'msg_text' => 'Workflow Text',
      'msg_html' => '<p>Workflow HTML</p>',
      'workflow_name' => 'contribution_online_receipt',
    ]);

    $_POST = $_REQUEST = ['tid' => $template['id']];

    try {
      CRM_Mailing_Page_AJAX::template();
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $this->assertEquals('Workflow Subject', $e->errorData['subject']);
      $this->assertEquals('Workflow Text', $e->errorData['msg_text']);
      $this->assertEquals('<p>Workflow HTML</p>', $e->errorData['msg_html']);
    }
  }

  /**
   * Test getContactMailings is denied when user lacks view permission on contact.
   */
  public function testGetContactMailingsForbiddenContactDenied(): void {
    $targetContactId = $this->individualCreate([], 'target_contact');
    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'access CiviMail']);

    $_GET = $_REQUEST = ['contact_id' => $targetContactId];

    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('You do not have permission to access this page.');
    CRM_Mailing_Page_AJAX::getContactMailings();
  }

  /**
   * Test getContactMailings succeeds when user has permission to view contact.
   */
  public function testGetContactMailingsSuccess(): void {
    $targetContactId = $this->individualCreate([], 'target_contact');
    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'access CiviMail', 'view all contacts']);

    $_GET = $_REQUEST = ['contact_id' => $targetContactId];

    try {
      CRM_Mailing_Page_AJAX::getContactMailings();
      $this->fail('Expected CRM_Core_Exception_PrematureExitException');
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $this->assertIsArray($e->errorData);
      $this->assertArrayHasKey('data', $e->errorData);
    }
  }

}
