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
 * Determine the URL of a resource file
 *
 * @param array $params
 *   Identify the resource by either 'ext'+'file' or 'expr'.
 *
 *   Array with keys:
 *   - ext: string, extension name. see CRM_Core_Resources::getUrl
 *   - file: string, relative file path. see CRM_Core_Resources::getUrl
 *   - expr: string, a dynamic path expression. See: \Civi\Core\Paths::getUrl()
 * @param CRM_Core_Smarty $smarty
 *
 * @return string
 */
function smarty_function_crmResURL($params, &$smarty) {
  if (!empty($params['expr'])) {
    return Civi::paths()->getUrl($params['expr'], 'absolute');
  }

  $res = CRM_Core_Resources::singleton();
  if (!array_key_exists('file', $params)) {
    $params['file'] = NULL;
  }
  return $res->getUrl($params['ext'], $params['file'], $params['addCacheCode'] ?? FALSE);
}
