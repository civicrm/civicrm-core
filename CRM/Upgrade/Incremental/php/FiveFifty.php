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
 * Upgrade logic for the 5.50.x series.
 *
 * Each minor version in the series is handled by either a `5.50.x.mysql.tpl` file,
 * or a function in this class named `upgrade_5_50_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_FiveFifty extends CRM_Upgrade_Incremental_Base {

  public function setPreUpgradeMessage(&$preUpgradeMessage, $rev, $currentVer = NULL) {
    parent::setPreUpgradeMessage($preUpgradeMessage, $rev, $currentVer);
    if ($rev === '5.50.alpha1') {
      $preUpgradeMessage = '<p>' . ts('To improve data-protection, CiviCRM (5.50+) may create snapshots of upgraded data ("snap_*" tables). The upgrader will automatically prune old snapshots. The "snap_*" tables should generally follow the same backup/replication rules as other MySQL tables, but advanced administrators may fine-tune per preference.') . '</p>'
        . $preUpgradeMessage;
      // The issue here is that most backup/replication rules are per-database (eg `mysqldump DB_NAME`), but some
      // systems have special filters in their backup configuration (eg `civicrm_*` vs `civicrm_tmp_*` vs `log_civicrm_*`).
      // We want to let these users know there's a change -- without scaring the regular users who don't have that.
    }
  }

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_50_alpha1($rev): void {
    $this->addSnapshotTask('mappings', CRM_Utils_SQL_Select::from('civicrm_mapping'));
    $this->addSnapshotTask('fields', CRM_Utils_SQL_Select::from('civicrm_mapping_field'));
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask(ts('Convert import mappings to use names'), 'convertMappingFieldLabelsToNames', $rev);

  }

  /**
   * Convert saved mapping fields for contact imports to use name rather than
   * label.
   *
   * Currently the 'name' column in civicrm_mapping_field holds names like
   * 'First Name' or, more tragically 'Contact ID (match to contact)'.
   *
   * This updates them to hold the name - eg. 'first_name' in conjunction with
   * a
   * change in the contact import.
   *
   * (Getting the other entities done is a stretch goal).
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  public static function convertMappingFieldLabelsToNames(): bool {
    CRM_Import_ImportProcessor::convertSavedFields();
    return TRUE;
  }

}
