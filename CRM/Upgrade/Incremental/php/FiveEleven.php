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
 * Upgrade logic for FiveEleven
 */
class CRM_Upgrade_Incremental_php_FiveEleven extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_5_11_alpha1($rev) {
    // Not used // $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Update smart groups where jcalendar fields have been converted to datepicker', 'updateSmartGroups', [
      'datepickerConversion' => [
        'grant_application_received_date',
        'grant_decision_date',
        'grant_money_transfer_date',
        'grant_due_date',
      ],
    ]);
    if (Civi::settings()->get('civimail_multiple_bulk_emails')) {
      $this->addTask('Update any on hold groups to reflect field change', 'updateOnHold', $rev);
    }
  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_5_11_beta1($rev) {
    // Not used // $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    if (Civi::settings()->get('civimail_multiple_bulk_emails')) {
      $this->addTask('Update any on hold groups to reflect field change', 'updateOnHold', $rev);
    }
  }

  /**
   * Update on hold groups -note the core function layout for this sort of upgrade changed in 5.12 - don't copy this.
   */
  public function updateOnHold($ctx, $version) {
    $groupUpdateObject = new CRM_Upgrade_Incremental_SmartGroups($version);
    $groupUpdateObject->convertEqualsStringToInArray('on_hold');
    return TRUE;
  }

}
