<?php

namespace Civi\Shimmy\Mixins;

/**
 * Assert that the case-type XML mixin is working properly.
 *
 * This class defines the assertions to run when installing or uninstalling the extension.
 * It use called as part of E2E_Shimmy_LifecycleTest.
 *
 * @see E2E_Shimmy_LifecycleTest
 */
class CaseTypeTest extends \PHPUnit\Framework\Assert {

  public function testPreConditions($cv) {
    $this->assertFileExists(static::getPath('/xml/case/DuckDance.xml'), 'The shimmy extension must have a Case XML file.');
  }

  public function testInstalled($cv) {
    $items = $cv->api4('CaseType', 'get', ['where' => [['name', '=', 'DuckDance']]]);
    $this->assertEquals('The mysterious case of the dancing duck', $items[0]['description']);
    $this->assertEquals('DuckDance', $items[0]['name']);
    $this->assertEquals('Duck Dance Case', $items[0]['title']);
    $this->assertEquals(TRUE, $items[0]['is_active']);
    $this->assertEquals(1, count($items));
  }

  public function testDisabled($cv) {
    $items = $cv->api4('CaseType', 'get', ['where' => [['name', '=', 'DuckDance']]]);
    $this->assertEquals('The mysterious case of the dancing duck', $items[0]['description']);
    $this->assertEquals('DuckDance', $items[0]['name']);
    $this->assertEquals('Duck Dance Case', $items[0]['title']);
    $this->assertEquals(FALSE, $items[0]['is_active']);
    $this->assertEquals(1, count($items));
  }

  public function testUninstalled($cv) {
    $items = $cv->api4('CaseType', 'get', ['where' => [['name', '=', 'DuckDance']]]);
    $this->assertEquals(0, count($items));
  }

  protected static function getPath($suffix = ''): string {
    return dirname(__DIR__, 2) . $suffix;
  }

}
