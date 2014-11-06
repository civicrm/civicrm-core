<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
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

/**
 * Api Explorer
 */
class CRM_Admin_Page_APIExplorer extends CRM_Core_Page {

  /**
   * @return string
   */
  function run() {
    CRM_Utils_System::setTitle(ts('API explorer and generator'));
    CRM_Core_Resources::singleton()
      ->addScriptFile('civicrm', 'templates/CRM/Admin/Page/APIExplorer.js')
      ->addScriptUrl('//cdnjs.cloudflare.com/ajax/libs/prettify/r298/prettify.min.js', 99)
      ->addStyleUrl('//cdnjs.cloudflare.com/ajax/libs/prettify/r298/prettify.min.css', 99);
    $this->assign('operators', CRM_Core_DAO::acceptedSQLOperators());
    return parent::run();
  }

  /**
   * Get user context.
   *
   * @return string user context.
   */
  function userContext() {
    return 'civicrm/api/explorer';
  }
}

