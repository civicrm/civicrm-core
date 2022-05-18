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
 * Upgrade logic for FiveOne
 */
class CRM_Upgrade_Incremental_php_FiveOne extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_5_50_alpha1($rev) {
    $this->addtask('Update civicrm_action_schedule.limit_to to be integer instead of boolean', 'changeColumnLimitTo');
  }

  /**
   * Alter civicrm_action_schedule.limit_to column from boolean to int and update the values
   */
  public static function changeColumnLimitTo() {
    CRM_Core_DAO::executeQuery("ALTER TABLE `civicrm_action_schedule` CHANGE `limit_to` `limit_to` int unsigned NOT NULL DEFAULT 1 COMMENT 'Is this the recipient criteria limited to OR in addition to?'", [], TRUE, NULL, FALSE, FALSE);
    CRM_Core_DAO::executeQuery("UPDATE `civicrm_action_schedule` SET `limit_to` =  CASE
      WHEN `limit_to` = 1 THEN 2
      WHEN `limit_to` = 0 THEN 3
      ELSE 1
    END", [], TRUE, NULL, FALSE, FALSE);
    return TRUE;
  }

}
