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
 * @copyright TTTP
 *
 */

/**
 * @param $params
 * @param $smarty
 * @return string|void
 */
function smarty_function_crmAPI($params, &$smarty) {
  if (!array_key_exists('entity', $params)) {
    $smarty->trigger_error("assign: missing 'entity' parameter");
    return "crmAPI: missing 'entity' parameter";
  }
  $entity = $params['entity'];
  $action = CRM_Utils_Array::value('action', $params, 'get');
  $params['sequential'] = CRM_Utils_Array::value('sequential', $params, 1);
  $var = $params['var'] ?? NULL;
  CRM_Utils_Array::remove($params, 'entity', 'action', 'var');
  try {
    $result = civicrm_api3($entity, $action, $params);
  }
  catch (Exception $e) {
    $smarty->trigger_error('{crmAPI} ' . $e->getMessage());
    return NULL;
  }

  if (!$var) {
    return json_encode($result);
  }
  if (!empty($params['json'])) {
    $smarty->assign($var, json_encode($result));
  }
  else {
    $smarty->assign($var, $result);
  }
}
