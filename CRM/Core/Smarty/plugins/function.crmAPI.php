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
 * $Id$
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
  $errorScope = CRM_Core_TemporaryErrorScope::create(['CRM_Utils_REST', 'fatal']);
  $entity = $params['entity'];
  $action = CRM_Utils_Array::value('action', $params, 'get');
  $params['sequential'] = CRM_Utils_Array::value('sequential', $params, 1);
  $var = CRM_Utils_Array::value('var', $params);
  CRM_Utils_Array::remove($params, 'entity', 'action', 'var');
  $params['version'] = 3;
  require_once 'api/api.php';
  $result = civicrm_api($entity, $action, $params);
  unset($errorScope);
  if ($result === FALSE) {
    $smarty->trigger_error("Unknown error");
  }

  if (!empty($result['is_error'])) {
    $smarty->trigger_error("{crmAPI} " . $result["error_message"]);
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
