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
 * Fetch an attribute from html
 *
 * @param array $params
 * @param CRM_Core_Smarty $smarty
 *
 * @return string
 */
function smarty_function_crmGetAttribute($params, &$smarty) {
  $ret = '';
  if (preg_match('#\W' . $params['attr'] . '="([^"]+)#', $params['html'], $matches)) {
    $ret = $matches[1];
  }
  if (!empty($params['assign'])) {
    $smarty->assign($params['assign'], $ret);
  }
  else {
    return $ret;
  }
}
