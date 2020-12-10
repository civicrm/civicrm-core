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
 * Add a stylesheet <LINK> to a specific part of the page
 *
 * @param array $params
 *   Array with keys:
 *   - ext: string, extension name. see CRM_Core_Resources::addStyleFile
 *   - file: string, relative file path. see CRM_Core_Resources::addStyleFile
 *   - url: string. see CRM_Core_Resources::addStyleURL
 *   - weight: int; default: CRM_Core_Resources::DEFAULT_WEIGHT (0)
 *   - region: string; default: CRM_Core_Resources::DEFAULT_REGION ('html-header')
 * @param CRM_Core_Smarty $smarty
 *
 * @throws Exception
 */
function smarty_function_crmStyle($params, &$smarty) {
  $res = CRM_Core_Resources::singleton();

  if (empty($params['weight'])) {
    $params['weight'] = CRM_Core_Resources::DEFAULT_WEIGHT;
  }
  if (empty($params['region'])) {
    $params['region'] = CRM_Core_Resources::DEFAULT_REGION;
  }
  if (empty($params['ext'])) {
    $params['ext'] = 'civicrm';
  }

  if (array_key_exists('file', $params)) {
    $res->addStyleFile($params['ext'], $params['file'], $params['weight'], $params['region']);
  }
  elseif (array_key_exists('url', $params)) {
    $res->addStyleUrl($params['url'], $params['weight'], $params['region']);
  }
  else {
    CRM_Core_Error::debug_var('crmStyle_params', $params);
    throw new Exception("crmStyle requires url or ext+file");
  }
}
