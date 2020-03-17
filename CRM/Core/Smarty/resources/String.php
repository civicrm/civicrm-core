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
 * $Id$
 *
 * @param $tpl_name
 * @param $tpl_source
 * @param $smarty_obj
 *
 * @return bool
 */
function civicrm_smarty_resource_string_get_template($tpl_name, &$tpl_source, &$smarty_obj) {
  $tpl_source = $tpl_name;
  return TRUE;
}

/**
 * @param string $tpl_name
 * @param $tpl_timestamp
 * @param CRM_Core_Smarty $smarty_obj
 *
 * @return bool
 */
function civicrm_smarty_resource_string_get_timestamp($tpl_name, &$tpl_timestamp, &$smarty_obj) {
  $tpl_timestamp = time();
  return TRUE;
}

/**
 * @param string $tpl_name
 * @param CRM_Core_Smarty $smarty_obj
 *
 * @return bool
 */
function civicrm_smarty_resource_string_get_secure($tpl_name, &$smarty_obj) {
  return TRUE;
}

/**
 * @param string $tpl_name
 * @param CRM_Core_Smarty $smarty_obj
 */
function civicrm_smarty_resource_string_get_trusted($tpl_name, &$smarty_obj) {

}

function civicrm_smarty_register_string_resource() {
  $template = CRM_Core_Smarty::singleton();
  $template->register_resource('string', [
    'civicrm_smarty_resource_string_get_template',
    'civicrm_smarty_resource_string_get_timestamp',
    'civicrm_smarty_resource_string_get_secure',
    'civicrm_smarty_resource_string_get_trusted',
  ]);
}
