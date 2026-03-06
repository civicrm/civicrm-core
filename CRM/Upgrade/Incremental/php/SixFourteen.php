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

  public function setPreUpgradeMessage(&$preUpgradeMessage, $rev, $currentVer = NULL) {
    if ($rev === '6.14.alpha1') {
      if (CRM_Core_DAO::singleValueQuery('SELECT MIN(id) FROM civicrm_activity WHERE is_current_revision = 0')) {
        $docUrl = 'https://civicrm.org/redirect/activities-5.57';
        $docAnchor = 'target="_blank" href="' . htmlentities($docUrl) . '"';
        $preUpgradeMessage .= '<p>' . ts('Your database contains CiviCase activity revisions which have been deprecated since 5.54. As of 6.14 they will begin to appear as duplicates everywhere and you may experience issues editing or working with activities that have revisions. For further instructions see this <a %1>Lab Snippet</a>.', [1 => $docAnchor]) . '</p>';
      }
    }
  }

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
    $this->addTask('Drop civicrm_activity.is_current_revision index', 'dropIndex', 'civicrm_activity', 'index_is_current_revision');
  }

}
