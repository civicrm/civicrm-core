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
   * The `civicrm_queue_item` table is a special requirement - without it, the upgrader cannot run.
   * The upgrader will make a special request for `findCreateTable()` before computing upgrade-tasks.
   *
   * @return bool
   *   TRUE if table now exists
   */
  public static function findCreateTable(): bool {
    if (!CRM_Core_DAO::checkTableExists('civicrm_queue_item')) {
      return static::createTable();
    }
    else {
      return static::updateTable();
    }
  }

  /**
   * Create the `civicrm_queue_item` table.
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  protected static function createTable(): bool {
    // civicrm/sql/civicrm_queue_item.mysql
    $fileName = dirname(__FILE__) . '/../../../sql/civicrm_queue_item.mysql';

    $config = CRM_Core_Config::singleton();
    CRM_Utils_File::sourceSQLFile($config->dsn, $fileName);

    return CRM_Core_DAO::checkTableExists('civicrm_queue_item');
  }

  /**
   * Ensure that the `civicrm_queue_item` table is up-to-date.
   *
   * @return bool
   */
  public static function updateTable(): bool {
    CRM_Upgrade_Incremental_Base::addColumn(NULL, 'civicrm_queue_item', 'retry_interval',
      "int NULL COMMENT 'Number of seconds to wait before retrying a failed execution. NULL to disable.'");
    CRM_Upgrade_Incremental_Base::addColumn(NULL, 'civicrm_queue_item', 'retry_count',
      "int NULL COMMENT 'Number of permitted retries. Decreases with each retry. NULL to disable.'");
    return TRUE;
  }

}
