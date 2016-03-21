<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 * $Id$
 *
 */

/**
 * Helpers for managing SQL-backed queue items
 *
 * @see CRM_Queue_Queue_Sql
 */
class CRM_Queue_BAO_QueueItem extends CRM_Queue_DAO_QueueItem {

  /**
   * Ensure that the required SQL table exists.
   *
   * @return bool
   *   TRUE if table now exists
   */
  public static function findCreateTable() {
    $checkTableSql = "show tables like 'civicrm_queue_item'";
    $foundName = CRM_Core_DAO::singleValueQuery($checkTableSql);
    if ($foundName == 'civicrm_queue_item') {
      return TRUE;
    }

    // civicrm/sql/civicrm_queue_item.mysql
    $fileName = dirname(__FILE__) . '/../../../sql/civicrm_queue_item.mysql';

    $config = CRM_Core_Config::singleton();
    CRM_Utils_File::sourceSQLFile($config->dsn, $fileName);

    // Make sure it succeeded
    $foundName = CRM_Core_DAO::singleValueQuery($checkTableSql);
    return ($foundName == 'civicrm_queue_item');
  }

}
