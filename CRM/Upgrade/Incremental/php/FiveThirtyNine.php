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
 * Upgrade logic for FiveThirtyNine
 */
class CRM_Upgrade_Incremental_php_FiveThirtyNine extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_5_39_alpha1($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Update smart groups to rename filters on case_from and case_to to case_start_date and case_end_date', 'updateSmartGroups', [
      'renameField' => [
        ['old' => 'case_from_relative', 'new' => 'case_start_date_relative'],
        ['old' => 'case_from_start_date_high', 'new' => 'case_start_date_high'],
        ['old' => 'case_from_start_date_low', 'new' => 'case_start_date_low'],
        ['old' => 'case_to_relative', 'new' => 'case_end_date_relative'],
        ['old' => 'case_to_end_date_high', 'new' => 'case_end_date_high'],
        ['old' => 'case_to_end_date_low', 'new' => 'case_end_date_low'],
        [
          'old' => 'mailing_date_relative',
          'new' => 'mailing_job_start_date_relative',
        ],
        ['old' => 'mailing_date_high', 'new' => 'mailing_job_start_date_high'],
        ['old' => 'mailing_date_low', 'new' => 'mailing_job_start_date_low'],
        [
          'old' => 'relation_start_date_low',
          'new' => 'relationship_start_date_low',
        ],
        [
          'old' => 'relation_start_date_high',
          'new' => 'relationship_start_date_high',
        ],
        [
          'old' => 'relation_start_date_relative',
          'new' => 'relationship_start_date_relative',
        ],
        [
          'old' => 'relation_end_date_low',
          'new' => 'relationship_end_date_low',
        ],
        [
          'old' => 'relation_end_date_high',
          'new' => 'relationship_end_date_high',
        ],
        [
          'old' => 'relation_end_date_relative',
          'new' => 'relationship_end_date_relative',
        ],
        ['old' => 'event_start_date_low', 'new' => 'event_low'],
        ['old' => 'event_end_date_high', 'new' => 'event_high'],
      ],
    ]);
    $this->addTask('Update smart groups where jcalendar fields have been converted to datepicker', 'updateSmartGroups', [
      'datepickerConversion' => [
        'birth_date',
        'deceased_date',
        'case_start_date',
        'case_end_date',
        'mailing_job_start_date',
        'relationship_start_date',
        'relationship_end_date',
        'event',
        'relation_active_period_date',
        'created_date',
        'modified_date',
      ],
    ]);

    $this->addTask('Convert Log date searches to their final names either created date or modified date', 'updateSmartGroups', [
      'renameLogFields' => [],
    ]);
    $this->addTask('Convert Custom data based smart groups from jcalendar to datepicker', 'updateSmartGroups', [
      'convertCustomSmartGroups' => NULL,
    ]);
  }

}
