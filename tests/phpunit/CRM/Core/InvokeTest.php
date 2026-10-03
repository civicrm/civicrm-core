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
 * @group headless
 *
 * Several tests below expect a CRM_Core_Exception_PrematureExitException.
 * CRM_Utils_System_UnitTests throws this in place of actually exiting
 * (sendJSONResponse(), renderMaintenanceMessage(), civiExit()); its message
 * is the name of whichever of those methods was called, so asserting the
 * message confirms *which* exit path was taken, not just that some exit
 * happened.
 */
class CRM_Core_InvokeTest extends CiviUnitTestCase {

  protected $_apiversion = 4;
  private mixed $originalMaintenanceMode;
  private ?array $originalPermissions;

  public function setUp(): void {
    parent::setUp();
    // Clear any pre-existing session status messages so assertions are clean.
    CRM_Core_Session::singleton()->getStatus(TRUE);
    $this->originalMaintenanceMode = \Civi::settings()->get('core_maintenance_mode');
    $this->originalPermissions = CRM_Core_Config::singleton()->userPermissionClass->permissions;
  }

  public function tearDown(): void {
    \Civi::settings()->set('core_maintenance_mode', $this->originalMaintenanceMode);
    CRM_Core_Config::singleton()->userPermissionClass->permissions = $this->originalPermissions;
    parent::tearDown();
  }

  /**
   * When not in maintenance mode, checkMaintenanceMode() is a no-op.
   */
  public function testCheckMaintenanceModePassesWhenOff(): void {
    \Civi::settings()->set('core_maintenance_mode', '0');
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];
    $this->callCheckMaintenanceMode(['civicrm', 'contact', 'view']);
    $this->assertEmpty(CRM_Core_Session::singleton()->getStatus());
  }

  /**
   * "administer CiviCRM system" satisfies the bypass check on its own, but it's
   * narrower than "administer CiviCRM" (which implies it) — the admin-specific
   * wording is gated on the latter, so this user still gets the generic notice.
   */
  public function testCheckMaintenanceModeShowsGenericNoticeForAdministerSystemOnly(): void {
    \Civi::settings()->set('core_maintenance_mode', '1');
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['administer CiviCRM system'];
    $this->callCheckMaintenanceMode(['civicrm', 'contact', 'view']);
    $statuses = CRM_Core_Session::singleton()->getStatus(TRUE);
    $this->assertCount(1, $statuses);
    $this->assertSame('Site is under maintenance. Please check back shortly.', $statuses[0]['text']);
  }

  /**
   * Administrators get the statusCheck() text, so the two notices collapse into one.
   */
  public function testCheckMaintenanceModeShowsSingleNoticeToAdministrator(): void {
    \Civi::settings()->set('core_maintenance_mode', '1');
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['administer CiviCRM'];
    $this->callCheckMaintenanceMode(['civicrm', 'contact', 'view']);

    $ref = new ReflectionMethod(CRM_Core_Invoke::class, 'getAdminMaintenanceWarning');

    $statuses = CRM_Core_Session::singleton()->getStatus(TRUE);
    $this->assertCount(1, $statuses);
    $this->assertSame($ref->invoke(NULL), $statuses[0]['text']);
  }

  /**
   * Bypass users who are not administrators get the generic notice.
   */
  public function testCheckMaintenanceModeShowsGenericNoticeToBypassOnlyUser(): void {
    \Civi::settings()->set('core_maintenance_mode', '1');
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['cms:bypass maintenance mode'];
    $this->callCheckMaintenanceMode(['civicrm', 'contact', 'view']);
    $statuses = CRM_Core_Session::singleton()->getStatus(TRUE);
    $this->assertCount(1, $statuses);
    $this->assertSame('Site is under maintenance. Please check back shortly.', $statuses[0]['text']);
  }

  /**
   * civicrm/ajax/api4 passes through untouched — the API4 AJAX handler gates those.
   */
  public function testCheckMaintenanceModeSkipsApi4Ajax(): void {
    \Civi::settings()->set('core_maintenance_mode', '1');
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];
    $this->callCheckMaintenanceMode(['civicrm', 'ajax', 'api4', 'Contact', 'get']);
    $this->assertEmpty(CRM_Core_Session::singleton()->getStatus());
  }

  /**
   * Other civicrm/ajax/* pages (legacy CRM_*_Page_AJAX classes, REST) have no
   * maintenance-mode gate of their own, so they must still be blocked.
   */
  public function testCheckMaintenanceModeBlocksNonApi4Ajax(): void {
    \Civi::settings()->set('core_maintenance_mode', '1');
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];
    CRM_Core_Session::singleton()->set('userID', 1);
    try {
      $this->expectException(CRM_Core_Exception_PrematureExitException::class);
      $this->expectExceptionMessage('renderMaintenanceMessage');
      $this->callCheckMaintenanceMode(['civicrm', 'ajax', 'statusmsg']);
    }
    finally {
      CRM_Core_Session::singleton()->set('userID', NULL);
    }
  }

  /**
   * Home and auth paths show the maintenance notice but remain accessible.
   */
  public function testCheckMaintenanceModeShowsNoticeOnLoginPaths(): void {
    \Civi::settings()->set('core_maintenance_mode', '1');
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];
    CRM_Core_Session::singleton()->set('userID', NULL);
    $paths = [
      'civicrm/login',
      'civicrm/login/password',
      'civicrm/logout',
      'civicrm/mfa/totp',
      'civicrm/mfa/totp-setup',
    ];
    foreach ($paths as $path) {
      CRM_Core_Session::singleton()->getStatus(TRUE);
      $this->callCheckMaintenanceMode(explode('/', $path));
      $statuses = CRM_Core_Session::singleton()->getStatus(TRUE);
      $this->assertNotEmpty($statuses, "Expected maintenance notice on $path");
      $this->assertSame('Site is under maintenance. Please check back shortly.', $statuses[0]['text']);
    }
  }

  /**
   * Anonymous users who would be sent to the login form anyway are not blocked.
   */
  public function testCheckMaintenanceModeLetsAnonymousReachLoginFormOnDeniedRoutes(): void {
    \Civi::settings()->set('core_maintenance_mode', '1');
    CRM_Core_Config::singleton()->userPermissionClass->permissions = [];
    CRM_Core_Session::singleton()->set('userID', NULL);
    foreach (['civicrm/home', 'civicrm/contact/view'] as $path) {
      CRM_Core_Session::singleton()->getStatus(TRUE);
      $this->callCheckMaintenanceMode(explode('/', $path));
      $statuses = CRM_Core_Session::singleton()->getStatus(TRUE);
      $this->assertNotEmpty($statuses, "Expected maintenance notice on $path");
    }
  }

  /**
   * Anonymous users are still blocked on routes that are open to them.
   */
  public function testCheckMaintenanceModeBlocksAnonymousOnPublicRoutes(): void {
    \Civi::settings()->set('core_maintenance_mode', '1');
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];
    CRM_Core_Session::singleton()->set('userID', NULL);
    $this->expectException(CRM_Core_Exception_PrematureExitException::class);
    $this->expectExceptionMessage('renderMaintenanceMessage');
    $this->callCheckMaintenanceMode(['civicrm', 'contact', 'view']);
  }

  /**
   * Being logged in without the right permission does not earn the login-form exemption.
   */
  public function testCheckMaintenanceModeBlocksLoggedInUserEvenWhenRouteDenied(): void {
    \Civi::settings()->set('core_maintenance_mode', '1');
    CRM_Core_Config::singleton()->userPermissionClass->permissions = [];
    CRM_Core_Session::singleton()->set('userID', 1);
    try {
      $this->callCheckMaintenanceMode(['civicrm', 'contact', 'view']);
      $this->fail('Expected logged-in user to be blocked');
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $this->assertSame('renderMaintenanceMessage', $e->getMessage());
    }
    finally {
      CRM_Core_Session::singleton()->set('userID', NULL);
    }
  }

  /**
   * The maintenance page offers logout to logged-in users only.
   */
  public function testCheckMaintenanceModeShowsLogoutLinkOnlyWhenLoggedIn(): void {
    \Civi::settings()->set('core_maintenance_mode', '1');
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];
    foreach ([[1, TRUE], [NULL, FALSE]] as [$userId, $expectLink]) {
      CRM_Core_Session::singleton()->set('userID', $userId);
      try {
        $this->callCheckMaintenanceMode(['civicrm', 'contact', 'view']);
        $this->fail('Expected the maintenance page to be rendered');
      }
      catch (CRM_Core_Exception_PrematureExitException $e) {
        $this->assertSame('renderMaintenanceMessage', $e->getMessage());
        $this->assertSame($expectLink, str_contains($e->errorData['content'], 'civicrm/logout'));
      }
      finally {
        CRM_Core_Session::singleton()->set('userID', NULL);
      }
    }
  }

  /**
   * A logged-in user without bypass permission must not reach the home page.
   */
  public function testCheckMaintenanceModeBlocksHomeForLoggedInNonBypassUser(): void {
    \Civi::settings()->set('core_maintenance_mode', '1');
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];
    CRM_Core_Session::singleton()->set('userID', 1);
    $this->expectException(CRM_Core_Exception_PrematureExitException::class);
    $this->expectExceptionMessage('renderMaintenanceMessage');
    try {
      $this->callCheckMaintenanceMode(['civicrm', 'home']);
    }
    finally {
      CRM_Core_Session::singleton()->set('userID', NULL);
    }
  }

  /**
   * The asset builder is needed to render the login page, so it is never blocked.
   */
  public function testCheckMaintenanceModeSkipsAssetBuilder(): void {
    \Civi::settings()->set('core_maintenance_mode', '1');
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];
    $this->callCheckMaintenanceMode(['civicrm', 'asset', 'builder']);
    $this->assertEmpty(CRM_Core_Session::singleton()->getStatus());
  }

  /**
   * Snippet requests must not be used to sidestep the maintenance block.
   */
  public function testCheckMaintenanceModeBlocksSnippetForNonBypassUser(): void {
    \Civi::settings()->set('core_maintenance_mode', '1');
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];
    $_REQUEST['snippet'] = 'json';
    try {
      $this->callCheckMaintenanceMode(['civicrm', 'contact', 'view']);
      $this->fail('Expected snippet request to be blocked');
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $this->assertSame('sendJSONResponse', $e->getMessage());
    }
    finally {
      unset($_REQUEST['snippet']);
    }
  }

  /**
   * Non-login paths trigger content substitution for users without bypass.
   */
  public function testCheckMaintenanceModeSubstitutesContentForNonBypassUser(): void {
    \Civi::settings()->set('core_maintenance_mode', '1');
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];
    $this->expectException(CRM_Core_Exception_PrematureExitException::class);
    $this->expectExceptionMessage('renderMaintenanceMessage');
    $this->callCheckMaintenanceMode(['civicrm', 'contact', 'view']);
  }

  /**
   * Confirms checkMaintenanceMode() is actually wired into _invoke().
   */
  public function testInvokeBlocksDuringMaintenanceForNonBypassUser(): void {
    \Civi::settings()->set('core_maintenance_mode', '1');
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];
    $this->expectException(CRM_Core_Exception_PrematureExitException::class);
    $this->expectExceptionMessage('renderMaintenanceMessage');
    CRM_Core_Invoke::_invoke(['civicrm', 'contact', 'view']);
  }

  /**
   * Confirms the maintenance check doesn't block normal requests when off.
   */
  public function testInvokeRendersNormallyWhenMaintenanceModeOff(): void {
    \Civi::settings()->set('core_maintenance_mode', '0');
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];
    CRM_Core_Session::singleton()->getStatus(TRUE);
    $_SERVER['REQUEST_URI'] = 'civicrm/dashboard?reset=1';
    $_GET['q'] = 'civicrm/dashboard';
    ob_start();
    CRM_Core_Invoke::_invoke(['civicrm', 'dashboard']);
    $contents = ob_get_clean();
    unset($_GET['q'], $_REQUEST['q']);
    $this->assertStringNotContainsString('Maintenance Mode', $contents);
    $this->assertStringContainsString('crm-content-block', $contents);
    $this->assertEmpty(CRM_Core_Session::singleton()->getStatus());
  }

  /**
   * Helper: invoke the protected checkMaintenanceMode method via reflection.
   */
  private function callCheckMaintenanceMode(array $args): void {
    $ref = new ReflectionMethod(CRM_Core_Invoke::class, 'checkMaintenanceMode');
    $ref->invoke(NULL, $args);
  }

  /**
   * Test that no php errors come up invoking dashboard url for non-admins
   * Motivation: This currently fails on php 7.4 because of IDS and magicquotes.
   */
  public function testInvokeDashboardForNonAdmin(): void {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];

    $_SERVER['REQUEST_URI'] = 'civicrm/dashboard?reset=1';
    $_GET['q'] = 'civicrm/dashboard';

    $item = CRM_Core_Invoke::getItem(['civicrm/dashboard?reset=1']);
    ob_start();
    CRM_Core_Invoke::runItem($item);
    ob_end_clean();
  }

  /**
   * Test dashboard with something actually on it.
   */
  public function testInvokeDashboardWithGettingStartedDashlet(): void {
    $user_id = $this->createLoggedInUser();
    $this->callAPISuccess('DashboardContact', 'create', [
      'dashboard_id' => 2,
      'contact_id' => $user_id,
    ]);

    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];

    $_SERVER['REQUEST_URI'] = 'civicrm/dashboard?reset=1';
    $_GET['q'] = 'civicrm/dashboard';

    $item = CRM_Core_Invoke::getItem(['civicrm/dashboard?reset=1']);
    ob_start();
    CRM_Core_Invoke::runItem($item);
    ob_end_clean();
  }

  public function testOpeningSearchBuilder(): void {
    $_SERVER['REQUEST_URI'] = 'civicrm/contact/search/builder?reset=1';
    $_GET['q'] = 'civicrm/contact/search/builder';
    $_GET['reset'] = 1;

    $item = CRM_Core_Invoke::getItem([$_GET['q']]);
    ob_start();
    CRM_Core_Invoke::runItem($item);
    $contents = ob_get_clean();

    unset($_GET['reset']);
    $this->assertMatchesRegularExpression('/form.+id="Builder" class="CRM_Contact_Form_Search_Builder/', $contents);
  }

  public function testContactSummary(): void {
    $cid = $this->createTestEntity('Contact', [
      'first_name' => 'ContactPage',
      'last_name' => 'Summary',
      'do_not_phone' => 1,
      'gender_id:name' => 'Male',
    ])['id'];
    $_SERVER['REQUEST_URI'] = "civicrm/contact/view?cid={$cid}&reset=1";
    $_GET['q'] = 'civicrm/contact/view';
    $_GET['reset'] = $_REQUEST['reset'] = 1;
    $_GET['cid'] = $_REQUEST['cid'] = $cid;

    $item = CRM_Core_Invoke::getItem([$_GET['q']]);
    ob_start();
    CRM_Core_Invoke::runItem($item);
    $contents = ob_get_clean();

    unset($_GET['q'], $_REQUEST['q']);
    unset($_GET['reset'], $_REQUEST['reset']);
    unset($_GET['cid'], $_REQUEST['cid']);

    $this->assertStringContainsString("<div class=\"crm-content crm-contact_type_label\">\n      Individual\n    </div>", $contents);
    $this->assertStringContainsString("<div class=\"crm-content crm-contact-privacy_values font-red upper\">\n                  Do not phone<br/>                                                                                              </div>", $contents);
    $this->assertStringContainsString("<div class=\"crm-content crm-contact-gender_display\">Male</div>", $contents);
  }

}
