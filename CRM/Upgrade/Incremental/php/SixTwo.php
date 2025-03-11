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
 * Upgrade logic for the 6.2.x series.
 *
 * Each minor version in the series is handled by either a `6.2.x.mysql.tpl` file,
 * or a function in this class named `upgrade_6_2_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_SixTwo extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_6_2_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Add column "civicrm_managed.checksum"', 'alterSchemaField', 'Managed', 'checksum', [
      'title' => ts('Checksum'),
      'sql_type' => 'varchar(45)',
      'input_type' => 'Text',
      'required' => FALSE,
      'description' => ts('Configuration of the managed-entity when last stored'),
    ]);
    $this->addTask('Set upload_date in file table', 'setFileUploadDate');
    $this->addTask('Set default for upload_date in file table', 'alterSchemaField', 'File', 'upload_date', [
      'title' => ts('File Upload Date'),
      'sql_type' => 'datetime',
      'input_type' => 'Select Date',
      'required' => TRUE,
      'readonly' => TRUE,
      'default' => 'CURRENT_TIMESTAMP',
      'description' => ts('Date and time that this attachment was uploaded or written to server.'),
    ]);
  }

  public static function setFileUploadDate(): bool {
    $sql = 'SELECT id, uri FROM civicrm_file WHERE upload_date IS NULL';
    $dao = CRM_Core_DAO::executeQuery($sql);
    $dir = CRM_Core_Config::singleton()->customFileUploadDir;
    while ($dao->fetch()) {
      $fileCreatedDate = time();
      if ($dao->uri) {
        $filePath = $dir . $dao->uri;
        // Get created date from file if possible
        if (is_file($filePath)) {
          $fileCreatedDate = filectime($filePath);
        }
      }
      CRM_Core_DAO::executeQuery('UPDATE civicrm_file SET upload_date = %1 WHERE id = %2', [
        1 => [date('YmdHis', $fileCreatedDate), 'Date'],
        2 => [$dao->id, 'Integer'],
      ]);
    }

    return TRUE;
  }

}
