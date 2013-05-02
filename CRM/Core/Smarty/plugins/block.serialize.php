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
 * CiviCRM's Smarty gettext plugin
 *
 * @package CRM
 * @author Donald Lobo <lobo@civicrm.org>
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 */

/**
 * Smarty block function providing serialization support
 *
 * See CRM_Core_I18n class documentation for details.
 *
 * @param array $params   template call's parameters
 * @param string $text    {serialize} block contents from the template
 * @param object $smarty  the Smarty object
 *
 * @return string  the string, translated by gettext
 */
function smarty_block_serialize($params, $text, &$smarty) {
  return serialize($text);
}

