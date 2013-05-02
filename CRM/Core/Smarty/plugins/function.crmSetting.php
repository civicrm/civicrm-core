<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * Retrieve CiviCRM settings from the api for use in templates
 */
function smarty_function_crmSetting($params, &$smarty) {

  CRM_Core_Error::setCallback(array('CRM_Utils_REST', 'fatal'));
  unset($params['method']);
  unset($params['assign']);
  $params['version'] = 3;

  require_once 'api/api.php';
  $result = civicrm_api('setting', 'getvalue', $params);
  CRM_Core_Error::setCallback();
  if ($result === FALSE) {
    $smarty->trigger_error("Unknown error");
    return;
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
