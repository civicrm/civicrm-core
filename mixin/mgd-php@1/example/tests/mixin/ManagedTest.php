<?php

namespace Civi\Shimmy\Mixins;

/**
 * Assert that the managed-entity mixin is working properly.
 *
 * This class defines the assertions to run when installing or uninstalling the extension.
 * It use called as part of E2E_Shimmy_LifecycleTest.
 *
 * @see E2E_Shimmy_LifecycleTest
 */
class ManagedTest extends \PHPUnit\Framework\Assert {

  public function testPreConditions($cv): void {
    $this->assertFileExists(static::getPath('/CRM/ShimmyGroup.mgd.php'), 'The shimmy extension must have a Menu XML file.');
  }

  public function testInstalled($cv): void {
    $items = $cv->api4('OptionGroup', 'get', ['where' => [['name', '=', 'shimmy_group']]]);
    $this->assertEquals('Shimmy Group', $items[0]['title']);
    $this->assertEquals(TRUE, $items[0]['is_active']);
  }

  public function testDisabled($cv): void {
    $items = $cv->api4('OptionGroup', 'get', ['where' => [['name', '=', 'shimmy_group']]]);
    $this->assertEquals('Shimmy Group', $items[0]['title']);
    $this->assertEquals(FALSE, $items[0]['is_active']);
  }

  public function testUninstalled($cv): void {
    $items = $cv->api4('OptionGroup', 'get', ['where' => [['name', '=', 'shimmy_group']]]);
    $this->assertEmpty($items);
  }

  protected static function getPath($suffix = ''): string {
    return dirname(__DIR__, 2) . $suffix;
  }

}
