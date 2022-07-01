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
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask(ts('Fix any Recurring Contribution total amounts that do not match template contributions', [1 => $rev]), 'synRecurTotal', $rev);
  }

  /**
   * Update any recurring contributions to have the same amount
   * as the recurring template contribution if it exists.
   *
   * Some of these got out of sync over recent changes.
   *
   * @return bool
   */
  public function synRecurTotal(): bool {
    CRM_Core_DAO::executeQuery('
      UPDATE civicrm_contribution.recur r
      LEFT JOIN civicrm_contribution c ON contribution_recur_id = r.id
      AND c.is_template = 1
      SET amount = total_amount');
    return TRUE;
  }

}
