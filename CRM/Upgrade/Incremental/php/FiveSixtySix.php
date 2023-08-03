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
 * Upgrade logic for the 5.66.x series.
 *
 * Each minor version in the series is handled by either a `5.66.x.mysql.tpl` file,
 * or a function in this class named `upgrade_5_66_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_FiveSixtySix extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_66_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    // These run after the sql file
    $this->addTask('Make ActionSchedule.name required', 'alterColumn', 'civicrm_action_schedule', 'name', "varchar(64) NOT NULL COMMENT 'Name of the action(reminder)'");
    $this->addTask(ts('Create index %1', [1 => 'civicrm_action_schedule.UI_name']), 'addIndex', 'civicrm_action_schedule', 'name', 'UI');
  }

}
