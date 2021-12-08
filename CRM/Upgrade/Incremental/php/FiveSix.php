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
 * Upgrade logic for FiveSix
 */
class CRM_Upgrade_Incremental_php_FiveSix extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_5_6_beta2($rev) {
    // Not used // $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('dev/core#107 - Add Activity\'s default assignee options', 'addActivityDefaultAssigneeOptions');
  }

  public static function addActivityDefaultAssigneeOptions() {
    // This data was originally added via upgrader in 5.4.alpha1. However, it was omitted from the
    // default data for new installations. Re-running the upgrader should fix sites initialized
    // between 5.4.alpha1-5.6.beta1.
    return CRM_Upgrade_Incremental_php_FiveFour::addActivityDefaultAssigneeOptions();
  }

}
