<?php

namespace Civi\Shimmy\Mixins;

use Civi\Test\HttpTestTrait;

/**
 * Assert that the `xml/Menu/*.xml` mixin is working properly.
 *
 * This class defines the assertions to run when installing or uninstalling the extension.
 * It use called as part of E2E_Shimmy_LifecycleTest.
 *
 * @see E2E_Shimmy_LifecycleTest
 */
class MenuXmlTest extends \PHPUnit\Framework\Assert {

  use HttpTestTrait;

  public function testPreConditions($cv): void {
    $this->assertFileExists(static::getPath('/xml/Menu/shimmy.xml'), 'The shimmy extension must have a Menu XML file.');
  }

  public function testInstalled($cv): void {
    // The menu item is registered...
    $items = $cv->api4('Route', 'get', ['where' => [['path', '=', 'civicrm/shimmy/foobar']]]);
    $this->assertEquals('CRM_Shimmy_Page_FooBar', $items[0]['page_callback']);

    $response = $this->createGuzzle()->get('frontend://civicrm/shimmy/foobar');
    $this->assertStatusCode(200, $response);
    $this->assertBodyRegexp(';hello world;', $response);
  }

  public function testDisabled($cv): void {
    $items = $cv->api4('Route', 'get', ['where' => [['path', '=', 'civicrm/shimmy/foobar']]]);
    $this->assertEmpty($items);

    $response = $this->createGuzzle(['http_errors' => FALSE])->get('frontend://civicrm/shimmy/foobar');
    $this->assertPageNotShown($response);
    $this->assertNotBodyRegexp(';hello world;', $response);
  }

  public function testUninstalled($cv): void {
    // Same as disabled.
    $this->testDisabled($cv);
  }

  protected static function getPath($suffix = ''): string {
    return dirname(__DIR__, 2) . $suffix;
  }

}
