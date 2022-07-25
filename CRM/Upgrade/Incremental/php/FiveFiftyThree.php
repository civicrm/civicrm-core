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
 * Upgrade logic for the 5.53.x series.
 *
 * Each minor version in the series is handled by either a `5.53.x.mysql.tpl` file,
 * or a function in this class named `upgrade_5_53_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_FiveFiftyThree extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_53_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
  }

  /**
   * Alter civicrm_action_schedule.limit_to column from boolean to int and update the values
   */
  public static function changeColumnLimitTo() {
    CRM_Core_DAO::executeQuery("ALTER TABLE `civicrm_action_schedule` CHANGE `limit_to` `limit_to` int unsigned NOT NULL DEFAULT 1 COMMENT 'Is this the recipient criteria limited to OR in addition to?'", [], TRUE, NULL, FALSE, FALSE);
    CRM_Core_DAO::executeQuery("UPDATE `civicrm_action_schedule` SET `limit_to` =  CASE
      WHEN `limit_to` = 1 THEN 2
      WHEN `limit_to` = 0 THEN 3
      ELSE 1
    END", [], TRUE, NULL, FALSE, FALSE);
    return TRUE;
  }

}
