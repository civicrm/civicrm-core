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
 * Upgrade logic for the 5.53.x series.
 *
 * Each minor version in the series is handled by either a `5.53.x.mysql.tpl` file,
 * or a function in this class named `upgrade_5_53_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_FiveFiftyThree extends CRM_Upgrade_Incremental_Base {

  public function setPostUpgradeMessage(&$postUpgradeMessage, $rev) {
    if ($rev === '5.53.alpha1') {
      $postUpgradeMessage .= '<br/>' . ts("WARNING: CiviCRM has changed our the date format variable %1 is outputted when using CRM_Core_Date::customFormat / crmDate.  Please review your <a href='%2' target='_blank'>Date Format</a> settings and your <a href='%3' target='_blank'>system message templates</a> for usage of the %1 variable", [
        1 => '%A',
        2 => CRM_Utils_System::url('civicrm/admin/setting/date', ['reset' => 1], TRUE),
        3 => CRM_Utils_System::url('civicrm/admin/messageTemplates', ['reset' => 1, 'selectedChild' => 'workflow'], TRUE),
      ]);
    }
  }

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_53_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
  }

}
