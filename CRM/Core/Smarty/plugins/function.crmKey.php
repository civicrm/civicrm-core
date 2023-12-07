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
 * Generate a CRM_Core_Key of a given name
 *
 * @param array $params
 *   Params of the {crmKey} call, with the ‘name’ key holding the name of the key.
 * @param CRM_Core_Smarty $smarty
 *   The Smarty object.
 *
 * @return string
 *   the generated key
 */
function smarty_function_crmKey($params, &$smarty) {
  return CRM_Core_Key::get($params['name'], $params['addSequence'] ?? FALSE);
}
