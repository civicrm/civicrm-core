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
class ManagedV2Test extends \PHPUnit\Framework\Assert {

  private $expectTitles = [
    // Sort by name, ascending.
    'Shimmy Group (api/v3/)',
    'Shimmy Group (CRM/Shimmy/)',
    'Shimmy Group (managed/)',
    'Shimmy Group (./)',
    // NOTE: 'other/other-group.mgd.php' is specifically excluded.
  ];

  public function testPreConditions($cv): void {
    $this->assertFileExists(static::getPath('/root-group.mgd.php'), 'The shimmy extension must have example MGD file.');
    $this->assertFileExists(static::getPath('/api/v3/api-group.mgd.php'), 'The shimmy extension must have example MGD file.');
    $this->assertFileExists(static::getPath('/CRM/Shimmy/crm-group.mgd.php'), 'The shimmy extension must have example MGD file.');
    $this->assertFileExists(static::getPath('/managed/managed-group.mgd.php'), 'The shimmy extension must have example MGD file.');
    $this->assertFileExists(static::getPath('/other/other-group.mgd.php'), 'The shimmy extension must have example MGD file.');
  }

  private function getMyGroups($cv): array {
    return $cv->api4('OptionGroup', 'get', [
      'where' => [['name', 'LIKE', 'shimmy_group_%']],
      'orderBy' => ['name' => 'ASC'],
    ]);
  }

  public function testInstalled($cv): void {
    $items = $this->getMyGroups($cv);
    foreach ($this->expectTitles as $n => $title) {
      $this->assertEquals($title, $items[$n]['title'], "Active item ($n) should have title ($title).");
      $this->assertEquals(TRUE, $items[$n]['is_active'], "Item ($n) should be active.");
    }
    $this->assertEquals(count($this->expectTitles), count($items), 'Number of groups should match number of supported folders');
  }

  public function testDisabled($cv): void {
    $items = $this->getMyGroups($cv);
    foreach ($this->expectTitles as $n => $title) {
      $this->assertEquals($title, $items[$n]['title'], "Disabled item ($n) should have title ($title).");
      $this->assertEquals(FALSE, $items[$n]['is_active'], "Item ($n) should be active.");
    }
    $this->assertEquals(count($this->expectTitles), count($items), 'Number of groups should match number of supported folders');
  }

  public function testUninstalled($cv): void {
    $items = $this->getMyGroups($cv);
    $this->assertEmpty($items);
  }

  protected static function getPath($suffix = ''): string {
    return dirname(__DIR__, 2) . $suffix;
  }

}
