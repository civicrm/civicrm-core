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
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * Given one of: ( page, title, text ) parameters, generates
 * an HTML link to documentation.
 *
 * @param array $params
 *   The function params.
 * @param CRM_Core_Smarty $smarty
 *   Reference to the smarty object.
 *
 * @return string|NULL
 *   HTML code of a link to documentation
 */
function smarty_function_docURL($params, &$smarty) {
  if (!isset($smarty)) {
    return NULL;
  }
  if (isset($params['params']) && is_array($params['params'])) {
    $params += $params['params'];
  }
  return CRM_Utils_System::docURL($params);
}
