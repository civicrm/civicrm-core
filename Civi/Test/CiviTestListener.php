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
class CiviTestListener extends \PHPUnit_Framework_BaseTestListener {
  /**
   * @var \CRM_Core_TemporaryErrorScope
   */
  private $errorScope;

  /**
   * @var array
   *  Ex: $cache['Some_Test_Class']['civicrm_foobar'] = 'hook_civicrm_foobar';
   *  Array(string $testClass => Array(string $hookName => string $methodName)).
   */
  private $cache = array();

  /**
   * @var \CRM_Core_Transaction|NULL
   */
  private $tx;

  public function startTestSuite(\PHPUnit_Framework_TestSuite $suite) {
    $byInterface = $this->indexTestsByInterface($suite->tests());
    $this->validateGroups($byInterface);
    $this->autoboot($byInterface);
  }

  public function endTestSuite(\PHPUnit_Framework_TestSuite $suite) {
    $this->cache = array();
  }

  public function startTest(\PHPUnit_Framework_Test $test) {
    if ($this->isCiviTest($test)) {
      error_reporting(E_ALL);
      $this->errorScope = \CRM_Core_TemporaryErrorScope::useException();
    }

    if ($test instanceof HeadlessInterface) {
      $this->bootHeadless($test);
    }

    if ($test instanceof HookInterface) {
      // Note: bootHeadless() indirectly resets any hooks, which means that hook_civicrm_config
      // is unsubscribable. However, after bootHeadless(), we're free to subscribe to hooks again.
      $this->registerHooks($test);
    }

    if ($test instanceof TransactionalInterface) {
      $this->tx = new \CRM_Core_Transaction(TRUE);
      $this->tx->rollback();
    }
    else {
      $this->tx = NULL;
    }
  }

  public function endTest(\PHPUnit_Framework_Test $test, $time) {
    if ($test instanceof TransactionalInterface) {
      $this->tx->rollback()->commit();
      $this->tx = NULL;
    }
    if ($test instanceof HookInterface) {
      \CRM_Utils_Hook::singleton()->reset();
    }
    if ($this->isCiviTest($test)) {
      error_reporting(E_ALL & ~E_NOTICE);
      $this->errorScope = NULL;
    }
  }

  /**
   * @param HeadlessInterface|\PHPUnit_Framework_Test $test
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
    $config = \CRM_Core_Config::singleton(TRUE, TRUE); // ugh, performance

    if (property_exists($config->userPermissionClass, 'permissions')) {
      $config->userPermissionClass->permissions = NULL;
    }
  }

  /**
   * @param \Civi\Test\HookInterface $test
   * @return array
   *   Array(string $hookName => string $methodName)).
   */
  protected function findTestHooks(HookInterface $test) {
    $class = get_class($test);
    if (!isset($this->cache[$class])) {
      $funcs = array();
      foreach (get_class_methods($class) as $func) {
        if (preg_match('/^hook_/', $func)) {
          $funcs[substr($func, 5)] = $func;
        }
      }
      $this->cache[$class] = $funcs;
    }
    return $this->cache[$class];
  }

  /**
   * @param \PHPUnit_Framework_Test $test
   * @return bool
   */
  protected function isCiviTest(\PHPUnit_Framework_Test $test) {
    return $test instanceof HookInterface || $test instanceof HeadlessInterface;
  }

  /**
   * Find any hook functions in $test and register them.
   *
   * @param \Civi\Test\HookInterface $test
   */
  protected function registerHooks(HookInterface $test) {
    if (CIVICRM_UF !== 'UnitTests') {
      // This is not ideal -- it's just a side-effect of how hooks and E2E tests work.
      // We can temporarily subscribe to hooks in-process, but for other processes, it gets messy.
      throw new \RuntimeException('CiviHookTestInterface requires CIVICRM_UF=UnitTests');
    }
    \CRM_Utils_Hook::singleton()->reset();
    /** @var \CRM_Utils_Hook_UnitTests $hooks */
    $hooks = \CRM_Utils_Hook::singleton();
    foreach ($this->findTestHooks($test) as $hook => $func) {
      $hooks->setHook($hook, array($test, $func));
    }
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
      eval($this->cv('php:boot --level=full', 'phpcode'));
    }
    elseif (!empty($byInterface['EndToEndInterface'])) {
      putenv('CIVICRM_UF=');
      eval($this->cv('php:boot --level=full', 'phpcode'));
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
    $descriptorSpec = array(0 => array("pipe", "r"), 1 => array("pipe", "w"), 2 => STDERR);
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
    $byInterface = array('HeadlessInterface' => array(), 'EndToEndInterface' => array());
    foreach ($tests as $test) {
      /** @var \PHPUnit_Framework_Test $test */
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
      if (strpos($docComment, "@group headless\n") === FALSE) {
        echo "WARNING: Class $className implements HeadlessInterface. It should declare \"@group headless\".\n";
      }
      if (strpos($docComment, "@group e2e\n") !== FALSE) {
        echo "WARNING: Class $className implements HeadlessInterface. It should not declare \"@group e2e\".\n";
      }
    }
    foreach ($byInterface['EndToEndInterface'] as $className => $nonce) {
      $clazz = new \ReflectionClass($className);
      $docComment = str_replace("\r\n", "\n", $clazz->getDocComment());
      if (strpos($docComment, "@group e2e\n") === FALSE) {
        echo "WARNING: Class $className implements EndToEndInterface. It should declare \"@group e2e\".\n";
      }
      if (strpos($docComment, "@group headless\n") !== FALSE) {
        echo "WARNING: Class $className implements EndToEndInterface. It should not declare \"@group headless\".\n";
      }
    }
  }

}
