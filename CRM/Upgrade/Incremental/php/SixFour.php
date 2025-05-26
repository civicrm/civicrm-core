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
 * Upgrade logic for the 6.4.x series.
 *
 * Each minor version in the series is handled by either a `6.4.x.mysql.tpl` file,
 * or a function in this class named `upgrade_6_4_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_SixFour extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_6_4_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Rename multisite_is_enabled setting', 'renameMultisiteSetting');
    $this->addTask('Add Contact Image file reference', 'alterSchemaField', 'Contact', 'image_file_id', [
      'title' => ts('Image File ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'File',
      'description' => ts('FK to civicrm_file'),
      'add' => '6.4',
      'input_attrs' => [
        'label' => ts('Image'),
      ],
      'entity_reference' => [
        'entity' => 'File',
        'key' => 'id',
        'fk' => FALSE,
      ],
    ], 'image_URL');
  }

  public static function renameMultisiteSetting(): bool {
    CRM_Core_DAO::executeQuery('UPDATE civicrm_setting SET name = "multisite_is_enabled" WHERE name = "is_enabled"');
    return TRUE;
  }

}
