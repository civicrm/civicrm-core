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
 * Upgrade logic for FiveSixteen
 */
class CRM_Upgrade_Incremental_php_FiveSixteen extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_5_16_alpha1($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Update smart groups to rename filters on contribution_date to receive_date', 'updateSmartGroups', [
      'renameField' => [
        ['old' => 'contribution_date', 'new' => 'receive_date'],
        ['old' => 'contribution_date_low', 'new' => 'receive_date_low'],
        ['old' => 'contribution_date_high', 'new' => 'receive_date_high'],
        ['old' => 'contribution_date_relative', 'new' => 'receive_date_relative'],
      ],
    ]);
    $this->addTask('Update smart groups where jcalendar fields have been converted to datepicker', 'updateSmartGroups', [
      'datepickerConversion' => [
        'receive_date',
        'contribution_cancel_date',
      ],
    ]);
  }

}
