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

namespace Civi\Core;

use Civi\Core\Event\GenericHookEvent;

/**
 * @group headless
 */
class MaintenanceModeTest extends \CiviUnitTestCase {

  protected $_apiversion = 4;
  private mixed $originalMaintenanceMode;
  private ?array $originalPermissions;
  private array $listeners = [];

  public function setUp(): void {
    parent::setUp();
    \CRM_Core_Session::singleton()->getStatus(TRUE);
    $this->originalMaintenanceMode = \Civi::settings()->get('core_maintenance_mode');
    $this->originalPermissions = \CRM_Core_Config::singleton()->userPermissionClass->permissions;
    $this->resetHttpResponse();
  }

  public function tearDown(): void {
    foreach ($this->listeners as $listener) {
      \Civi::dispatcher()->removeListener('civi.maintenance.access', $listener);
    }
    unset($_REQUEST['snippet']);
    \CRM_Core_Session::singleton()->set('userID', NULL);
    \Civi::settings()->set('core_maintenance_mode', $this->originalMaintenanceMode);
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = $this->originalPermissions;
    $this->resetHttpResponse();
    parent::tearDown();
  }

  /**
   * When not in maintenance mode, check() is a no-op.
   */
  public function testCheckPassesWhenOff(): void {
    \Civi::settings()->set('core_maintenance_mode', '0');
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];
    $this->assertCheckAllows(['civicrm', 'contact', 'view']);
    $this->assertEmpty(\CRM_Core_Session::singleton()->getStatus());
  }

  /**
   * "administer CiviCRM system" satisfies the bypass check on its own, but it's
   * narrower than "administer CiviCRM" (which implies it) — the admin-specific
   * wording is gated on the latter, so this user still gets the generic notice.
   */
  public function testCheckShowsGenericNoticeForAdministerSystemOnly(): void {
    \Civi::settings()->set('core_maintenance_mode', '1');
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['administer CiviCRM system'];
    $this->assertCheckAllows(['civicrm', 'contact', 'view']);
    $statuses = \CRM_Core_Session::singleton()->getStatus(TRUE);
    $this->assertCount(1, $statuses);
    $this->assertSame('Site is under maintenance. Please check back shortly.', $statuses[0]['text']);
  }

  /**
   * Administrators get the statusCheck() text, so the two notices collapse into one.
   */
  public function testCheckShowsSingleNoticeToAdministrator(): void {
    \Civi::settings()->set('core_maintenance_mode', '1');
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['administer CiviCRM'];
    $this->assertCheckAllows(['civicrm', 'contact', 'view']);
    $statuses = \CRM_Core_Session::singleton()->getStatus(TRUE);
    $this->assertCount(1, $statuses);
    $this->assertSame(MaintenanceMode::getAdminWarning(), $statuses[0]['text']);
  }

  /**
   * Bypass users who are not administrators get the generic notice.
   */
  public function testCheckShowsGenericNoticeToBypassOnlyUser(): void {
    \Civi::settings()->set('core_maintenance_mode', '1');
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['cms:bypass maintenance mode'];
    $this->assertCheckAllows(['civicrm', 'contact', 'view']);
    $statuses = \CRM_Core_Session::singleton()->getStatus(TRUE);
    $this->assertCount(1, $statuses);
    $this->assertSame('Site is under maintenance. Please check back shortly.', $statuses[0]['text']);
  }

  /**
   * civicrm/ajax/api4 passes through untouched — the API4 AJAX handler gates those.
   */
  public function testCheckSkipsApi4Ajax(): void {
    \Civi::settings()->set('core_maintenance_mode', '1');
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];
    $this->assertCheckAllows(['civicrm', 'ajax', 'api4', 'Contact', 'get']);
    $this->assertEmpty(\CRM_Core_Session::singleton()->getStatus());
  }

  /**
   * Other civicrm/ajax/* pages (legacy CRM_*_Page_AJAX classes, REST) have no
   * maintenance-mode gate of their own, so they are still blocked.
   */
  public function testCheckBlocksNonApi4Ajax(): void {
    \Civi::settings()->set('core_maintenance_mode', '1');
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];
    \CRM_Core_Session::singleton()->set('userID', 1);
    $this->expectException(\CRM_Core_Exception_PrematureExitException::class);
    $this->expectExceptionMessage('renderMaintenanceMessage');
    MaintenanceMode::check(['civicrm', 'ajax', 'statusmsg']);
  }

  /**
   * Login, logout, password reset and MFA paths are not blocked; the maintenance notice is queued.
   */
  public function testCheckDoesNotBlockLoginPaths(): void {
    \Civi::settings()->set('core_maintenance_mode', '1');
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];
    \CRM_Core_Session::singleton()->set('userID', NULL);
    $paths = [
      'civicrm/login',
      'civicrm/login/password',
      'civicrm/logout',
      'civicrm/mfa/totp',
      'civicrm/mfa/totp-setup',
    ];
    foreach ($paths as $path) {
      \CRM_Core_Session::singleton()->getStatus(TRUE);
      $this->assertCheckAllows(explode('/', $path));
      $statuses = \CRM_Core_Session::singleton()->getStatus(TRUE);
      $this->assertNotEmpty($statuses, "Expected maintenance notice on $path");
      $this->assertSame('Site is under maintenance. Please check back shortly.', $statuses[0]['text']);
    }
  }

  /**
   * Anonymous users on routes they cannot access are not blocked, so the normal
   * flow can redirect them to the login form; the maintenance notice is queued.
   */
  public function testCheckDoesNotBlockAnonymousOnDeniedRoutes(): void {
    \Civi::settings()->set('core_maintenance_mode', '1');
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [];
    \CRM_Core_Session::singleton()->set('userID', NULL);
    foreach (['civicrm/home', 'civicrm/contact/view'] as $path) {
      \CRM_Core_Session::singleton()->getStatus(TRUE);
      $this->assertCheckAllows(explode('/', $path));
      $statuses = \CRM_Core_Session::singleton()->getStatus(TRUE);
      $this->assertNotEmpty($statuses, "Expected maintenance notice on $path");
      $this->assertSame('Site is under maintenance. Please check back shortly.', $statuses[0]['text']);
    }
  }

  /**
   * Anonymous users are blocked on a route they are permitted to access
   * (simulated by granting 'access CiviCRM'): the page content is replaced
   * with the notice, in a 503 with Retry-After.
   */
  public function testCheckBlocksAnonymousOnPermittedRoutes(): void {
    \Civi::settings()->set('core_maintenance_mode', '1');
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];
    \CRM_Core_Session::singleton()->set('userID', NULL);
    try {
      MaintenanceMode::check(['civicrm', 'contact', 'view']);
      $this->fail('Expected the maintenance page to be rendered');
    }
    catch (\CRM_Core_Exception_PrematureExitException $e) {
      $this->assertSame('renderMaintenanceMessage', $e->getMessage());
      $this->assertStringContainsString('Site is under maintenance. Please check back shortly.', $e->errorData['content']);
      $this->assertSame(503, http_response_code());
      $this->assertContains('Retry-After: 300', \Civi::$statics[\CRM_Utils_System_UnitTests::class]['header']);
    }
  }

  /**
   * Logged-in users are blocked even on routes they cannot access: only anonymous
   * users are let through to be redirected to the login form.
   */
  public function testCheckBlocksLoggedInUserEvenWhenRouteDenied(): void {
    \Civi::settings()->set('core_maintenance_mode', '1');
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = [];
    \CRM_Core_Session::singleton()->set('userID', 1);
    try {
      MaintenanceMode::check(['civicrm', 'contact', 'view']);
      $this->fail('Expected logged-in user to be blocked');
    }
    catch (\CRM_Core_Exception_PrematureExitException $e) {
      $this->assertSame('renderMaintenanceMessage', $e->getMessage());
    }
  }

  /**
   * The maintenance page offers logout to logged-in users only.
   */
  public function testCheckShowsLogoutLinkOnlyWhenLoggedIn(): void {
    \Civi::settings()->set('core_maintenance_mode', '1');
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];
    foreach ([[1, TRUE], [NULL, FALSE]] as [$userId, $expectLink]) {
      \CRM_Core_Session::singleton()->set('userID', $userId);
      try {
        MaintenanceMode::check(['civicrm', 'contact', 'view']);
        $this->fail('Expected the maintenance page to be rendered');
      }
      catch (\CRM_Core_Exception_PrematureExitException $e) {
        $this->assertSame('renderMaintenanceMessage', $e->getMessage());
        $this->assertSame($expectLink, str_contains($e->errorData['content'], 'civicrm/logout'));
      }
    }
  }

  /**
   * A logged-in user without bypass permission is blocked from the home page.
   */
  public function testCheckBlocksHomeForLoggedInNonBypassUser(): void {
    \Civi::settings()->set('core_maintenance_mode', '1');
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];
    \CRM_Core_Session::singleton()->set('userID', 1);
    $this->expectException(\CRM_Core_Exception_PrematureExitException::class);
    $this->expectExceptionMessage('renderMaintenanceMessage');
    MaintenanceMode::check(['civicrm', 'home']);
  }

  /**
   * The asset builder is needed to render the login page, so it is never blocked.
   */
  public function testCheckSkipsAssetBuilder(): void {
    \Civi::settings()->set('core_maintenance_mode', '1');
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];
    $this->assertCheckAllows(['civicrm', 'asset', 'builder']);
    $this->assertEmpty(\CRM_Core_Session::singleton()->getStatus());
  }

  /**
   * Snippet requests do not sidestep the maintenance block: a non-bypass user gets a 503 JSON response.
   */
  public function testCheckBlocksSnippetForNonBypassUser(): void {
    \Civi::settings()->set('core_maintenance_mode', '1');
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];
    $_REQUEST['snippet'] = 'json';
    try {
      MaintenanceMode::check(['civicrm', 'contact', 'view']);
      $this->fail('Expected snippet request to be blocked');
    }
    catch (\CRM_Core_Exception_PrematureExitException $e) {
      $this->assertSame('sendJSONResponse', $e->getMessage());
      $this->assertSame(503, $e->errorData['status_code']);
      $this->assertSame('Site is under maintenance. Please check back shortly.', $e->errorData['status_message']);
      $this->assertContains('Retry-After: 300', \Civi::$statics[\CRM_Utils_System_UnitTests::class]['header']);
    }
  }

  /**
   * A listener can allow a route that would otherwise be blocked; the notice is still shown.
   */
  public function testCheckLetsListenerAllowBlockedRoute(): void {
    \Civi::settings()->set('core_maintenance_mode', '1');
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];
    $allowContactView = fn(GenericHookEvent $event) => $event->blocked = $event->path !== 'civicrm/contact/view';
    $this->addAccessListener($allowContactView);
    $this->assertCheckAllows(['civicrm', 'contact', 'view']);
    $statuses = \CRM_Core_Session::singleton()->getStatus(TRUE);
    $this->assertCount(1, $statuses);
    $this->assertSame('Site is under maintenance. Please check back shortly.', $statuses[0]['text']);
  }

  /**
   * Allowing one route in a listener does not open up the others.
   */
  public function testCheckListenerAllowingOneRouteStillBlocksOthers(): void {
    \Civi::settings()->set('core_maintenance_mode', '1');
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];
    $allowContactView = fn(GenericHookEvent $event) => $event->blocked = $event->path !== 'civicrm/contact/view';
    $this->addAccessListener($allowContactView);
    $this->expectException(\CRM_Core_Exception_PrematureExitException::class);
    $this->expectExceptionMessage('renderMaintenanceMessage');
    MaintenanceMode::check(['civicrm', 'dashboard']);
  }

  /**
   * A listener can block a route that the built-in rules would allow.
   */
  public function testCheckLetsListenerBlockAllowedRoute(): void {
    \Civi::settings()->set('core_maintenance_mode', '1');
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];
    $this->addBlockAllListener();
    $this->expectException(\CRM_Core_Exception_PrematureExitException::class);
    $this->expectExceptionMessage('renderMaintenanceMessage');
    MaintenanceMode::check(['civicrm', 'login']);
  }

  /**
   * The API4 AJAX handler and the asset builder are not hookable.
   */
  public function testCheckListenerCannotBlockApi4AjaxOrAssetBuilder(): void {
    \Civi::settings()->set('core_maintenance_mode', '1');
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];
    $this->addBlockAllListener();
    $this->assertCheckAllows(['civicrm', 'ajax', 'api4', 'Contact', 'get']);
    $this->assertCheckAllows(['civicrm', 'asset', 'builder']);
    $this->assertEmpty(\CRM_Core_Session::singleton()->getStatus());
  }

  /**
   * Bypass users are never blocked, so listeners are not consulted.
   */
  public function testCheckSkipsListenersForBypassUser(): void {
    \Civi::settings()->set('core_maintenance_mode', '1');
    \CRM_Core_Config::singleton()->userPermissionClass->permissions = ['cms:bypass maintenance mode'];
    $this->addBlockAllListener();
    $this->assertCheckAllows(['civicrm', 'contact', 'view']);
    $this->assertCount(1, \CRM_Core_Session::singleton()->getStatus(TRUE));
  }

  private function addAccessListener(callable $listener): void {
    $this->listeners[] = $listener;
    \Civi::dispatcher()->addListener('civi.maintenance.access', $listener);
  }

  private function addBlockAllListener(): void {
    $this->addAccessListener(fn(GenericHookEvent $event) => $event->blocked = TRUE);
  }

  private function resetHttpResponse(): void {
    http_response_code(200);
    unset(\Civi::$statics[\CRM_Utils_System_UnitTests::class]['header']);
  }

  private function assertCheckAllows(array $args): void {
    try {
      MaintenanceMode::check($args);
    }
    catch (\CRM_Core_Exception_PrematureExitException $e) {
      $this->fail(implode('/', $args) . ' was blocked (' . $e->getMessage() . ')');
    }
  }

}
