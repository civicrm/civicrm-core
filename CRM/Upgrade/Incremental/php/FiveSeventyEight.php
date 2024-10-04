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
 * Upgrade logic for the 5.78.x series.
 *
 * Each minor version in the series is handled by either a `5.78.x.mysql.tpl` file,
 * or a function in this class named `upgrade_5_78_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_FiveSeventyEight extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_78_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);

    // Uninstall `eventcart` extension if `enable_cart` setting is not enabled.
    // As of 5.78 this setting no longer exists, so manually check for it in the db:
    $cartSetting = CRM_Core_DAO::singleValueQuery('SELECT value FROM civicrm_setting WHERE name = "enable_cart"', [], TRUE, FALSE);
    if ($cartSetting) {
      $cartSetting = unserialize($cartSetting);
    }
    // Event Cart is disabled. If it has no data, completely uninstall it.
    if (!$cartSetting) {
      // Check for data
      $tableExists = \CRM_Core_DAO::checkTableExists('civicrm_event_cart_participant');
      if ($tableExists) {
        $dataExists = CRM_Core_DAO::singleValueQuery('SELECT COUNT(id) FROM civicrm_event_cart_participant');
      }
      if (empty($dataExists)) {
        $this->addUninstallTask('Uninstall Event Cart extension.', ['eventcart']);
      }
      else {
        CRM_Core_DAO::executeQuery('UPDATE civicrm_extension SET is_active = 0 WHERE full_name = "eventcart"');
      }
    }
  }

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_78_beta1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
  }

}
