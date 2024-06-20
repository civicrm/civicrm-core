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
 * Upgrade logic for the 5.76.x series.
 *
 * Each minor version in the series is handled by either a `5.76.x.mysql.tpl` file,
 * or a function in this class named `upgrade_5_76_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_FiveSeventySix extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_76_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Add start_date to civicrm_mailing table', 'addColumn', 'civicrm_mailing', 'start_date', "timestamp NULL DEFAULT NULL COMMENT 'date on which this mailing was started.'");
    $this->addTask('Add end_date to civicrm_mailing table', 'addColumn', 'civicrm_mailing', 'end_date', "timestamp NULL DEFAULT NULL COMMENT 'date on which this mailing was completed.'");
    $this->addTask('Add status to civicrm_mailing table', 'addColumn', 'civicrm_mailing', 'status', "varchar(12) DEFAULT NULL COMMENT 'The status of this Mailing'");
    $this->addTask('Alter translation to make string non-required', 'alterColumn', 'civicrm_translation', 'string',
      "longtext NULL COMMENT 'Translated string'"
    );
  }

}
