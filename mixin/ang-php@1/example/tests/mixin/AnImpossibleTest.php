<?php

namespace Civi\Shimmy\Mixins;

/**
 * See if mixin lifecycle test cares about exceptions
 *
 * @see E2E_Shimmy_LifecycleTest
 */
class AnImpossibleTest extends \PHPUnit\Framework\Assert {

  public function testPreConditions($cv): void {
    $this->assertEquals(1, 1);
  }

  public function testInstalled($cv): void {
    $this->assertEquals(1, 1);
  }

  public function testDisabled($cv): void {
    $this->assertEquals(1, 1);
  }

  public function testUninstalled($cv): void {
    $this->assertEquals(1, 1);
    // so far so good!
    if ($cv->isLocal()) {
      $a = [];
      $b = $a['missing_key'];
    }
  }

  protected static function getPath($suffix = ''): string {
    return dirname(__DIR__, 2) . $suffix;
  }

}
