<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 *
 * @package CiviCRM_Hook
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Utils_Hook_UnitTests extends CRM_Utils_Hook {

  protected $mockObject;

  /**
   * @var array
   */
  protected $adhocHooks;
  protected $civiModules;

  /**
   * Call this in CiviUnitTestCase::setUp()
   */
  public function reset(): void {
    $this->mockObject = NULL;
    $this->adhocHooks = [];
  }

  /**
   * Use a unit-testing mock object to handle hook invocations.
   *
   * e.g. hook_civicrm_foo === $mockObject->foo()
   * Mocks with a magic `__call()` method are called for every hook invocation.
   *
   * @param PHPUnit\Framework\MockObject\MockBuilder $mockObject
   */
  public function setMock($mockObject): void {
    $this->mockObject = $mockObject;
  }

  /**
   * Register a function to run when invoking a specific hook.
   *
   * @param string $hook
   *   Hook name, e.g civicrm_pre.
   * @param callable|array $callable
   *   Function to call ie array(class, method).
   *   eg. array($this, myMethod)
   */
  public function setHook(string $hook, $callable): void {
    $this->adhocHooks[$hook] = $callable;
    if (strpos($hook, 'token') !== FALSE) {
      unset(Civi::$statics['CRM_Contact_Tokens']['hook_tokens']);
    }
  }

  /**
   * Invoke standard, mock and ad hoc hooks.
   *
   * @param int $numParams
   *   Number of parameters to pass to the hook.
   * @param mixed $arg1
   *   Parameter to be passed to the hook.
   * @param mixed $arg2
   *   Parameter to be passed to the hook.
   * @param mixed $arg3
   *   Parameter to be passed to the hook.
   * @param mixed $arg4
   *   Parameter to be passed to the hook.
   * @param mixed $arg5
   *   Parameter to be passed to the hook.
   * @param mixed $arg6
   *   Parameter to be passed to the hook.
   * @param string $fnSuffix
   *   Function suffix, this is effectively the hook name.
   *
   * @return array|bool
   * @throws \CRM_Core_Exception
   */
  public function invokeViaUF(
    $numParams,
    &$arg1, &$arg2, &$arg3, &$arg4, &$arg5, &$arg6,
    $fnSuffix) {
    $params = [&$arg1, &$arg2, &$arg3, &$arg4, &$arg5, &$arg6];

    $fResult2 = $fResult3 = NULL;

    // run standard hooks
    if ($this->civiModules === NULL) {
      $this->civiModules = [];
      $this->requireCiviModules($this->civiModules);
    }
    $fResult1 = $this->runHooks($this->civiModules, $fnSuffix, $numParams, $arg1, $arg2, $arg3, $arg4, $arg5, $arg6);

    // run mock object hooks
    if ($this->mockObject && is_callable([$this->mockObject, $fnSuffix])) {
      $fResult2 = call_user_func([$this->mockObject, $fnSuffix], $arg1, $arg2, $arg3, $arg4, $arg5, $arg6);
    }

    // run adhoc hooks
    if (!empty($this->adhocHooks[$fnSuffix])) {
      $fResult3 = call_user_func_array($this->adhocHooks[$fnSuffix], $params);
    }

    $result = [];
    foreach ([$fResult1, $fResult2, $fResult3] as $fResult) {
      if (!empty($fResult) && is_array($fResult)) {
        $result = array_merge($result, $fResult);
      }
    }

    return empty($result) ? TRUE : $result;
  }

}
