<?php

namespace Civi\Test;

/**
 * Class CiviTestListener
 * @package Civi\Test
 *
 * CiviTestListener participates in test-execution, looking for test-classes
 * which have certain tags. If the tags are found, the listener will perform
 * additional setup/teardown logic.
 *
 * @see EndToEndInterface
 * @see HeadlessInterface
 * @see HookInterface
 */
class CiviTestListenerPHPUnit7 implements \PHPUnit\Framework\TestListener {

  use \PHPUnit\Framework\TestListenerDefaultImplementation;

  /**
   * @var array
   *  Ex: $cache['Some_Test_Class']['civicrm_foobar'] = 'hook_civicrm_foobar';
   *  Array(string $testClass => Array(string $hookName => string $methodName)).
   */
  private $cache = [];

  public $errorScope;

  /**
   * @var \CRM_Core_Transaction|null
   */
  private $tx;

  public function startTestSuite(\PHPUnit\Framework\TestSuite $suite): void {
    $byInterface = $this->indexTestsByInterface($suite->tests());
    $this->validateGroups($byInterface);
    $this->autoboot($byInterface);
  }

  public function endTestSuite(\PHPUnit\Framework\TestSuite $suite): void {
    $this->cache = [];
  }

  public function startTest(\PHPUnit\Framework\Test $test): void {
    if ($this->isCiviTest($test)) {
      error_reporting(E_ALL);
      $GLOBALS['CIVICRM_TEST_CASE'] = $test;
    }

    if ($test instanceof HeadlessInterface) {
      $this->bootHeadless($test);
    }

    if ($test instanceof TransactionalInterface) {
      $this->tx = new \CRM_Core_Transaction(TRUE);
      $this->tx->rollback();
    }
    else {
      $this->tx = NULL;
    }

    if ($this->isCiviTest($test)) {
      \Civi\Test::eventChecker()->start($test);
    }
  }

  public function endTest(\PHPUnit\Framework\Test $test, float $time): void {
    $exception = NULL;

    if ($this->isCiviTest($test)) {
      try {
        \Civi\Test::eventChecker()->stop($test);
      }
      catch (\Exception $e) {
        $exception = $e;
      }
    }

    if ($test instanceof TransactionalInterface) {
      $this->tx->rollback()->commit();
      $this->tx = NULL;
    }
    if ($test instanceof HookInterface) {
      \CRM_Utils_Hook::singleton()->reset();
    }
    \CRM_Utils_Time::resetTime();
    if ($this->isCiviTest($test)) {
      unset($GLOBALS['CIVICRM_TEST_CASE']);
      unset($_SERVER['HTTP_X_REQUESTED_WITH']); /* Several tests neglect to clean this up... */
      error_reporting(E_ALL & ~E_NOTICE);
      $this->errorScope = NULL;
    }

    if ($exception) {
      throw $exception;
    }
  }

  /**
   * @param HeadlessInterface|\PHPUnit\Framework\Test $test
   */
  protected function bootHeadless($test) {
    if (CIVICRM_UF !== 'UnitTests') {
      throw new \RuntimeException('HeadlessInterface requires CIVICRM_UF=UnitTests');
    }

    // Hrm, this seems wrong. Shouldn't we be resetting the entire session?
    $session = \CRM_Core_Session::singleton();
    $session->set('userID', NULL);
    $test->setUpHeadless();

    \CRM_Utils_System::flushCache();
    \Civi::reset();
    \CRM_Core_Session::singleton()->set('userID', NULL);
    // ugh, performance
    $config = \CRM_Core_Config::singleton(TRUE, TRUE);
    $config->userSystem->setMySQLTimeZone();

    if (property_exists($config->userPermissionClass, 'permissions')) {
      $config->userPermissionClass->permissions = NULL;
    }
  }

  /**
   * @param \PHPUnit\Framework\Test $test
   * @return bool
   */
  protected function isCiviTest(\PHPUnit\Framework\Test $test) {
    return $test instanceof HookInterface || $test instanceof HeadlessInterface || $test instanceof \CiviUnitTestCase;
  }

  /**
   * The first time we come across HeadlessInterface or EndToEndInterface, we'll
   * try to autoboot.
   *
   * Once the system is booted, there's nothing we can do -- we're stuck with that
   * environment. (Thank you, prolific define()s!) If there's a conflict between a
   * test-class and the active boot-level, then we'll have to bail.
   *
   * @param array $byInterface
   *   List of test classes, keyed by major interface (HeadlessInterface vs EndToEndInterface).
   */
  protected function autoboot($byInterface) {
    if (defined('CIVICRM_UF')) {
      // OK, nothing we can do. System has booted already.
    }
    elseif (!empty($byInterface['HeadlessInterface'])) {
      putenv('CIVICRM_UF=UnitTests');
      // phpcs:disable
      eval($this->cv('php:boot --level=full', 'phpcode'));
      // phpcs:enable
    }
    elseif (!empty($byInterface['EndToEndInterface'])) {
      putenv('CIVICRM_UF=');
      // phpcs:disable
      eval($this->cv('php:boot --level=full', 'phpcode'));
      // phpcs:enable
    }

    $blurb = "Tip: Run the headless tests and end-to-end tests separately, e.g.\n"
    . "  $ phpunit5 --group headless\n"
    . "  $ phpunit5 --group e2e  \n";

    if (!empty($byInterface['HeadlessInterface']) && CIVICRM_UF !== 'UnitTests') {
      $testNames = implode(', ', array_keys($byInterface['HeadlessInterface']));
      throw new \RuntimeException("Suite includes headless tests ($testNames) which require CIVICRM_UF=UnitTests.\n\n$blurb");
    }
    if (!empty($byInterface['EndToEndInterface']) && CIVICRM_UF === 'UnitTests') {
      $testNames = implode(', ', array_keys($byInterface['EndToEndInterface']));
      throw new \RuntimeException("Suite includes end-to-end tests ($testNames) which do not support CIVICRM_UF=UnitTests.\n\n$blurb");
    }
  }

  /**
   * Call the "cv" command.
   *
   * This duplicates the standalone `cv()` wrapper that is recommended in bootstrap.php.
   * This duplication is necessary because `cv()` is optional, and downstream implementers
   * may alter, rename, or omit the wrapper, and (by virtue of its role in bootstrap) there
   * it is impossible to define it centrally.
   *
   * @param string $cmd
   *   The rest of the command to send.
   * @param string $decode
   *   Ex: 'json' or 'phpcode'.
   * @return string
   *   Response output (if the command executed normally).
   * @throws \RuntimeException
   *   If the command terminates abnormally.
   */
  protected function cv($cmd, $decode = 'json') {
    $cmd = 'cv ' . $cmd;
    $descriptorSpec = [0 => ["pipe", "r"], 1 => ["pipe", "w"], 2 => STDERR];
    $oldOutput = getenv('CV_OUTPUT');
    putenv("CV_OUTPUT=json");
    $process = proc_open($cmd, $descriptorSpec, $pipes, __DIR__);
    putenv("CV_OUTPUT=$oldOutput");
    fclose($pipes[0]);
    $result = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    if (proc_close($process) !== 0) {
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

  /**
   * @param $tests
   * @return array
   */
  protected function indexTestsByInterface($tests) {
    $byInterface = ['HeadlessInterface' => [], 'EndToEndInterface' => []];
    foreach ($tests as $test) {
      /** @var \PHPUnit\Framework\Test $test */
      if ($test instanceof HeadlessInterface) {
        $byInterface['HeadlessInterface'][get_class($test)] = 1;
      }
      if ($test instanceof EndToEndInterface) {
        $byInterface['EndToEndInterface'][get_class($test)] = 1;
      }
    }
    return $byInterface;
  }

  /**
   * Ensure that any tests have sensible groups, e.g.
   *
   * `HeadlessInterface` ==> `group headless`
   * `EndToEndInterface` ==> `group e2e`
   *
   * @param array $byInterface
   */
  protected function validateGroups($byInterface) {
    foreach ($byInterface['HeadlessInterface'] as $className => $nonce) {
      $clazz = new \ReflectionClass($className);
      $docComment = str_replace("\r\n", "\n", $clazz->getDocComment());
      if (!str_contains($docComment, "@group headless\n")) {
        echo "WARNING: Class $className implements HeadlessInterface. It should declare \"@group headless\".\n";
      }
      if (str_contains($docComment, "@group e2e\n")) {
        echo "WARNING: Class $className implements HeadlessInterface. It should not declare \"@group e2e\".\n";
      }
    }
    foreach ($byInterface['EndToEndInterface'] as $className => $nonce) {
      $clazz = new \ReflectionClass($className);
      $docComment = str_replace("\r\n", "\n", $clazz->getDocComment());
      if (!str_contains($docComment, "@group e2e\n")) {
        echo "WARNING: Class $className implements EndToEndInterface. It should declare \"@group e2e\".\n";
      }
      if (str_contains($docComment, "@group headless\n")) {
        echo "WARNING: Class $className implements EndToEndInterface. It should not declare \"@group headless\".\n";
      }
    }
  }

}
