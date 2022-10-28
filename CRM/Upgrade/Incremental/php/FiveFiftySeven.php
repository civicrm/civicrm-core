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
 * Upgrade logic for the 5.57.x series.
 *
 * Each minor version in the series is handled by either a `5.57.x.mysql.tpl` file,
 * or a function in this class named `upgrade_5_57_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_FiveFiftySeven extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_57_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Add in Client Removed Activity Type', 'addCaseClientRemovedActivity');
  }

  public static function addCaseClientRemovedActivity() {
    if (CRM_Core_Component::isEnabled('CiviCase')) {
      CRM_Core_BAO_OptionValue::ensureOptionValueExists([
        'option_group_id' => 'activity_type',
        'name' => 'Case Client Removed',
        'label' => ts('Case Client was removed from Case'),
        'description' => ts('Case client was removed from a case'),
        'is_active' => TRUE,
        'component_id' => CRM_Core_Component::getComponentID('CiviCase'),
        'icon' => 'fa-trash',
      ]);
    }
    return TRUE;
  }

}
