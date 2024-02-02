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
 * Upgrade logic for the 5.71.x series.
 *
 * Each minor version in the series is handled by either a `5.71.x.mysql.tpl` file,
 * or a function in this class named `upgrade_5_71_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_FiveSeventyOne extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_71_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('dev/core#4947 - Add contribution_page_id column to civicrm_contribution_recur table', 'addColumn', 'civicrm_contribution_recur', 'contribution_page_id',
      'int unsigned COMMENT "The Contribution Page which triggered this contribution"');
    $this->addTask('dev/core#4947 - Add contribution_page_id foreign key to civicrm_contribution_recur', 'addContributionPageIdFKToContributionRecur');
  }

  /**
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   */
  public static function addContributionPageIdFKToContributionRecur(CRM_Queue_TaskContext $ctx): bool {
    if (!self::checkFKExists('civicrm_contribution_recur', 'FK_civicrm_contribution_recur_contribution_page_id')) {
      CRM_Core_DAO::executeQuery("
        ALTER TABLE `civicrm_contribution_recur`
          ADD CONSTRAINT `FK_civicrm_contribution_recur_contribution_page_id`
            FOREIGN KEY (`contribution_page_id`) REFERENCES `civicrm_contribution_page` (`id`)
            ON DELETE SET NULL;
      ", [], TRUE, NULL, FALSE, FALSE);
    }
    return TRUE;
  }

}
