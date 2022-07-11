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
class CRM_Utils_Hook_Standalone extends CRM_Utils_Hook {

  /**
   * Invoke hooks.
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
   * @return mixed
   */
  public function invokeViaUF($numParams, &$arg1, &$arg2, &$arg3, &$arg4, &$arg5, &$arg6, $fnSuffix) {
    // @todo Just guessing, based on how other CMSes invoke hooks, not sure about the empty fnPrefix
    return $this->commonInvoke($numParams,
      $arg1, $arg2, $arg3, $arg4, $arg5, $arg6,
      $fnSuffix, '');
  }

}
