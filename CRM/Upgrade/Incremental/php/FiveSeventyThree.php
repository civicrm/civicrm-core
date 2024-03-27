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
 * Upgrade logic for the 5.73.x series.
 *
 * Each minor version in the series is handled by either a `5.73.x.mysql.tpl` file,
 * or a function in this class named `upgrade_5_73_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_FiveSeventyThree extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_73_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask(ts('Disable financial ACL extension if unused'), 'disableFinancialAcl');
  }

  public static function disableFinancialAcl($rev): bool {
    $setting = CRM_Core_DAO::singleValueQuery('SELECT value FROM civicrm_setting WHERE name = "acl_financial_type"');
    if ($setting) {
      $setting = unserialize($setting);
    }
    if (!$setting) {
      CRM_Core_DAO::executeQuery('UPDATE civicrm_extension SET is_active = 0 WHERE full_name = "financialacls"');
    }
    return TRUE;
  }

}
