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
 * Upgrade logic for the 5.81.x series.
 *
 * Each minor version in the series is handled by either a `5.81.x.mysql.tpl` file,
 * or a function in this class named `upgrade_5_81_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_FiveEightyOne extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_81_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Set "group_multiple_parents" setting if used', 'setMultipleGroupParents');
  }

  /**
   * Set "group_multiple_parents" setting if used.
   *
   * @return bool
   * @throws CRM_Core_Exception
   */
  public static function setMultipleGroupParents(): bool {
    $sql = "SELECT COUNT(`id`)
      FROM `civicrm_group`
      WHERE `parents` LIKE '%,%'";
    $groupFoundWithMutipleParents = (bool) CRM_Core_DAO::singleValueQuery($sql);
    Civi::settings()->set('group_multiple_parents', $groupFoundWithMutipleParents);
    return TRUE;
  }

}
