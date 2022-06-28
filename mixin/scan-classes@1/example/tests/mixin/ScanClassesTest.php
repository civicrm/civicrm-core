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

  public function testPreConditions($cv) {
    $this->assertFileExists(static::getPath('/CRM/Shimmy/ShimmyMessage.php'), 'The shimmy extension must have example PHP files.');
  }

  public function testInstalled($cv) {
    // Assert that WorkflowMessageInterface's are registered.
    $items = $cv->api4('WorkflowMessage', 'get', ['where' => [['name', '=', 'shimmy_message_example']]]);
    $this->assertEquals('CRM_Shimmy_ShimmyMessage', $items[0]['class']);
  }

  public function testDisabled($cv) {
    // Assert that WorkflowMessageInterface's are removed.
    $items = $cv->api4('WorkflowMessage', 'get', ['where' => [['name', '=', 'shimmy_message_example']]]);
    $this->assertEmpty($items);
  }

  public function testUninstalled($cv) {
    // Assert that WorkflowMessageInterface's are removed.
    $items = $cv->api4('WorkflowMessage', 'get', ['where' => [['name', '=', 'shimmy_message_example']]]);
    $this->assertEmpty($items);
  }

  protected static function getPath($suffix = ''): string {
    return dirname(__DIR__, 2) . $suffix;
  }

}
