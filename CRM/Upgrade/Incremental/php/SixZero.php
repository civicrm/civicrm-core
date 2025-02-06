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
 * Upgrade logic for the 6.0.x series.
 *
 * Each minor version in the series is handled by either a `6.0.x.mysql.tpl` file,
 * or a function in this class named `upgrade_6_0_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_SixZero extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_6_0_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask(
      'Convert MembershipLog.modified_date to timestamp',
      'alterColumn',
      'civicrm_membership_log',
      'modified_date',
      "timestamp NULL DEFAULT NULL COMMENT 'Date this membership modification action was logged.'",
      FALSE
    );
    $this->addTask('Set a default activity priority', 'addActivityPriorityDefault');
    $this->addExtensionTask('Enable Riverlea extension', ['riverlea']);
  }

  /**
   * This task sets the Normal option as the default activity status.
   * It was previously hardcoded in Form and BAO files.
   *
   * @return bool
   */
  public static function addActivityPriorityDefault() {
    // Check if a default option is already set (could be other than Normal)
    $oid = CRM_Core_DAO::singleValueQuery('SELECT ov.id
      FROM civicrm_option_value ov
      LEFT JOIN civicrm_option_group og ON (og.id = ov.option_group_id)
      WHERE og.name = %1 and ov.is_default = 1', [
        1 => ['priority', 'String'],
      ]);

    if ($oid) {
      return TRUE;
    }

    // Set 'Normal' as the default
    $sql = CRM_Utils_SQL::interpolate('UPDATE civicrm_option_value SET is_default = 1 WHERE option_group_id = #group AND name = @name', [
      'name' => 'Normal',
      'group' => CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_option_group WHERE name = "priority"'),
    ]);
    CRM_Core_DAO::executeQuery($sql);

    return TRUE;
  }

}
