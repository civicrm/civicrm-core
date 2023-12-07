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
 * Upgrade logic for the 5.69.x series.
 *
 * Each minor version in the series is handled by either a `5.69.x.mysql.tpl` file,
 * or a function in this class named `upgrade_5_69_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_FiveSixtyNine extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_69_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Add is_show_calendar_links column to Event table', 'addColumn', 'civicrm_event', 'is_show_calendar_links',
      'tinyint NOT NULL DEFAULT 1 COMMENT "If true, calendar links are shown for this event"');
    $this->addTask('fix crmDate for installs that existed pre-5.43 - start date',
      'updatePrintLabelToken', 'event.start_date|crmDate:"%B %E%f}"', 'event.start_date|crmDate:\\\"%B %E%f\\\"}"', $rev
    );
    $this->addTask('fix crmDate for installs that existed pre-5.43 - end date',
      'updatePrintLabelToken', 'event.end_date|crmDate:"%B %E%f}"', 'event.end_date|crmDate:\\\"%B %E%f\\\"}"', $rev
    );
  }

}
