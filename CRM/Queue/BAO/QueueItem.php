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
      // Table originated in 4.2. We no longer support direct upgrades from <=4.2. Don't bother trying to create table.
      return FALSE;
    }
    else {
      return static::updateTable();
    }
  }

  /**
   * Ensure that the `civicrm_queue_item` table is up-to-date.
   *
   * @return bool
   */
  public static function updateTable(): bool {
    CRM_Upgrade_Incremental_Base::addColumn(NULL, 'civicrm_queue_item', 'run_count',
      "int NOT NULL DEFAULT 0 COMMENT 'Number of times execution has been attempted.'");
    return TRUE;
  }

}
