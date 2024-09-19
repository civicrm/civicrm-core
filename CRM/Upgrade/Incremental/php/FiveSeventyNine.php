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
 * Upgrade logic for the 5.79.x series.
 *
 * Each minor version in the series is handled by either a `5.79.x.mysql.tpl` file,
 * or a function in this class named `upgrade_5_79_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_FiveSeventyNine extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_79_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Update "Website Type" options', 'updateWebsiteType');
  }

  /**
   * Delete branded website-type options that are not in use.
   * Add new "Social" option.
   */
  public static function updateWebsiteType() {
    $query = CRM_Core_DAO::executeQuery("SELECT `id`, `value` FROM `civicrm_option_value`
      WHERE `option_group_id` = (SELECT `id` FROM `civicrm_option_group` WHERE `name` = 'website_type')
      AND `name` NOT IN ('Work', 'Main', 'Social')
      AND `value` NOT IN (SELECT `website_type_id` FROM `civicrm_website`)");
    $types = $query->fetchMap('value', 'id');
    if ($types) {
      CRM_Core_DAO::executeQuery('DELETE FROM `civicrm_option_value` WHERE `id` IN (' . implode(', ', $types) . ')');
    }
    \CRM_Core_BAO_OptionValue::ensureOptionValueExists([
      'option_group_id' => 'website_type',
      'name' => 'Social',
      'label' => ts('Social'),
    ]);
    return TRUE;
  }

}
