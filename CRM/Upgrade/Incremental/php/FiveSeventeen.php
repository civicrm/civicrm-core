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
 * Upgrade logic for FiveSeventeen
 */
class CRM_Upgrade_Incremental_php_FiveSeventeen extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_5_17_alpha1($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Update smart groups where jcalendar fields have been converted to datepicker', 'updateSmartGroups', [
      'datepickerConversion' => [
        'contribution_recur_start_date',
        'contribution_recur_next_sched_contribution_date',
        'contribution_recur_cancel_date',
        'contribution_recur_end_date',
        'contribution_recur_create_date',
        'contribution_recur_modified_date',
        'contribution_recur_failure_retry_date',
      ],
    ]);
    $this->addTask(ts('Add pptx to accepted attachment file types'), 'updateFileTypes');
  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_5_17_1($rev) {
    // Not used // $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    // Need to do this again because the alpha1 version had a typo and so didn't do anything.
    $this->addTask(ts('Add pptx to accepted attachment file types'), 'updateFileTypes');
  }

  /**
   * Update safe file types.
   */
  public static function updateFileTypes() {
    CRM_Core_BAO_OptionValue::ensureOptionValueExists([
      'option_group_id' => 'safe_file_extension',
      'label' => 'pptx',
      'name' => 'pptx',
    ]);
    return TRUE;
  }

}
