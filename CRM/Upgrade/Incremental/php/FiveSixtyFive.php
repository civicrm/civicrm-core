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
 * Upgrade logic for the 5.65.x series.
 *
 * Each minor version in the series is handled by either a `5.65.x.mysql.tpl` file,
 * or a function in this class named `upgrade_5_65_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_FiveSixtyFive extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_65_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    // These should run after the sql file.
    $this->addTask('Make LocationType.name required', 'alterColumn', 'civicrm_location_type', 'name', "varchar(64) NOT NULL COMMENT 'Location Type Name.'");
    $this->addTask('Make LocationType.display_name required', 'alterColumn', 'civicrm_location_type', 'display_name', "varchar(64) NOT NULL COMMENT 'Location Type Display Name.'", TRUE);
    $this->addTask('Make LocationType.is_reserved required', 'alterColumn', 'civicrm_location_type', 'is_reserved', "tinyint NOT NULL DEFAULT 0 COMMENT 'Is this location type a predefined system location?'");
    $this->addTask('Make LocationType.is_active required', 'alterColumn', 'civicrm_location_type', 'is_active', "tinyint NOT NULL DEFAULT 1 COMMENT 'Is this property active?'");
    $this->addTask('Make LocationType.is_default required', 'alterColumn', 'civicrm_location_type', 'is_default', "tinyint NOT NULL DEFAULT 0 COMMENT 'Is this location type the default?'");
    $this->addTask('Make Group.name required', 'alterColumn', 'civicrm_group', 'name', "varchar(255) NOT NULL COMMENT 'Internal name of Group.'");
    $this->addTask('Make Group.title required', 'alterColumn', 'civicrm_group', 'title', "varchar(255) NOT NULL COMMENT 'Alternative public title for this Group.'", TRUE);
    $this->addTask('Make Group.frontend_title required', 'alterColumn', 'civicrm_group', 'frontend_title', "varchar(255) NOT NULL COMMENT 'Alternative public description of the group.'", TRUE);

    $this->addTask('Update ActionSchedule.limit_to column', 'alterColumn', 'civicrm_action_schedule', 'limit_to', "int COMMENT 'Is this the recipient criteria limited to OR in addition to?'");
  }

}
