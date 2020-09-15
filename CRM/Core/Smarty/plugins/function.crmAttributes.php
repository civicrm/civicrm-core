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
 * @package CRM
 * @copyright CiviCRM LLC
 *
 */

/**
 * Formats an array of attributes as html
 *
 * @param array $params
 *   ['a'] array of attributes.
 * @param CRM_Core_Smarty $smarty
 *
 * @return string
 */
function smarty_function_crmAttributes($params, &$smarty) {
  $attributes = $params['a'] ?? [];
  return CRM_Utils_String::htmlAttributes($attributes);
}
