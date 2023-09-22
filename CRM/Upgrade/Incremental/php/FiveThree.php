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
 * Upgrade logic for FiveThree
 */
class CRM_Upgrade_Incremental_php_FiveThree extends CRM_Upgrade_Incremental_Base {

  /**
   * Compute any messages which should be displayed beforeupgrade.
   *
   * Note: This function is called iteratively for each upcoming
   * revision to the database.
   *
   * @param string $preUpgradeMessage
   * @param string $rev
   *   a version number, e.g. '4.4.alpha1', '4.4.beta3', '4.4.0'.
   * @param null $currentVer
   */
  public function setPreUpgradeMessage(&$preUpgradeMessage, $rev, $currentVer = NULL) {
    if ($rev == '5.3.0') {
      $params = [
        1 => 'edit user-driven message templates',
        2 => 'edit system workflow message templates',
        3 => 'edit message templates',
      ];
      $preUpgradeMessage .= '<p>' . ts('New granular permissions called %1 and %2 have been added for %3 permission. These permissions help to limit user access per template', $params) . '</p>';
    }
    // Example: Generate a pre-upgrade message.
    // if ($rev == '5.12.34') {
    //   $preUpgradeMessage .= '<p>' . ts('A new permission, "%1", has been added. This permission is now used to control access to the Manage Tags screen.', array(1 => ts('manage tags'))) . '</p>';
    // }
  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_5_3_alpha1($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('CRM-19948 - Add created_id column to civicrm_file', 'addFileCreatedIdColumn');
  }

  public static function addFileCreatedIdColumn(CRM_Queue_TaskContext $ctx) {
    self::addColumn($ctx, 'civicrm_file', 'created_id', "int unsigned COMMENT 'FK to civicrm_contact, who uploaded this file'");

    CRM_Core_BAO_SchemaHandler::safeRemoveFK('civicrm_file', 'FK_civicrm_file_created_id');

    CRM_Core_DAO::executeQuery("
      ALTER TABLE `civicrm_file`
        ADD CONSTRAINT `FK_civicrm_file_created_id`
        FOREIGN KEY (`created_id`)
        REFERENCES `civicrm_contact`(`id`)
        ON DELETE SET NULL
        ON UPDATE CASCADE;
    ");

    return TRUE;
  }

}
