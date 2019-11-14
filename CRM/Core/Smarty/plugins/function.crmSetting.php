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
 * Retrieve CiviCRM settings from the api for use in templates.
 *
 * @param $params
 * @param $smarty
 *
 * @return int|string|null
 */
function smarty_function_crmSetting($params, &$smarty) {

  $errorScope = CRM_Core_TemporaryErrorScope::create(['CRM_Utils_REST', 'fatal']);
  unset($params['method']);
  unset($params['assign']);
  $params['version'] = 3;

  require_once 'api/api.php';
  $result = civicrm_api('setting', 'getvalue', $params);
  unset($errorScope);
  // Core-688 FALSE is returned by Boolean settings, thus giving false errors.
  if ($result === NULL) {
    $smarty->trigger_error("Unknown error");
    return NULL;
  }

  if (empty($params['var'])) {
    return is_numeric($result) ? $result : json_encode($result);
  }
  if (!empty($params['json'])) {
    $smarty->assign($params["var"], json_encode($result));
  }
  else {
    $smarty->assign($params["var"], $result);
  }
}
