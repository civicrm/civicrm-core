<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
 * @copyright CiviCRM LLC (c) 2004-2014
 * $Id$
 *
 */

require_once 'CRM/Core/Smarty/plugins/function.crmAttributes.php';

/**
 * Generate the html for a button-style link
 *
 * @param array $params
 *   Params of the {crmButton} call.
 * @param string $text
 *   Contents of block.
 * @param CRM_Core_Smarty $smarty
 *   The Smarty object.
 *
 * @return string
 *   The generated html.
 */
function smarty_block_crmButton($params, $text, &$smarty) {
  // Generate url (pass 'html' param as false to avoid double-encode by crmAttributes)
  $params['href'] = CRM_Utils_System::crmURL($params + array('h' => FALSE));
  // Always add class 'button' - fixme probably should be crm-button
  $params['class'] = 'button ' . CRM_Utils_Array::value('class', $params, '');
  // Any jQuery-UI icon works
  $icon = CRM_Utils_Array::value('icon', $params, 'pencil');
  // All other params are treated as html attributes
  CRM_Utils_Array::remove($params, 'icon', 'p', 'q', 'a', 'f', 'h', 'fb', 'fe');
  $attributes = smarty_function_crmAttributes(array('a' => $params), CRM_Core_DAO::$_nullObject);
  return "<a$attributes><span><span class='icon ui-icon-$icon'></span> $text</span></a>";
}
