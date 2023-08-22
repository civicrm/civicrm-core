<?php

namespace Civi\Shimmy\Mixins;

/**
 * Assert that the `xml/Menu/*.xml` mixin is working properly.
 *
 * This class defines the assertions to run when installing or uninstalling the extension.
 * It use called as part of E2E_Shimmy_LifecycleTest.
 *
 * @see E2E_Shimmy_LifecycleTest
 */
class MenuXmlTest extends \PHPUnit\Framework\Assert {

  /**
   * The URL of hte example route, `civicrm/shimmy/foobar`.
   * @var string
   */
  protected $url;

  public function testPreConditions($cv): void {
    $this->assertFileExists(static::getPath('/xml/Menu/shimmy.xml'), 'The shimmy extension must have a Menu XML file.');
  }

  public function testInstalled($cv): void {
    // The menu item is registered...
    $items = $cv->api4('Route', 'get', ['where' => [['path', '=', 'civicrm/shimmy/foobar']]]);
    $this->assertEquals('CRM_Shimmy_Page_FooBar', $items[0]['page_callback']);

    // And the menu item works...
    $this->url = cv('url civicrm/shimmy/foobar');
    $this->assertTrue(is_string($this->url));
    $response = file_get_contents($this->url);
    $this->assertMatchesRegularExpression(';hello world;', $response);
  }

  public function testDisabled($cv): void {
    $items = $cv->api4('Route', 'get', ['where' => [['path', '=', 'civicrm/shimmy/foobar']]]);
    $this->assertEmpty($items);

    $this->assertNotEmpty($this->url);
    $response = file_get_contents($this->url, FALSE, stream_context_create(['http' => ['ignore_errors' => TRUE]]));
    $this->assertDoesNotMatchRegularExpression(';hello world;', $response);
    $this->assertDoesNotMatchRegularExpression(';HTTP.*200.*;', $http_response_header[0]);
  }

  public function testUninstalled($cv): void {
    // Same as disabled.
    $this->testDisabled($cv);
  }

  protected static function getPath($suffix = ''): string {
    return dirname(__DIR__, 2) . $suffix;
  }

}
