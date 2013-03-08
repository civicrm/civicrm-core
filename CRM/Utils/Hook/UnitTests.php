<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id: $
 *
 */
class CRM_Utils_Hook_UnitTests extends CRM_Utils_Hook {

  protected $mockObject;
  protected $adhocHooks;

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

  function invoke($numParams,
    &$arg1, &$arg2, &$arg3, &$arg4, &$arg5,
    $fnSuffix) {
    $params = array( &$arg1, &$arg2, &$arg3, &$arg4, &$arg5);
    if ($this->mockObject && is_callable(array($this->mockObject, $fnSuffix))) {
      call_user_func(array($this->mockObject, $fnSuffix), $arg1, $arg2, $arg3, $arg4, $arg5);
    }
    if (!empty($this->adhocHooks[$fnSuffix])) {
      call_user_func_array($this->adhocHooks[$fnSuffix], $params );
    }
  }
}

