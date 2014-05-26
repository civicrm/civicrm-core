<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CiviCRM_Hook
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id: $
 *
 */
class CRM_Utils_Hook_UnitTests extends CRM_Utils_Hook {

  protected $mockObject;
  protected $adhocHooks;
  protected $civiModules = NULL;

  // Call this in CiviUnitTestCase::setUp()
  function reset() {
    $this->mockObject = NULL;
    $this->adhocHooks = array();
  }

  /**
   * Use a unit-testing mock object to handle hook invocations
   * e.g. hook_civicrm_foo === $mockObject->foo()
   */
  function setMock($mockObject) {
    $this->mockObject = $mockObject;
  }

  /**
   * Register a piece of code to run when invoking a hook
   */
  function setHook($hook, $callable) {
    $this->adhocHooks[$hook] = $callable;
  }

  /**
   *Invoke hooks
   *
   * @param int $numParams Number of parameters to pass to the hook
   * @param mixed $arg1 parameter to be passed to the hook
   * @param mixed $arg2 parameter to be passed to the hook
   * @param mixed $arg3 parameter to be passed to the hook
   * @param mixed $arg4 parameter to be passed to the hook
   * @param mixed $arg5 parameter to be passed to the hook
   * @param mixed $arg6 parameter to be passed to the hook
   * @param string $fnSuffix function suffix, this is effectively the hook name
   *
   * @return mixed
   */
  /**
   * @param int $numParams
   * @param mixed $arg1
   * @param mixed $arg2
   * @param mixed $arg3
   * @param mixed $arg4
   * @param mixed $arg5
   * @param mixed $arg6
   * @param string $fnSuffix
   *
   * @return mixed
   */
  function invoke($numParams,
    &$arg1, &$arg2, &$arg3, &$arg4, &$arg5, &$arg6,
    $fnSuffix) {

    $params = array( &$arg1, &$arg2, &$arg3, &$arg4, &$arg5, &$arg6);

    if ($this->civiModules === NULL) {
      $this->civiModules = array();
      $this->requireCiviModules($this->civiModules);
    }
    $this->runHooks($this->civiModules, $fnSuffix, $numParams, $arg1, $arg2, $arg3, $arg4, $arg5, $arg6);

    if ($this->mockObject && is_callable(array($this->mockObject, $fnSuffix))) {
      call_user_func(array($this->mockObject, $fnSuffix), $arg1, $arg2, $arg3, $arg4, $arg5, $arg6);
    }
    if (!empty($this->adhocHooks[$fnSuffix])) {
      call_user_func_array($this->adhocHooks[$fnSuffix], $params );
    }
  }
}

