<?php

namespace Civi\Shimmy\Mixins;

/**
 * Assert that the 'scan-classes' mixin is working properly.
 *
 * This class defines the assertions to run when installing or uninstalling the extension.
 * It is called as part of E2E_Shimmy_LifecycleTest.
 *
 * @see E2E_Shimmy_LifecycleTest
 */
class ScanClassesTest extends \PHPUnit\Framework\Assert {

  public function testPreConditions($cv): void {
    $this->assertFileExists(static::getPath('/CRM/Shimmy/ShimmyMessage.php'), 'The shimmy extension must have example PHP files.');
  }

  public function testInstalled($cv): void {
    // Assert that WorkflowMessageInterface's are registered.
    $items = $cv->api4('WorkflowMessage', 'get', ['where' => [['name', '=', 'shimmy_message_example']]]);
    $this->assertEquals('CRM_Shimmy_ShimmyMessage', $items[0]['class']);

    // Assert that HookInterface's are registered.
    $hookData = $this->fireHookShimmyFooBar($cv, 'world');
    sort($hookData);
    $expectHookData = [
      'hello world (CRM_Shimmy_ShimmyHookStatic, as event)',
      'hello world (CRM_Shimmy_ShimmyHookStatic, as hook)',
      'hello world (shimmy.hook.object, as event)',
      'hello world (shimmy.hook.object, as hook)',
    ];
    $this->assertEquals($expectHookData, $hookData);
  }

  public function testDisabled($cv): void {
    // Assert that WorkflowMessageInterface's are removed.
    $items = $cv->api4('WorkflowMessage', 'get', ['where' => [['name', '=', 'shimmy_message_example']]]);
    $this->assertEmpty($items);

    // Assert that HookInterface's are removed.
    $hookData = $this->fireHookShimmyFooBar($cv, 'world');
    $this->assertEquals([], $hookData);
  }

  public function testUninstalled($cv): void {
    // Assert that WorkflowMessageInterface's are removed.
    $items = $cv->api4('WorkflowMessage', 'get', ['where' => [['name', '=', 'shimmy_message_example']]]);
    $this->assertEmpty($items);

    // Assert that HookInterface's are removed.
    $hookData = $this->fireHookShimmyFooBar($cv, 'world');
    $this->assertEquals([], $hookData);
  }

  protected static function getPath($suffix = ''): string {
    return dirname(__DIR__, 2) . $suffix;
  }

  /**
   * Fire hook_civicrm_shimmyFooBar() in the system-under-test.
   *
   * @param $cv
   * @param string $name
   * @return array
   *   The modified $data
   */
  protected function fireHookShimmyFooBar($cv, string $name): array {
    try {
      putenv('SHIMMY_FOOBAR=' . $name);
      return $cv->phpEval('$d=[]; Civi::dispatcher()->dispatch("hook_civicrm_shimmyFooBar", \Civi\Core\Event\GenericHookEvent::create(["data"=>&$d,"for"=>getenv("SHIMMY_FOOBAR")])); return $d;');
    }
    finally {
      putenv('SHIMMY_FOOBAR');
    }
  }

}
