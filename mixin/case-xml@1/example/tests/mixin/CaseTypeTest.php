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

  public function testPreConditions($cv): void {
    $this->assertFileExists(static::getPath('/xml/case/DuckDance.xml'), 'The shimmy extension must have a Case XML file.');
  }

  public function testInstalled($cv): void {
    $items = $cv->api4('CaseType', 'get', ['where' => [['name', '=', 'DuckDance']]]);
    $this->assertEquals('The mysterious case of the dancing duck', $items[0]['description']);
    $this->assertEquals('DuckDance', $items[0]['name']);
    $this->assertEquals('Duck Dance Case', $items[0]['title']);
    $this->assertEquals(TRUE, $items[0]['is_active']);
    $this->assertEquals(1, count($items));

    // FIXME: This flush should be unnecessary. The fact that we need it indicates a bug during activation.
    // However, the bug is pre-existing, and adding these assertions will ensure that it doesn't get worse.
    $cv->api3('System', 'flush', []);

    $actTypes = $cv->api4('OptionValue', 'get', [
      'where' => [['option_group_id:name', '=', 'activity_type'], ['name', '=', 'Quack']],
    ]);
    $this->assertEquals('Quack', $actTypes[0]['name'], 'ActivityType "Quack" should be auto enabled. It\'s missing.');
    $this->assertEquals(TRUE, $actTypes[0]['is_active'], 'ActivityType "Quack" should be auto enabled. It\'s inactive.');
  }

  public function testDisabled($cv): void {
    $items = $cv->api4('CaseType', 'get', ['where' => [['name', '=', 'DuckDance']]]);
    $this->assertEquals('The mysterious case of the dancing duck', $items[0]['description']);
    $this->assertEquals('DuckDance', $items[0]['name']);
    $this->assertEquals('Duck Dance Case', $items[0]['title']);
    $this->assertEquals(FALSE, $items[0]['is_active']);
    $this->assertEquals(1, count($items));
  }

  public function testUninstalled($cv): void {
    $items = $cv->api4('CaseType', 'get', ['where' => [['name', '=', 'DuckDance']]]);
    $this->assertEquals(0, count($items));

    $actTypes = $cv->api4('OptionValue', 'get', [
      'where' => [['option_group_id:name', '=', 'activity_type'], ['name', '=', 'Quack']],
    ]);
    $this->assertEmpty($actTypes);
  }

  protected static function getPath($suffix = ''): string {
    return dirname(__DIR__, 2) . $suffix;
  }

}
