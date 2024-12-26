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
 * Upgrade logic for the 5.77.x series.
 *
 * Each minor version in the series is handled by either a `5.77.x.mysql.tpl` file,
 * or a function in this class named `upgrade_5_77_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_FiveSeventySeven extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_77_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Alter Queue.name length', 'alterColumn', 'civicrm_queue', 'name', "varchar(128)  NOT NULL COMMENT 'Name of the queue'", FALSE);
    $this->addTask('Alter QueueItem.queue_name length', 'alterColumn', 'civicrm_queue_item', 'queue_name', "varchar(128) NOT NULL COMMENT 'Name of the queue which includes this item'", FALSE);
  }

}
