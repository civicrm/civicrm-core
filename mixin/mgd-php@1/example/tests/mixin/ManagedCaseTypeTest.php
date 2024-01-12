<?php

namespace Civi\Shimmy\Mixins;

/**
 * Assert that the mgd-php mixin is picking the case-type and all its related data.
 *
 * This class defines the assertions to run when installing or uninstalling the extension.
 * It use called as part of E2E_Shimmy_LifecycleTest.
 *
 * @see E2E_Shimmy_LifecycleTest
 */
class ManagedCaseTypeTest extends \PHPUnit\Framework\Assert {

  public function testPreConditions($cv): void {
    $this->assertFileExists(static::getPath('/CRM/BunnyDance.mgd.php'), 'The shimmy extension must have a Case MGD file.');
  }

  public function testInstalled($cv): void {
    $items = $cv->api4('CaseType', 'get', ['where' => [['name', '=', 'BunnyDance']]]);
    $this->assertEquals('The mysterious case of the dancing bunny', $items[0]['description']);
    $this->assertEquals('BunnyDance', $items[0]['name']);
    $this->assertEquals('Bunny Dance Case', $items[0]['title']);
    $this->assertEquals(TRUE, $items[0]['is_active']);
    $this->assertEquals(1, count($items));

    $actTypes = $cv->api4('OptionValue', 'get', [
      'where' => [['option_group_id:name', '=', 'activity_type'], ['name', '=', 'Nibble']],
    ]);
    $this->assertEquals('Nibble', $actTypes[0]['name'], 'ActivityType "Nibble" should be auto enabled. It\'s missing.');
    $this->assertEquals(TRUE, $actTypes[0]['is_active'], 'ActivityType "Nibble" should be auto enabled. It\'s inactive.');
  }

  public function testDisabled($cv): void {
    $items = $cv->api4('CaseType', 'get', ['where' => [['name', '=', 'BunnyDance']]]);
    $this->assertEquals('The mysterious case of the dancing bunny', $items[0]['description']);
    $this->assertEquals('BunnyDance', $items[0]['name']);
    $this->assertEquals('Bunny Dance Case', $items[0]['title']);
    $this->assertEquals(FALSE, $items[0]['is_active']);
    $this->assertEquals(1, count($items));
  }

  public function testUninstalled($cv): void {
    $items = $cv->api4('CaseType', 'get', ['where' => [['name', '=', 'BunnyDance']]]);
    $this->assertEquals(0, count($items));

    $actTypes = $cv->api4('OptionValue', 'get', [
      'where' => [['option_group_id:name', '=', 'activity_type'], ['name', '=', 'Nibble']],
    ]);
    $this->assertEmpty($actTypes);
  }

  protected static function getPath($suffix = ''): string {
    return dirname(__DIR__, 2) . $suffix;
  }

}
