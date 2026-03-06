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
 * Upgrade logic for the 6.14.x series.
 *
 * Each minor version in the series is handled by either a `6.14.x.mysql.tpl` file,
 * or a function in this class named `upgrade_6_14_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_SixFourteen extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_6_14_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Add File.is_public', 'alterSchemaField', 'File', 'is_public', [
      'title' => ts('File Is Public'),
      'sql_type' => 'boolean',
      'input_type' => 'Toggle',
      'required' => TRUE,
      'description' => ts('Controls whether file is stored in public or private directory.'),
      'default' => FALSE,
    ], 'AFTER `description`');
    $this->addTask('Add PaymentProcessor.config', 'alterSchemaField', 'PaymentProcessor', 'config', [
      'title' => ts('Configuration'),
      'sql_type' => 'text',
      'input_type' => 'Textarea',
      'required' => FALSE,
      'description' => ts('JSON blob of config as appropriate for the specific integration'),
    ], 'AFTER `accepted_credit_cards`');
  }

}
