<?php

namespace Civi\Shimmy\Mixins;

/**
 * Assert that the settings-page is created.
 *
 * This class defines the assertions to run when installing or uninstalling the extension.
 * It use called as part of E2E_Shimmy_LifecycleTest.
 *
 * @see E2E_Shimmy_LifecycleTest
 */
class SettingsAdminTest extends \PHPUnit\Framework\Assert {

  public function testPreConditions($cv): void {
    // $this->assertFileExists(static::getPath('/xml/Menu/shimmy.xml'), 'The shimmy extension must have a Menu XML file.');
  }

  public function testInstalled($cv): void {
    // The permission is registered...
    $items = $cv->api4('Permission', 'get', ['where' => [['name', '=', 'administer shimmy']]]);
    $this->assertEquals(TRUE, $items[0]['is_active']);

    // The route is registered...
    $items = $cv->api4('Route', 'get', ['where' => [['path', '=', 'civicrm/admin/setting/shimmy']]]);
    $this->assertEquals('CRM_Admin_Form_Generic', $items[0]['page_callback']);

    // The nav-menu is registered...
    $navMenu = $this->adminHttp('civicrm/ajax/navmenu');
    $this->assertTrue(static::hasPathLikeExpr(';civicrm/admin/setting/shimmy;', $navMenu), 'Page should be in nav-menu');

    // And the route works...
    $pageContent = $this->adminHttp('civicrm/admin/setting/shimmy?reset=1');
    $this->assertMatchesRegularExpression(';crm-setting-block;', $pageContent);
  }

  public function testDisabled($cv): void {
    $items = $cv->api4('Permission', 'get', ['where' => [['name', '=', 'administer shimmy']]]);
    $this->assertEquals(FALSE, $items[0]['is_active']);

    $items = $cv->api4('Route', 'get', ['where' => [['path', '=', 'civicrm/admin/setting/shimmy']]]);
    $this->assertEmpty($items);

    $navMenu = $this->adminHttp('civicrm/ajax/navmenu');
    $this->assertFalse(static::hasPathLikeExpr(';civicrm/admin/setting/shimmy;', $navMenu), 'Page should not be in nav-menu');

    $pageContent = $this->adminHttp('civicrm/admin/setting/shimmy?reset=1');
    $this->assertDoesNotMatchRegularExpression(';crm-setting-block;', $pageContent);
  }

  public function testUninstalled($cv): void {
    $items = $cv->api4('Permission', 'get', ['where' => [['name', '=', 'administer shimmy']]]);
    $this->assertEquals(0, count($items));

    $items = $cv->api4('Route', 'get', ['where' => [['path', '=', 'civicrm/admin/setting/shimmy']]]);
    $this->assertEmpty($items);

    $navMenu = $this->adminHttp('civicrm/ajax/navmenu');
    $this->assertFalse(static::hasPathLikeExpr(';civicrm/admin/setting/shimmy;', $navMenu), 'Page should not be in nav-menu');

    $pageContent = $this->adminHttp('civicrm/admin/setting/shimmy?reset=1');
    $this->assertDoesNotMatchRegularExpression(';crm-setting-block;', $pageContent);
  }

  protected static function getPath($suffix = ''): string {
    return dirname(__DIR__, 2) . $suffix;
  }

  protected static function adminHttp(string $path) {
    $cmd = sprintf('http %s --login -U %s', escapeshellarg($path), escapeshellarg($GLOBALS['_CV']['ADMIN_USER']));
    return cv($cmd, 'raw');
  }

  protected static function hasPathLikeExpr($pattern, $httpResponse) {
    // URL formatting varies by UF and content-type... we just want something generally close...
    $httpResponse = str_replace('%2F', '/', $httpResponse);
    $httpResponse = str_replace('%2f', '/', $httpResponse);
    $httpResponse = str_replace('\\/', '/', $httpResponse);
    return (bool) preg_match($pattern, $httpResponse);
  }

}
