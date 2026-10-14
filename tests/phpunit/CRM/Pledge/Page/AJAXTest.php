<?php

/**
 * Test class for CRM_Pledge_Page_AJAX.
 *
 * @group headless
 */
class CRM_Pledge_Page_AJAXTest extends CiviUnitTestCase {

  public function setUp(): void {
    parent::setUp();
    $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
  }

  public function tearDown(): void {
    unset($_SERVER['HTTP_X_REQUESTED_WITH']);
    $_REQUEST = [];
    $_GET = [];
    $_POST = [];
    parent::tearDown();
  }

  /**
   * Test getPledgeDefaults is denied when user lacks view permission on the pledge's contact.
   */
  public function testPledgeDefaultsForbiddenContactDenied(): void {
    $targetContactId = $this->individualCreate();
    $pledgeId = $this->pledgeCreate(['contact_id' => $targetContactId]);

    $this->createLoggedInUser();
    $this->setPermissions(['access CiviCRM', 'access CiviPledge']);

    $_POST = $_REQUEST = ['pid' => $pledgeId];

    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('You do not have permission to access this page.');
    CRM_Pledge_Page_AJAX::getPledgeDefaults();
  }

  /**
   * Test getPledgeDefaults succeeds when user has permission.
   */
  public function testPledgeDefaultsSuccess(): void {
    $targetContactId = $this->individualCreate();
    $pledgeId = $this->pledgeCreate(['contact_id' => $targetContactId]);

    $this->createLoggedInUser();
    $this->setPermissions(['administer CiviCRM', 'access CiviPledge', 'view all contacts']);

    $_POST = $_REQUEST = ['pid' => $pledgeId];

    try {
      CRM_Pledge_Page_AJAX::getPledgeDefaults();
      $this->fail('Expected CRM_Core_Exception_PrematureExitException');
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $this->assertIsArray($e->errorData);
    }
  }

}
