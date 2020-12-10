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
  $params += [
    'weight' => CRM_Core_Resources::DEFAULT_WEIGHT,
    'region' => CRM_Core_Resources::DEFAULT_REGION,
    'ext' => 'civicrm',
  ];

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
