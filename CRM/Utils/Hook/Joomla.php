<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC (c) 2004-2018
 */
class CRM_Utils_Hook_Joomla extends CRM_Utils_Hook {
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
  public function invokeViaUF(
    $numParams,
    &$arg1, &$arg2, &$arg3, &$arg4, &$arg5, &$arg6,
    $fnSuffix
  ) {
    // ensure that we are running in a joomla context
    // we've not yet figured out how to bootstrap joomla, so we should
    // not execute hooks if joomla is not loaded
    if (defined('_JEXEC')) {
      //Invoke the Joomla plugin system to observe to civicrm events.
      jimport('joomla.plugin.helper');
      jimport('cms.plugin.helper');
      JPluginHelper::importPlugin('civicrm');

      // get app based on cli or web
      if (PHP_SAPI != 'cli') {
        $app = JFactory::getApplication('administrator');
      }
      else {
        // condition on Joomla version
        if (version_compare(JVERSION, '3.0', 'lt')) {
          $app = JCli::getInstance();
        }
        else {
          $app = JApplicationCli::getInstance();
        }
      }

      $result = $app->triggerEvent($fnSuffix, array(&$arg1, &$arg2, &$arg3, &$arg4, &$arg5, &$arg6));

      $moduleResult = $this->commonInvoke($numParams,
        $arg1, $arg2, $arg3, $arg4, $arg5, $arg6,
        $fnSuffix, 'joomla');
      if (!empty($moduleResult) && is_array($moduleResult)) {
        if (empty($result)) {
          $result = $moduleResult;
        }
        else {
          if (is_array($moduleResult)) {
            $result = array_merge($result, $moduleResult);
          }
        }
      }

      if (!empty($result)) {
        // collapse result returned from hooks
        // CRM-9XXX
        $finalResult = array();
        foreach ($result as $res) {
          if (!is_array($res)) {
            $res = array($res);
          }
          $finalResult = array_merge($finalResult, $res);
        }
        $result = $finalResult;
      }
      return $result;
    }
    else {
      // CRM-20904: We should still call Civi extension hooks even if Joomla isn't online yet.
      return $this->commonInvoke($numParams,
        $arg1, $arg2, $arg3, $arg4, $arg5, $arg6,
        $fnSuffix, 'joomla');
    }
  }

}
