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
        elseif (version_compare(JVERSION, '4.0', 'lt')) {
          $app = JApplicationCli::getInstance();
        }
        else {
          $app = \Joomla\CMS\Factory::getApplication();
        }
      }

      $result = $app->triggerEvent($fnSuffix, [&$arg1, &$arg2, &$arg3, &$arg4, &$arg5, &$arg6]);

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
        $finalResult = [];
        foreach ($result as $res) {
          if (!is_array($res)) {
            $res = [$res];
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
