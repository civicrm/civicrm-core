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
 * Upgrade logic for the 5.55.x series.
 *
 * Each minor version in the series is handled by either a `5.55.x.mysql.tpl` file,
 * or a function in this class named `upgrade_5_55_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_FiveFiftyFive extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_55_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
  }

  public function upgrade_5_55_beta2($rev): void {
    $this->addTask(ts('Fix Event Badge Upgrade'), 'fix_event_badge_upgrade');
  }

  public static function fix_event_badge_upgrade() {
    $problematic_fields = CRM_Core_DAO::executeQuery('SELECT id, data FROM civicrm_print_label WHERE data like \'%crmDate:\"%B %E%f\"%\'');
    while ($problematic_fields->fetch()) {
      $data = $problematic_fields->data;
      $data = str_replace('crmDate:"%B %E%f"', 'crmDate:\\\"%B %E%f\\\"', $data);
      CRM_Core_DAO::executeQuery("UPDATE civicrm_print_label SET data = '{$data}' WHERE id = %1", [1 => [$problematic_fields->id, 'Positive']]);
    }
    return TRUE;
  }

}
