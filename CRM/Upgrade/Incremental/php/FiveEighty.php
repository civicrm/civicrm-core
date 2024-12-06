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
 * Upgrade logic for the 5.80.x series.
 *
 * Each minor version in the series is handled by either a `5.80.x.mysql.tpl` file,
 * or a function in this class named `upgrade_5_80_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_FiveEighty extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_80_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Increase length of Website.url field', 'alterSchemaField', 'Website', 'url', [
      'title' => ts('Website'),
      'sql_type' => 'varchar(2048)',
      'input_type' => 'Text',
      'description' => ts('Website'),
      'add' => '3.2',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'size' => '45',
      ],
    ]);
    $this->addTask('Increase length of Activity.location field', 'alterSchemaField', 'Activity', 'location', [
      'title' => ts('Location'),
      'sql_type' => 'varchar(2048)',
      'input_type' => 'Text',
      'description' => ts('Location of the activity (optional, open text).'),
      'add' => '1.1',
      'unique_name' => 'activity_location',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
    ]);
  }

}
