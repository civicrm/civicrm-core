<?php

namespace Civi\Shimmy\Mixins;

/**
 * Assert that the `settings/*.setting.php` mixin is working properly.
 *
 * This class defines the assertions to run when installing or uninstalling the extension.
 * It use called as part of E2E_Shimmy_LifecycleTest.
 *
 * @see E2E_Shimmy_LifecycleTest
 */
class SettingsTest extends \PHPUnit\Framework\Assert {

  public function testPreConditions($cv): void {
    $this->assertFileExists(static::getPath('/settings/Shimmy.setting.php'), 'The shimmy extension must have a Menu XML file.');
  }

  public function testInstalled($cv): void {
    // The menu item is registered...
    $items = $cv->api4('Setting', 'getFields', ['where' => [['name', '=', 'shimmy_example']], 'loadOptions' => TRUE]);
    $this->assertEquals('shimmy_example', $items[0]['name']);
    $this->assertEquals('select', $items[0]['html_type']);
    $this->assertEquals('First example', $items[0]['options']['first']);
    $this->assertEquals('Second example', $items[0]['options']['second']);

    // And it supports reading/writing...
    $cv->api3('Setting', 'revert', ['name' => 'shimmy_example']);  /* FIXME: Prior installations don't cleanup... */
    $value = $cv->api3('Setting', 'getvalue', ['name' => 'shimmy_example']);
    $this->assertEquals('first', $value);
    $r = $cv->api3('Setting', 'create', ['shimmy_example' => 'second']);
    $this->assertEquals(0, $r['is_error']);
    $value = $cv->api3('Setting', 'getvalue', ['name' => 'shimmy_example']);
    $this->assertEquals('second', $value);
  }

  public function testDisabled($cv): void {
    $items = $cv->api4('Setting', 'getFields', ['where' => [['name', '=', 'shimmy_example']], 'loadOptions' => TRUE]);
    $this->assertEmpty($items);

    $value = $cv->api3('Setting', 'getvalue', ['name' => 'shimmy_example']);
    $this->assertEquals('second', $value);
  }

  public function testUninstalled($cv): void {
    $items = $cv->api4('Setting', 'getFields', ['where' => [['name', '=', 'shimmy_example']], 'loadOptions' => TRUE]);
    $this->assertEmpty($items);

    // Uninstall should probably drop old settings, but it hasn't traditionally, so we won't check for the moment.
    // FIXME // $value = cv('ev \'return Civi::settings()->get("shimmy_example");\'', 'raw');
    // FIXME // $this->assertEquals('null', trim($value));
  }

  protected static function getPath($suffix = ''): string {
    return dirname(__DIR__, 2) . $suffix;
  }

}
