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
 * Retrieve CiviCRM settings from the api for use in templates.
 *
 * @param $params
 * @param $smarty
 *
 * @return int|string|null
 */
function smarty_function_crmSetting($params, &$smarty) {
  unset($params['method']);
  unset($params['assign']);

  try {
    $result = civicrm_api3('setting', 'getvalue', $params);
  }
  catch (Exception $e) {
    trigger_error('{crmSetting} ' . htmlentities($e->getMessage()), E_USER_ERROR);
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
