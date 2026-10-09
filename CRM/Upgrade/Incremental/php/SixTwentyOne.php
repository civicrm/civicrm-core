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
 * Upgrade logic for the 6.21.x series.
 *
 * Each minor version in the series is handled by either a `6.21.x.mysql.tpl` file,
 * or a function in this class named `upgrade_6_21_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_SixTwentyOne extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_6_21_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Add column "MessageTemplate.usage"', 'alterSchemaField', 'MessageTemplate', 'usage', [
      'title' => ts('Usage'),
      'sql_type' => 'varchar(512)',
      'input_type' => 'Select',
      'description' => ts('Optional list of entities (e.g. Case, Activity) this template is relevant to. Used to filter the token picker. NULL/empty means no explicit restriction: for System Workflow templates the schema is derived from the workflow; for User Driven templates it defaults to contact tokens only.'),
      'add' => '6.21',
      'default' => NULL,
      'serialize' => CRM_Core_DAO::SERIALIZE_COMMA,
    ]);
  }

}
