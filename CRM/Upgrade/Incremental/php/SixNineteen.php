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
 * Upgrade logic for the 6.19.x series.
 *
 * Each minor version in the series is handled by either a `6.19.x.mysql.tpl` file,
 * or a function in this class named `upgrade_6_19_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_SixNineteen extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_6_19_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Drop OptionValue.domain_id column', 'dropColumn', 'civicrm_option_value', 'domain_id');
    $this->addTask('Update contribution page menu items to use the "edit all contribution pages" permission', 'updateContributionPageNavPermission');
  }

  /**
   * Point the contribution page navigation items at the new
   * 'edit all contribution pages' permission.
   *
   * Only rows still holding the shipped default are updated, so that sites which
   * have customised these menu items keep their own setting.
   *
   * @return bool
   */
  public static function updateContributionPageNavPermission(): bool {
    CRM_Core_DAO::executeQuery(
      'UPDATE civicrm_navigation
        SET permission = %1
        WHERE name IN ("Manage Contribution Pages", "New Contribution Page")
          AND permission = %2
          AND permission_operator = "AND"',
      [
        1 => ['access CiviContribute,edit all contribution pages', 'String'],
        2 => ['access CiviContribute,administer CiviCRM', 'String'],
      ]
    );
    return TRUE;
  }

}
