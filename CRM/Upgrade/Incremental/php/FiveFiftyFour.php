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
 * Upgrade logic for the 5.54.x series.
 *
 * Each minor version in the series is handled by either a `5.54.x.mysql.tpl` file,
 * or a function in this class named `upgrade_5_54_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_FiveFiftyFour extends CRM_Upgrade_Incremental_Base {

  public function setPreUpgradeMessage(&$preUpgradeMessage, $rev, $currentVer = NULL) {
    parent::setPreUpgradeMessage($preUpgradeMessage, $rev, $currentVer);
    if ($rev === '5.54.alpha1') {
      if (\Civi::settings()->get('civicaseActivityRevisions')) {
        $preUpgradeMessage .= '<p>' . ts('The setting that used to be at <em>Administer &gt; CiviCase &gt; CiviCase Settings</em> for <strong>Enable deprecated Embedded Activity Revisions</strong> is enabled, but is no longer functional.<ul><li>For more information see this <a %1>Lab Snippet</a>.</li></ul>', [1 => 'target="_blank" href="https://lab.civicrm.org/-/snippets/85"']) . '</p>';
      }
    }
  }

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_54_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
  }

}
