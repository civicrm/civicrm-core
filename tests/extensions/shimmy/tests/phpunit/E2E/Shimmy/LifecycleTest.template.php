<?php

/**
 * Enable, disable, and uninstall an extension. Ensure that various local example is enabled and disabled appropriately.
 *
 * The substantive assertions are split across various files in `tests/mixins/*.php`.
 *
 * @group e2e
 * @see cv
 */
class E2E_Shimmy_LifecycleTest extends \Civi\Test\MixinTestCase {

  /**
   * @return array
   */
  protected function getMixinTests(): array {
    $src = dirname(__DIR__, 4);

    $mixinTests = [];
    $mixinTestFiles = (array) glob($src . '/tests/mixin/*Test.php');
    foreach ($mixinTestFiles as $file) {
      require_once $file;
      $class = '\\Civi\\Shimmy\\Mixins\\' . basename($file, '.php');
      $mixinTests[] = new $class();
    }
    return $mixinTests;
  }

  public static function getTestId(): array {
    // We don't really want to use a data provider, but we don't to ensure that each
    // invocation of this suite can produce distinct results-files.
    return ['%%TEST_ID%%' => ['%%TEST_ID%%']];
  }

  /**
   * Install and uninstall the extension. Ensure that various mixins+artifacts work correctly.
   *
   * This interacts with Civi by running many subprocesses (`cv api3` and `cv api4` commands).
   * This style of interaction is a better representation of how day-to-day sysadmin works.
   *
   * @dataProvider getTestId
   */
  public function testLifecycleWithSubprocesses(string $id): void {
    $this->runLifecycle($this->createCvWithSubprocesses());
  }

  /**
   * Install and uninstall the extension. Ensure that various mixins+artifacts work correctly.
   *
   * This interacts with Civi by calling local PHP functions (`civicrm_api3(` and `civicrm_api4()`).
   * This style of interaction reveals whether the install/uninstall mechanics have data-leaks that
   * may cause subtle/buggy interactions during the transitions.
   *
   * @dataProvider getTestId
   */
  public function testLifecycleWithLocalFunctions(string $id): void {
    $this->runLifecycle($this->createCvWithLocalFunctions());
  }

}
