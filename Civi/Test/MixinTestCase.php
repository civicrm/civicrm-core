<?php

namespace Civi\Test;

/**
 * Enable, disable, and uninstall an extension. Ensure that various local example is enabled and disabled appropriately.
 *
 * The substantive assertions are split across various files in `tests/mixins/*.php`.
 *
 * TIP: To monitor the progress of the test, set DEBUG=1.
 */
abstract class MixinTestCase extends \PHPUnit\Framework\TestCase implements \Civi\Test\EndToEndInterface {

  /**
   * @var array
   */
  protected $mixinTests;

  abstract protected function getMixinTests(): array;

  protected function setUp(): void {
    $this->assertNotEquals('UnitTests', getenv('CIVICRM_UF'), 'This is an end-to-end test involving CLI and HTTP. CIVICRM_UF should not be set to UnitTests.');
    parent::setUp();
    $this->mixinTests = $this->getMixinTests();
  }

  /**
   * @param object $cv
   *   The `$cv` object is (roughly speaking) a wrapper for calling `cv`.
   *   It has method bindings for `$cv->api3()` and `$cv->api4()`.
   *   Different variations of `$cv` may be supplied - they will execute
   *   `$cv->api3()` and `$cv->api4()` in slightly different ways.
   */
  protected function runLifecycle($cv): void {
    $this->runMethods('testPreConditions', $cv);

    // Clear out anything from previous runs.
    $cv->api3('Extension', 'refresh', ['local' => TRUE, 'remote' => FALSE]);
    $cv->api3('Extension', 'disable', ['key' => 'shimmy']);
    $cv->api3('Extension', 'uninstall', ['key' => 'shimmy']);

    // The main show.
    $cv->api3('Extension', 'enable', ['key' => 'shimmy']);
    $this->runMethods('testInstalled', $cv);

    // This is a duplicate - make sure things still work after an extra run.
    $cv->api3('Extension', 'enable', ['key' => 'shimmy']);
    $this->runMethods('testInstalled', $cv);

    // OK, how's the cleanup?
    $cv->api3('Extension', 'disable', ['key' => 'shimmy']);
    $this->runMethods('testDisabled', $cv);

    $cv->api3('Extension', 'uninstall', ['key' => 'shimmy']);
    $this->runMethods('testUninstalled', $cv);
  }

  protected function runMethods(string $method, ...$args): void {
    if (empty($this->mixinTests)) {
      $this->fail('Cannot run methods. No mixin tests found.');
    }
    foreach ($this->mixinTests as $test) {
      $test->$method(...$args);
    }
  }

  protected function createCvWithLocalFunctions() {
    return new class {

      public function isLocal(): bool {
        return TRUE;
      }

      public function api3($entity, $action, $params) {
        return civicrm_api3($entity, $action, $params);
      }

      public function api4($entity, $action, $params): array {
        $params = array_merge(['checkPermissions' => FALSE], $params);
        return (array) civicrm_api4($entity, $action, $params);
      }

      public function phpCall($func, array $args = []) {
        return call_user_func_array($func, $args);
      }

      public function phpEval(string $expr) {
        // phpcs:ignore
        return eval($expr);
      }

    };
  }

  protected function createCvWithSubprocesses() {
    return new class {

      public function isLocal(): bool {
        return FALSE;
      }

      public function api3($entity, $action, $params) {
        return $this->cv('api3 --in=json ' . escapeshellarg("$entity.$action"), json_encode($params));
      }

      public function api4($entity, $action, $params): array {
        $params = array_merge(['checkPermissions' => FALSE], $params);
        return $this->cv('api4 --in=json ' . escapeshellarg("$entity.$action"), json_encode($params));
      }

      public function phpCall($func, array $args = []) {
        return $this->phpEval(sprintf('return call_user_func_array(%s, %s);', var_export($func, 1), var_export($args, 1)));
      }

      public function phpEval(string $expr) {
        return $this->cv('php:eval ' . escapeshellarg($expr));
      }

      /**
       * Call the "cv" command.
       *
       * @param string $cmd
       *   The rest of the command to send.
       * @param string|NULL $pipeData
       *   Optional data to send to `cv` via pipe.
       * @param string $decode
       *   Ex: 'json' or 'phpcode'.
       * @return string|array
       *   Response output (if the command executed normally).
       * @throws \RuntimeException
       *   If the command terminates abnormally.
       */
      protected function cv(string $cmd, ?string $pipeData = NULL, string $decode = 'json') {
        $cmd = 'cv ' . $cmd;
        $descriptorSpec = array(0 => array('pipe', 'r'), 1 => array('pipe', 'w'), 2 => STDERR);
        $oldOutput = getenv('CV_OUTPUT');
        putenv("CV_OUTPUT=json");

        // Execute `cv` in the original folder. This is a work-around for
        // phpunit/codeception, which seem to manipulate PWD.
        $cmd = sprintf('cd %s; %s', escapeshellarg(getenv('PWD')), $cmd);

        if (getenv('DEBUG')) {
          fprintf(STDERR, "#$# ");
          if (!empty($pipeData)) {
            fprintf(STDERR, "echo %s | (%s)\n", escapeshellarg($pipeData), $cmd);
          }
          else {
            fprintf(STDERR, "%s\n", $cmd);
          }
        }
        $process = proc_open($cmd, $descriptorSpec, $pipes, __DIR__);
        putenv("CV_OUTPUT=$oldOutput");

        if ($pipeData !== NULL) {
          fwrite($pipes[0], $pipeData);
        }
        fclose($pipes[0]);
        $result = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $exitCode = proc_close($process);

        if (getenv('DEBUG')) {
          // fprintf(STDERR, "## Exit: %s\n", $exitCode);
          fprintf(STDERR, "#%d# %s\n", $exitCode, $result);
        }

        if ($exitCode !== 0) {
          throw new \RuntimeException("Command failed ($cmd):\n$result");
        }
        switch ($decode) {
          case 'raw':
            return $result;

          case 'phpcode':
            // If the last output is /*PHPCODE*/, then we managed to complete execution.
            if (substr(trim($result), 0, 12) !== "/*BEGINPHP*/" || substr(trim($result), -10) !== "/*ENDPHP*/") {
              throw new \RuntimeException("Command failed ($cmd):\n$result");
            }
            return $result;

          case 'json':
            return json_decode($result, 1);

          default:
            throw new \RuntimeException("Bad decoder format ($decode)");
        }
      }

    };

  }

}
