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
 * Upgrade logic for the 6.5.x series.
 *
 * Each minor version in the series is handled by either a `6.5.x.mysql.tpl` file,
 * or a function in this class named `upgrade_6_5_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_SixFive extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_6_5_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Install legacyprofiles extension', 'installLegacyProfiles');
  }

  /**
   * Install legacyprofiles extension.
   *
   * This feature is restructured as a core extension - which is primarily a code cleanup step.
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  public static function installLegacyProfiles(): bool {
    // Based on the instructions for the FiveThirty financialacls upgrade step
    // Install via direct SQL manipulation. Note that:
    // (1) This extension has no activation logic as of 5.76 (the DB tables are still in core)
    // (2) This extension is not enabled on new installs.
    // (3) Caches are flushed at the end of the upgrade.
    // ($) Over long term, upgrade steps are more reliable in SQL. API/BAO sometimes don't work mid-upgrade.
    $active = CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_uf_field WHERE visibility != "User and User Admin Only" LIMIT 1');
    if ($active) {
      $insert = CRM_Utils_SQL_Insert::into('civicrm_extension')->row([
        'type' => 'module',
        'full_name' => 'legacyprofiles',
        'name' => 'legacyprofiles',
        'label' => 'Legacy Profiles',
        'file' => 'legacyprofiles',
        'schema_version' => NULL,
        'is_active' => 1,
      ]);
      CRM_Core_DAO::executeQuery($insert->usingReplace()->toSQL());
    }
    return TRUE;
  }

}
