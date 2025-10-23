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
 * Upgrade logic for the 6.9.x series.
 *
 * Each minor version in the series is handled by either a `6.9.x.mysql.tpl` file,
 * or a function in this class named `upgrade_6_9_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_SixNine extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_6_9_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);

    $this->addTask('Add column "Contribution.created_date"', 'alterSchemaField', 'Contribution', 'created_date', [
      'title' => ts('Created Date'),
      'sql_type' => 'timestamp',
      'input_type' => 'Select Date',
      'readonly' => TRUE,
      'description' => ts('When was the contribution created.'),
      'unique_name' => 'contribution_created_date',
      'default' => 'CURRENT_TIMESTAMP',
      'usage' => [
        'export',
      ],
      'input_attrs' => [
        'label' => ts('Created Date'),
      ],
    ]);

    $this->addTask('Add column "Contribution.modified_date"', 'alterSchemaField', 'Contribution', 'modified_date', [
      'title' => ts('Modified Date'),
      'sql_type' => 'timestamp',
      'input_type' => 'Select Date',
      'readonly' => TRUE,
      'description' => ts('When was the contribution created or modified or deleted.'),
      'unique_name' => 'contribution_modified_date',
      'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
      'usage' => [
        'export',
      ],
      'input_attrs' => [
        'label' => ts('Modified Date'),
      ],
    ]);

    $this->addTask('Add column "Participant.created_date"', 'alterSchemaField', 'Participant', 'created_date', [
      'title' => ts('Created Date'),
      'sql_type' => 'timestamp',
      'input_type' => 'Select Date',
      'readonly' => TRUE,
      'description' => ts('When was the participant record was created.'),
      'unique_name' => 'participant_created_date',
      'default' => 'CURRENT_TIMESTAMP',
      'usage' => [
        'export',
      ],
      'input_attrs' => [
        'format_type' => 'activityDateTime',
        'label' => ts('Created Date'),
      ],
    ]);

    $this->addTask('Add column "Participant.modified_date"', 'alterSchemaField', 'Participant', 'modified_date', [
      'title' => ts('Modified Date'),
      'sql_type' => 'timestamp',
      'input_type' => 'Select Date',
      'readonly' => TRUE,
      'description' => ts('When was the participant record created or modified or deleted.'),
      'unique_name' => 'participant_modified_date',
      'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
      'usage' => [
        'export',
      ],
      'input_attrs' => [
        'format_type' => 'activityDateTime',
        'label' => ts('Modified Date'),
      ],
    ]);

    // dev/core#2290 - Remove id from cache tables
    $tables = ['civicrm_cache', 'civicrm_acl_cache', 'civicrm_acl_contact_cache', 'civicrm_group_contact_cache'];
    foreach ($tables as $table) {
      $this->addTask("dev/core#2290 - Remove id column from '$table' table", 'dropColumn', $table, 'id');
    }
  }

}
