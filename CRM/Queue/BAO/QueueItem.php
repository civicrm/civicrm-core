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
 * @copyright CiviCRM LLC https://civicrm.org/licensing
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
