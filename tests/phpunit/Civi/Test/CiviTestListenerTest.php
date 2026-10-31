<?php
namespace Civi\Test {

  /**
   * @group headless
   */
  class CiviTestListenerTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface {

    /**
     * @var array
     *   Ex: $containerBuilds['civitestlistenertest'] = 1.
     */
    public static array $containerBuilds = [];

    public function setUpHeadless() {
      return \Civi\Test::headless()->apply();
    }

    protected function tearDown(): void {
      $GLOBALS['CIVICRM_FORCE_MODULES'] = array_diff($GLOBALS['CIVICRM_FORCE_MODULES'] ?? [], array_keys(self::$containerBuilds));
      $GLOBALS['CIVICRM_TEST_CASE'] = $this;
      parent::tearDown();
    }

    public function testBootHeadlessBuildsOneContainer(): void {
      $this->forceModule('civitestlistenertest');
      $this->bootHeadless($this);
      $this->assertSame(1, self::$containerBuilds['civitestlistenertest']);
    }

    public function testHeadlessBootsShareTheCompiledContainer(): void {
      $this->forceModule('civitestlistenershare');
      $this->bootHeadless($this);
      $first = \Civi::container();
      $this->bootHeadless($this);
      $second = \Civi::container();

      $this->assertSame(1, self::$containerBuilds['civitestlistenershare']);
      $this->assertSame(get_class($first), get_class($second));
      $this->assertNotSame($first, $second);
    }

    public function testClearingTheCompileDirRecompiles(): void {
      $this->forceModule('civitestlistenerrecompile');
      $this->bootHeadless($this);
      \Civi::rebuild(['files' => TRUE])->execute();
      $this->bootHeadless($this);
      $this->assertSame(2, self::$containerBuilds['civitestlistenerrecompile']);
    }

    public function testATestImplementingTheContainerHookGetsItsOwnContainer(): void {
      $test = new class() implements HeadlessInterface, HookInterface {

        public int $builds = 0;

        public function setUpHeadless() {
          return \Civi\Test::headless();
        }

        public function hook_civicrm_container(): void {
          $this->builds++;
        }

      };
      $GLOBALS['CIVICRM_TEST_CASE'] = $test;
      $this->bootHeadless($test);
      $this->bootHeadless($test);
      $this->assertSame(2, $test->builds);
    }

    public static function compileHookListeners(): array {
      $hookMethod = new class() extends CompileHookListener {

        public function hook_civicrm_entityTypes(): void {
        }

      };
      $onMethod = new class() extends CompileHookListener {

        public function on_hook_civicrm_scanClasses(): void {
        }

      };
      return [
        'hook method' => [$hookMethod],
        'on_ method' => [$onMethod],
      ];
    }

    /**
     * @dataProvider compileHookListeners
     */
    public function testATestListeningToACompileHookIsNotServedTheSharedContainer(HeadlessInterface $test): void {
      $this->bootHeadless($this);
      $GLOBALS['CIVICRM_TEST_CASE'] = $test;
      $this->bootHeadless($test);
      $this->assertInstanceOf(\Symfony\Component\DependencyInjection\ContainerBuilder::class, \Civi::container());
    }

    /**
     * Start from an empty compile dir, so the counts hold when a test repeats.
     */
    private function forceModule(string $module): void {
      \Civi::rebuild(['files' => TRUE])->execute();
      self::$containerBuilds[$module] = 0;
      $GLOBALS['CIVICRM_FORCE_MODULES'][] = $module;
    }

    private function bootHeadless(HeadlessInterface $test): void {
      $listener = new class() extends CiviTestListener {

        public function boot(HeadlessInterface $test): void {
          $this->bootHeadless($test);
        }

      };
      $listener->boot($test);
    }

  }

  abstract class CompileHookListener implements HeadlessInterface, HookInterface {

    public function setUpHeadless() {
      return \Civi\Test::headless();
    }

  }

}

namespace {

  function civitestlistenertest_civicrm_container(): void {
    \Civi\Test\CiviTestListenerTest::$containerBuilds['civitestlistenertest']++;
  }

  function civitestlistenershare_civicrm_container(): void {
    \Civi\Test\CiviTestListenerTest::$containerBuilds['civitestlistenershare']++;
  }

  function civitestlistenerrecompile_civicrm_container(): void {
    \Civi\Test\CiviTestListenerTest::$containerBuilds['civitestlistenerrecompile']++;
  }

}
