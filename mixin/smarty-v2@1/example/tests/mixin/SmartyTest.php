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
class SmartyTest extends \PHPUnit\Framework\Assert {

  public function testPreConditions(object $cv): void {
    $this->assertFileExists(static::getPath('/templates/CRM/Shimmy/Example.tpl'), 'The shimmy extension must have example TPL files.');
  }

  public function testInstalled(object $cv): void {
    $out = $this->renderExample($cv, 'CRM/Shimmy/Example.tpl');
    $this->assertEquals('<p>OK</p>', trim($out));
  }

  public function testDisabled(object $cv): void {
    if ($cv->isLocal()) {
      // Historically, Smarty templates have been left active for duration of same-process (post-disabling).
      // We'll ignore testing this edge-case until someone decides that a change in behavior is better.
      return;
    }

    $out = $this->renderExample($cv, 'CRM/Shimmy/Example.tpl');
    $this->assertEquals('', trim($out));
  }

  public function testUninstalled(object $cv): void {
    // Same as disabled....
    $this->testDisabled($cv);
  }

  protected static function getPath($suffix = ''): string {
    return dirname(__DIR__, 2) . $suffix;
  }

  /**
   * Render a template with the system-under-test.
   *
   * @param \object $cv
   * @param string $name
   * @return string
   */
  protected function renderExample(object $cv, string $name) {
    try {
      putenv('SHIMMY_FOOBAR=' . $name);
      try {
        return $cv->phpEval('return CRM_Core_Smarty::singleton()->fetch(getenv("SHIMMY_FOOBAR"));');
      }
      catch (\Throwable $e) {
        return '';
      }
    }
    finally {
      putenv('SHIMMY_FOOBAR');
    }
  }

}
