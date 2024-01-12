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
 * @param $tpl_name
 * @param $tpl_source
 *
 * @return bool
 */
function civicrm_smarty_resource_string_get_template($tpl_name, &$tpl_source) {
  $tpl_source = $tpl_name;
  return TRUE;
}

/**
 * @param string $tpl_name
 * @param $tpl_timestamp
 *
 * @return bool
 */
function civicrm_smarty_resource_string_get_timestamp($tpl_name, &$tpl_timestamp) {
  $tpl_timestamp = time();
  return TRUE;
}

/**
 * @return bool
 */
function civicrm_smarty_resource_string_get_secure() {
  return TRUE;
}

/**
 */
function civicrm_smarty_resource_string_get_trusted() {

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
