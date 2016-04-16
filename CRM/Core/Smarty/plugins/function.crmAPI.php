<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
  $errorScope = CRM_Core_TemporaryErrorScope::create(array('CRM_Utils_REST', 'fatal'));
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
    return;
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
