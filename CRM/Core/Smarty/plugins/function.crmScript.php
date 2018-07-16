<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * @copyright CiviCRM LLC
 * $Id$
 *
 */

/**
 * Add a Javascript file to a specific part of the page
 *
 * @param array $params
 *   Array with keys:
 *   - ext: string, extension name. see CRM_Core_Resources::addScriptFile
 *   - file: string, relative file path. see CRM_Core_Resources::addScriptFile
 *   - url: string. see CRM_Core_Resources::addScriptURL
 *   - weight: int; default: CRM_Core_Resources::DEFAULT_WEIGHT (0)
 *   - region: string; default: CRM_Core_Resources::DEFAULT_REGION ('html-header')
 * @param CRM_Core_Smarty $smarty
 *
 * @throws Exception
 */
function smarty_function_crmScript($params, &$smarty) {
  $params += array(
    'weight' => CRM_Core_Resources::DEFAULT_WEIGHT,
    'region' => CRM_Core_Resources::DEFAULT_REGION,
    'ext' => 'civicrm',
  );

  if (array_key_exists('file', $params)) {
    Civi::resources()->addScriptFile($params['ext'], $params['file'], $params['weight'], $params['region']);
  }
  elseif (array_key_exists('url', $params)) {
    Civi::resources()->addScriptUrl($params['url'], $params['weight'], $params['region']);
  }
  else {
    CRM_Core_Error::debug_var('crmScript_params', $params);
    throw new Exception("crmScript requires url or ext+file");
  }
}
