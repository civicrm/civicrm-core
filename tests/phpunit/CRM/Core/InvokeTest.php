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
 */
class CRM_Core_InvokeTest extends CiviUnitTestCase {

  protected $_apiversion = 4;
  private mixed $originalMaintenanceMode;
  private ?array $originalPermissions;
  private array $listeners = [];
  private int $outputBufferLevel;

  public function setUp(): void {
    parent::setUp();
    $this->outputBufferLevel = ob_get_level();
    CRM_Core_Session::singleton()->getStatus(TRUE);
    $this->originalMaintenanceMode = \Civi::settings()->get('core_maintenance_mode');
    $this->originalPermissions = CRM_Core_Config::singleton()->userPermissionClass->permissions;
  }

  public function tearDown(): void {
    while (ob_get_level() > $this->outputBufferLevel) {
      ob_end_clean();
    }
    foreach ($this->listeners as $listener) {
      \Civi::dispatcher()->removeListener('civi.maintenance.access', $listener);
    }
    unset($_GET['q'], $_REQUEST['q']);
    CRM_Core_Session::singleton()->set('userID', NULL);
    \Civi::settings()->set('core_maintenance_mode', $this->originalMaintenanceMode);
    CRM_Core_Config::singleton()->userPermissionClass->permissions = $this->originalPermissions;
    parent::tearDown();
  }

  /**
   * Confirms MaintenanceMode::check() is wired into _invoke(): a non-bypass
   * user gets the notice in place of the page.
   */
  public function testInvokeBlocksDuringMaintenanceForNonBypassUser(): void {
    \Civi::settings()->set('core_maintenance_mode', '1');
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];
    try {
      $this->invokeDashboard();
      $this->fail('Expected the maintenance page to be rendered');
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      $this->assertSame('renderMaintenanceMessage', $e->getMessage());
      $this->assertStringContainsString('Site is under maintenance. Please check back shortly.', $e->errorData['content']);
    }
  }

  /**
   * Confirms the maintenance check doesn't block normal requests when off.
   */
  public function testInvokeRendersNormallyWhenMaintenanceModeOff(): void {
    \Civi::settings()->set('core_maintenance_mode', '0');
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];
    $contents = $this->invokeDashboard();
    $this->assertStringNotContainsString('Maintenance Mode', $contents);
    $this->assertStringContainsString('crm-content-block', $contents);
    $this->assertEmpty(CRM_Core_Session::singleton()->getStatus());
  }

  /**
   * A bypass user gets the real page, with the maintenance notice on top.
   */
  public function testInvokeRendersPageWithNoticeForBypassUser(): void {
    \Civi::settings()->set('core_maintenance_mode', '1');
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'cms:bypass maintenance mode'];
    $contents = $this->invokeDashboard();
    $this->assertStringContainsString('crm-content-block', $contents);
    $this->assertStringContainsString('Site is under maintenance. Please check back shortly.', $contents);
  }

  /**
   * A route allowed by a listener renders its real content, alongside the maintenance notice.
   */
  public function testInvokeRendersPageWhenListenerAllowsDuringMaintenance(): void {
    \Civi::settings()->set('core_maintenance_mode', '1');
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM'];
    $allowDashboard = fn(\Civi\Core\Event\GenericHookEvent $event) => $event->blocked = $event->path !== 'civicrm/dashboard';
    $this->listeners[] = $allowDashboard;
    \Civi::dispatcher()->addListener('civi.maintenance.access', $allowDashboard);
    $contents = $this->invokeDashboard();
    $this->assertStringContainsString('crm-content-block', $contents);
    $this->assertStringContainsString('Site is under maintenance. Please check back shortly.', $contents);
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

  /**
   * Runs the dashboard through _invoke() and returns the rendered output.
   */
  private function invokeDashboard(): string {
    CRM_Core_Session::singleton()->getStatus(TRUE);
    $_SERVER['REQUEST_URI'] = 'civicrm/dashboard?reset=1';
    $_GET['q'] = 'civicrm/dashboard';
    ob_start();
    CRM_Core_Invoke::_invoke(['civicrm', 'dashboard']);
    return ob_get_clean();
  }

}
