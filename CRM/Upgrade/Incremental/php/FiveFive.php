<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007.                                       |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License along with this program; if not, contact CiviCRM LLC       |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 * Upgrade logic for FiveFive */
class CRM_Upgrade_Incremental_php_FiveFive extends CRM_Upgrade_Incremental_Base {

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
    // Example: Generate a pre-upgrade message.
    // if ($rev == '5.12.34') {
    //   $preUpgradeMessage .= '<p>' . ts('A new permission has been added called %1 This Permission is now used to control access to the Manage Tags screen', array(1 => 'manage tags')) . '</p>';
    // }
  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_5_5_alpha1($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => $rev)), 'runSql', $rev);
    $this->addTask('Entity File schema updates', 'entityFileSchemaUpdate');
  }

  /**
   * dev/core#321 - Prevent duplicate entries in civicrm_entity_file
   *
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   */
  public static function entityFileSchemaUpdate(CRM_Queue_TaskContext $ctx) {
    if (CRM_Core_BAO_SchemaHandler::checkIfIndexExists('civicrm_entity_file', 'index_entity_file_id')) {
      // Delete any stray duplicate rows and add unique index to prevent new dupes and enable INSERT/UPDATE combo query
      CRM_Core_DAO::executeQuery('
        DELETE ef1 FROM civicrm_entity_file ef1, civicrm_entity_file ef2
          WHERE ef1.entity_table = ef2.entity_table AND
           ef1.entity_id = ef2.entity_id AND
           ef1.file_id = ef2.file_id AND
           ef1.id > ef2.id
      ');
      CRM_Core_DAO::executeQuery('ALTER TABLE civicrm_entity_file DROP INDEX `index_entity_file_id`');
      CRM_Core_BAO_SchemaHandler::createUniqueIndex('civicrm_entity_file', ['entity_table', 'entity_id', 'file_id']);
    }

    return TRUE;
  }

  /**
   * Compute any messages which should be displayed after upgrade.
   *
   * @param string $postUpgradeMessage
   *   alterable.
   * @param string $rev
   *   an intermediate version; note that setPostUpgradeMessage is called repeatedly with different $revs.
   */
  public function setPostUpgradeMessage(&$postUpgradeMessage, $rev) {
    // Example: Generate a post-upgrade message.
    // if ($rev == '5.12.34') {
    //   $postUpgradeMessage .= '<br /><br />' . ts("By default, CiviCRM now disables the ability to import directly from SQL. To use this feature, you must explicitly grant permission 'import SQL datasource'.");
    // }
  }

  /*
   * Important! All upgrade functions MUST add a 'runSql' task.
   * Uncomment and use the following template for a new upgrade version
   * (change the x in the function name):
   */

  //  /**
  //   * Upgrade function.
  //   *
  //   * @param string $rev
  //   */
  //  public function upgrade_5_0_x($rev) {
  //    $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => $rev)), 'runSql', $rev);
  //    $this->addTask('Do the foo change', 'taskFoo', ...);
  //    // Additional tasks here...
  //    // Note: do not use ts() in the addTask description because it adds unnecessary strings to transifex.
  //    // The above is an exception because 'Upgrade DB to %1: SQL' is generic & reusable.
  //  }

  // public static function taskFoo(CRM_Queue_TaskContext $ctx, ...) {
  //   return TRUE;
  // }

}
