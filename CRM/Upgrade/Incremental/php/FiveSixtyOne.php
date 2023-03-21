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
 * Upgrade logic for the 5.61.x series.
 *
 * Each minor version in the series is handled by either a `5.61.x.mysql.tpl` file,
 * or a function in this class named `upgrade_5_61_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_FiveSixtyOne extends CRM_Upgrade_Incremental_Base {

  public function setPreUpgradeMessage(&$preUpgradeMessage, $rev, $currentVer = NULL): void {
    if ($rev === '5.61.alpha1' && CRM_Core_DAO::singleValueQuery('SELECT id FROM civicrm_contribution_recur LIMIT 1')) {
      $documentationUrl = 'https://docs.civicrm.org/dev/en/latest/financial/recurring-contributions/';
      $documentationAnchor = 'target="_blank" href="' . htmlentities($documentationUrl) . '"';
      $extensionUrl = 'https://docs.civicrm.org/dev/en/latest/financial/recurring-contributions/';
      $extensionAnchor = 'target="_blank" href="' . htmlentities($extensionUrl) . '"';

      $preUpgradeMessage .= '<p>' .
        ts('This release contains a change to the behaviour of recurring contributions under some edge-case circumstances.')
        . ' ' . ts('Since 5.49 the amount and currency on the recurring contribution record changed when the amount of any contribution against it was changed, indicating a change in future intent.')
        . ' ' . ts('It is generally not possible to edit the amount for contributions linked to recurring contributions so for most sites this would never occur anyway.')
        . ' ' . ts('If you still want this behaviour you should install the <a %1>Recur future amounts extension</a>', [1 => $extensionAnchor])
        . ' ' . ts('Please <a %1>read about recurring contribution templates</a> for more information', [1 => $documentationAnchor])
        . '</p>';
    }
  }

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_61_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask(ts('Drop index %1', [1 => 'civicrm_cache.UI_group_path_date']), 'dropIndex', 'civicrm_cache', 'UI_group_path_date');
    $this->addTask(ts('Create index %1', [1 => 'civicrm_cache.UI_group_name_path']), 'addIndex', 'civicrm_cache', [['group_name', 'path']], 'UI');
  }

}
