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

  public function tearDown(): void {
    unset($_SERVER['HTTP_X_REQUESTED_WITH'], $_SERVER['REQUEST_URI'], $_GET['q']);
    \Civi::router()->clear();
    parent::tearDown();
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
    $cid = $this->individualCreate([
      'first_name' => 'ContactPage',
      'last_name' => 'Summary',
      'do_not_phone' => 1,
      'gender_id' => 'Male',
    ]);
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
   * Test that invoking a route marked with is_ajax throws an exception without XHR.
   */
  public function testInvokeAjaxRouteWithoutXhrThrowsException(): void {
    $route = [
      'page_callback' => [self::class, 'dummyAjaxCallback'],
      'access_callback' => 1,
    ];

    $this->hookClass->setHook('civicrm_alterMenu', function (&$items) use ($route) {
      $items['civicrm/test-ajax-route'] = $route;
    });
    \Civi::router()->clear();

    unset($_SERVER['HTTP_X_REQUESTED_WITH']);
    $_SERVER['REQUEST_URI'] = $_GET['q'] = 'civicrm/test-ajax-route';

    // No ajax set in the route, should succeed.
    $result = CRM_Core_Invoke::_invoke(['civicrm', 'test-ajax-route']);
    $this->assertEquals('ajax_success', $result);

    // Now set is_ajax on route
    $route['is_ajax'] = 1;
    $this->hookClass->setHook('civicrm_alterMenu', function (&$items) use ($route) {
      $items['civicrm/test-ajax-route'] = $route;
    });
    \Civi::router()->clear();

    $_SERVER['HTTP_X_REQUESTED_WITH'] = 'XMLHttpRequest';
    // Ajax set in the route, correct header sent, should succeed.
    $result = CRM_Core_Invoke::_invoke(['civicrm', 'test-ajax-route']);
    $this->assertEquals('ajax_success', $result);

    unset($_SERVER['HTTP_X_REQUESTED_WITH']);

    $this->expectException(CRM_Core_Exception::class);
    $this->expectExceptionMessage('SECURITY ALERT: Ajax requests can only be issued by javascript clients.');
    CRM_Core_Invoke::_invoke(['civicrm', 'test-ajax-route']);
  }

  public static function dummyAjaxCallback(): string {
    return 'ajax_success';
  }

}
