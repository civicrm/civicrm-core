<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
 * Determine the URL of a resource file
 *
 * @param array $params
 *   Array with keys:
 *   - ext: string, extension name. see CRM_Core_Resources::getUrl
 *   - file: string, relative file path. see CRM_Core_Resources::getUrl
 * @param CRM_Core_Smarty $smarty
 *
 * @return string
 */
function smarty_function_crmResURL($params, &$smarty) {
  $res = CRM_Core_Resources::singleton();
  if (!array_key_exists('file', $params)) {
    $params['file'] = NULL;
  }
  return $res->getUrl($params['ext'], $params['file'], $params['addCacheCode']);
}
