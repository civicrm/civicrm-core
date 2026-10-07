<?php

namespace Civi\Shimmy\Mixins;

/**
 * Assert that the 'legacy-include-path' mixin is working properly.
 *
 * This class defines the assertions to run when installing or uninstalling the extension.
 * It is called as part of E2E_Shimmy_LifecycleTest.
 *
 * @see E2E_Shimmy_LifecycleTest
 */
class LegacyIncludePathTest extends \PHPUnit\Framework\Assert {

  public function testPreConditions(object $cv): void {
    $this->assertDirectoryExists(static::getPath(), 'The shimmy extension directory must exist.');
  }

  public function testInstalled(object $cv): void {
    $extPath = rtrim(static::getPath(), DIRECTORY_SEPARATOR);
    $includePath = $cv->phpEval('return get_include_path();');
    $dirs = array_map(function ($p) {
      return rtrim($p, DIRECTORY_SEPARATOR);
    }, explode(PATH_SEPARATOR, $includePath));
    $this->assertContains($extPath, $dirs, 'Extension root directory should be present in PHP include_path when installed.');
  }

  public function testDisabled(object $cv): void {
    // In a multi-process or new request after disable, the mixin is inactive.
    if ($cv->isLocal()) {
      return;
    }
    $extPath = rtrim(static::getPath(), DIRECTORY_SEPARATOR);
    $includePath = $cv->phpEval('return get_include_path();');
    $dirs = array_map(function ($p) {
      return rtrim($p, DIRECTORY_SEPARATOR);
    }, explode(PATH_SEPARATOR, $includePath));
    $this->assertNotContains($extPath, $dirs, 'Extension root directory should not be present in PHP include_path when disabled.');
  }

  public function testUninstalled(object $cv): void {
    $this->testDisabled($cv);
  }

  protected static function getPath($suffix = ''): string {
    return dirname(__DIR__, 2) . $suffix;
  }

}
