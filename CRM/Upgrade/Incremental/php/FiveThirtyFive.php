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
 * Upgrade logic for FiveThirtyFive
 */
class CRM_Upgrade_Incremental_php_FiveThirtyFive extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_5_35_alpha1(string $rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);

    $this->addTask('dev/core#2329 - Add is_active to Country', 'addColumn',
      'civicrm_country', 'is_active', "tinyint DEFAULT 1 COMMENT 'Is this Country active?'");
    $this->addTask('dev/core#2329 - Add is_active to StateProvince', 'addColumn',
      'civicrm_state_province', 'is_active', "tinyint DEFAULT 1 COMMENT 'Is this StateProvince active?'");
    $this->addTask('dev/core#2329 - Add is_active to County', 'addColumn',
      'civicrm_county', 'is_active', "tinyint DEFAULT 1 COMMENT 'Is this County active?'");
  }

}
