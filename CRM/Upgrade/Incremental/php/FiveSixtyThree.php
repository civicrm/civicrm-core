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
 * Upgrade logic for the 5.63.x series.
 *
 * Each minor version in the series is handled by either a `5.63.x.mysql.tpl` file,
 * or a function in this class named `upgrade_5_63_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_FiveSixtyThree extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_63_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);

    // Campaign indexes
    $this->addTask(ts('Drop index %1', [1 => 'civicrm_campaign.UI_campaign_type_id']), 'dropIndex', 'civicrm_campaign', 'UI_campaign_type_id');
    $this->addTask(ts('Create index %1', [1 => 'civicrm_campaign.index_campaign_type_id']), 'addIndex', 'civicrm_campaign', 'campaign_type_id', 'index');
    $this->addTask(ts('Drop index %1', [1 => 'civicrm_campaign.UI_campaign_status_id']), 'dropIndex', 'civicrm_campaign', 'UI_campaign_status_id');
    $this->addTask(ts('Create index %1', [1 => 'civicrm_campaign.index_status_id']), 'addIndex', 'civicrm_campaign', 'status_id', 'index');
    $this->addTask('Add default value to civicrm_campaign.created_date', 'alterColumn', 'civicrm_campaign', 'created_date', "datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Date and time that Campaign was created.'");

    $enabledComponents = Civi::settings()->get('enable_components');
    $extensions = array_map(['CRM_Utils_String', 'convertStringToSnakeCase'], $enabledComponents);
    $this->addExtensionTask('Enable component extensions', $extensions);
  }

}
