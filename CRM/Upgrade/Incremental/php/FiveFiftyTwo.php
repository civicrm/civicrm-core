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
 * Upgrade logic for the 5.52.x series.
 *
 * Each minor version in the series is handled by either a `5.52.x.mysql.tpl` file,
 * or a function in this class named `upgrade_5_52_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_FiveFiftyTwo extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_52_alpha1($rev): void {
    $this->addSnapshotTask('contribution', CRM_Utils_SQL_Select::from('civicrm_contribution')
      ->where('(contribution_recur_id IS NOT NULL) or (is_template = 1)')
      ->select(['id', 'contribution_recur_id', 'is_template', 'total_amount'])
    );
    $this->addSnapshotTask('contribution_recur', CRM_Utils_SQL_Select::from('civicrm_contribution_recur')
      ->select(['id', 'amount', 'modified_date'])
    );

    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
  }

}
