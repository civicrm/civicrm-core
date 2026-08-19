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
 * Upgrade logic for the 6.19.x series.
 *
 * Each minor version in the series is handled by either a `6.19.x.mysql.tpl` file,
 * or a function in this class named `upgrade_6_19_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_SixNineteen extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_6_19_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Drop OptionValue.domain_id column', 'dropColumn', 'civicrm_option_value', 'domain_id');
    $this->addTask('Add column "MessageTemplate.usage"', 'alterSchemaField', 'MessageTemplate', 'usage', [
      'title' => ts('Usage'),
      'sql_type' => 'varchar(512)',
      'input_type' => 'Select',
      'description' => ts('Optional list of entities (e.g. Case, Activity) this template is relevant to.'),
      'add' => '6.19',
      'default' => NULL,
      'serialize' => CRM_Core_DAO::SERIALIZE_COMMA,
    ]);
  }

}
