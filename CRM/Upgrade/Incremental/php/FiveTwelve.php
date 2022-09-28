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
 * Upgrade logic for FiveTwelve
 */
class CRM_Upgrade_Incremental_php_FiveTwelve extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_5_12_alpha1($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Update smart groups to rename filters on activity_date to activity_date_time', 'updateSmartGroups', [
      'renameFields' => [
        ['old' => 'activity_date', 'new' => 'activity_date_time'],
        ['old' => 'activity_date_low', 'new' => 'activity_date_time_low'],
        ['old' => 'activity_date_high', 'new' => 'activity_date_time_high'],
        ['old' => 'activity_date_relative', 'new' => 'activity_date_time_relative'],
      ],
    ]);
    $this->addTask('Update smart groups where jcalendar fields have been converted to datepicker', 'updateSmartGroups', [
      'datepickerConversion' => [
        'age_asof_date',
        'activity_date_time',
      ],
    ]);
  }

}
