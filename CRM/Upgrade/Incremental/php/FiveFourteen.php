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
 * Upgrade logic for FiveFourteen
 */
class CRM_Upgrade_Incremental_php_FiveFourteen extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_5_14_alpha1($rev) {
    // Not used // $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => $rev)), 'runSql', $rev);
  }

}
