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
 * CiviCRM's Smarty looped value change plugin
 *
 * Checks for change in value of given key
 *
 * @package CRM
 * @author Allen Shaw <allen@nswebsolutions.com>
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * Smarty function for checking change in a property's value, for example
 * when looping through an array.
 *
 * Smarty param:  string $key     unique identifier for this property (REQUIRED)
 * Smarty param:  mixed  $value   the current value of the property
 * Smarty param:  string $assign  name of template variable to which to assign result
 *
 *
 * @param array $params
 *   Template call's parameters.
 * @param CRM_Core_Smarty $smarty
 *   The Smarty object.
 *
 * @return NULL
 */
function smarty_function_isValueChange($params, &$smarty) {
  static $values = [];

  if (empty($params['key'])) {
    trigger_error('Missing required parameter, &#039;key&#039;, in isValueChange plugin.', E_USER_ERROR);
    return NULL;
  }

  $is_changed = FALSE;

  if (!array_key_exists($params['key'], $values) || strcasecmp($params['value'], $values[$params['key']]) !== 0) {
    // if we have a new value

    $is_changed = TRUE;

    $values[$params['key']] = $params['value'];

    // clear values on all properties added below/after this property
    $clear = FALSE;
    foreach ($values as $k => $dontcare) {
      if ($clear) {
        unset($values[$k]);
      }
      elseif ($params['key'] == $k) {
        $clear = TRUE;
      }
    }
  }

  if ($params['assign']) {
    $smarty->assign($params['assign'], $is_changed);
  }

  return NULL;
}
