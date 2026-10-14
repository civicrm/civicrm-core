<?php
namespace Civi\Test {

  /**
   * @group headless
   */
  class CiviTestListenerTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface {

    public static int $containerBuilds = 0;

    public function setUpHeadless() {
      return \Civi\Test::headless()->apply();
    }

    public function testBootHeadlessBuildsOneContainer(): void {
      $listener = new class() extends CiviTestListener {

        public function boot(HeadlessInterface $test): void {
          $this->bootHeadless($test);
        }

      };
      $GLOBALS['CIVICRM_FORCE_MODULES'][] = 'civitestlistenertest';
      self::$containerBuilds = 0;
      try {
        $listener->boot($this);
      }
      finally {
        $GLOBALS['CIVICRM_FORCE_MODULES'] = array_diff($GLOBALS['CIVICRM_FORCE_MODULES'], ['civitestlistenertest']);
      }
      $this->assertSame(1, self::$containerBuilds);
    }

  }

}

namespace {

  function civitestlistenertest_civicrm_container(): void {
    \Civi\Test\CiviTestListenerTest::$containerBuilds++;
  }

}
