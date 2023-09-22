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
 * Determine the path of a resource file
 *
 * @param array $params
 *   Identify the resource by either 'ext'+'file' or 'expr'.
 *
 *   Array with keys:
 *   - ext: string, extension name. see CRM_Core_Resources::getPath
 *   - file: string, relative file path. see CRM_Core_Resources::getPath
 *   - expr: string, a dynamic path expression. See: \Civi\Core\Paths::getPath()
 * @param CRM_Core_Smarty $smarty
 *
 * @return string
 */
function smarty_function_crmResPath($params, &$smarty) {
  if (!empty($params['expr'])) {
    return Civi::paths()->getPath($params['expr']);
  }

  $res = CRM_Core_Resources::singleton();
  if (!array_key_exists('ext', $params)) {
    $params['ext'] = 'civicrm';
  }
  if (!array_key_exists('file', $params)) {
    $params['file'] = NULL;
  }
  return $res->getPath($params['ext'], $params['file']);
}
