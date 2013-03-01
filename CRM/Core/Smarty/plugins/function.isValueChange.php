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
 * CiviCRM's Smarty looped value change plugin
 *
 * Checks for change in value of given key
 *
 * @package CRM
 * @author Allen Shaw <allen@nswebsolutions.com>
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 */

/**
 * Smarty function for checking change in a property's value, for example
 * when looping through an array.
 *
 *
 * Smarty param:  string $key     unique identifier for this property (REQUIRED)
 * Smarty param:  mixed  $value   the current value of the property
 * Smarty param:  string $assign  name of template variable to which to assign result
 *
 *
 * @param array $params   template call's parameters
 * @param object $smarty  the Smarty object
 *
 * @return NULL
 */
function smarty_function_isValueChange($params, &$smarty) {
  static $values = array();

  if (empty($params['key'])) {
    $smarty->trigger_error("Missing required parameter, 'key', in isValueChange plugin.");
    return;
  }

  $is_changed = FALSE;

  if (!array_key_exists($params['key'], $values) || $params['value'] != $values[$params['key']]) {
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

  return;
}

