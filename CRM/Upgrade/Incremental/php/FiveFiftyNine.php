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
 * Upgrade logic for the 5.59.x series.
 *
 * Each minor version in the series is handled by either a `5.59.x.mysql.tpl` file,
 * or a function in this class named `upgrade_5_59_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_FiveFiftyNine extends CRM_Upgrade_Incremental_Base {

  public function setPreUpgradeMessage(&$preUpgradeMessage, $rev, $currentVer = NULL): void {
    if ($rev === '5.59.alpha1') {
      if (empty(CRM_Core_Config::singleton()->userSystem->is_wordpress)) {
        $preUpgradeMessage .= '<p>' . ts('The handling of invalid smarty template code in mailings, reminders and other automated messages has changed. For details, see <a %1>upgrade notes</a>.',
            [1 => 'href="https://docs.civicrm.org/sysadmin/en/latest/upgrade/version-specific/#civicrm-559" target="_blank"']) . '</p>';
      }
    }
  }

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_59_alpha1(string $rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Drop column civicrm_custom_field.mask', 'dropColumn', 'civicrm_custom_field', 'mask');
  }

}
