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
 * Upgrade logic for the 5.71.x series.
 *
 * Each minor version in the series is handled by either a `5.71.x.mysql.tpl` file,
 * or a function in this class named `upgrade_5_71_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_FiveSeventyOne extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_71_alpha1(string $rev): void {

    // It is important the line above run before the sql, which will populate the fields
    // before they are made required.
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);

    $this->addTask('Add entity_delete column', 'addColumn', 'civicrm_custom_field', 'fk_entity_on_delete', "VARCHAR(255) NOT NULL DEFAULT 'set_null' COMMENT 'Behavior if referenced entity is deleted.' AFTER `fk_entity`");

    $this->addTask('Make civicrm_uf_group.name required', 'alterColumn', 'civicrm_uf_group', 'name',
      "varchar(64) NOT NULL  COMMENT 'Form name.'"
    );
    $this->addTask('Make civicrm_uf_group.frontend_title required', 'alterColumn', 'civicrm_uf_group', 'frontend_title',
      "varchar(64) NOT NULL  COMMENT 'Profile Form Public title'",
      TRUE
    );
  }

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_71_beta1(string $rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Make civicrm_event.selfcancelxfer_time', 'alterColumn', 'civicrm_event', 'selfcancelxfer_time',
      "int(11) DEFAULT 0 NOT NULL COMMENT 'Number of hours prior to event start date to allow self-service cancellation or transfer.'"
    );
  }

}
