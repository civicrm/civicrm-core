<?php

namespace E2E\Core;

use Civi\Test\RemoteTestFunction;

/**
 * Class PharTest
 *
 * @package E2E\Core
 * @group e2e
 */
class PharTest extends \CiviEndToEndTestCase {

  /**
   * Ensure that PHARs work. Load 'greeter.phar' and run a PHP function.
   */
  public function testGreeter(): void {
    $expect = 'Hello from greeter!';

    global $civicrm_root;
    require_once 'phar://' . $civicrm_root . '/tests/phpunit/E2E/Core/greeter.phar/greeter.php';
    $localResult = \example_greeter();
    $this->assertEquals($expect, $localResult, 'Local call should return greeting');

    $remoteGreeter = RemoteTestFunction::register(__CLASS__, 'greet', function () {
      global $civicrm_root;
      require_once 'phar://' . $civicrm_root . '/tests/phpunit/E2E/Core/greeter.phar/greeter.php';
      $remoteResult = \example_greeter();
      return $remoteResult;
    });

    $remoteResult = $remoteGreeter->execute();
    $this->assertEquals($expect, $remoteResult, "Remote call should return greeting");
  }

}
