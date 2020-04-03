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
 * Upgrade logic for FiveTwentyFour */
class CRM_Upgrade_Incremental_php_FiveTwentyFour extends CRM_Upgrade_Incremental_Base {

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
    //   $preUpgradeMessage .= '<p>' . ts('A new permission, "%1", has been added. This permission is now used to control access to the Manage Tags screen.', array(1 => ts('manage tags'))) . '</p>';
    // }
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

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_5_24_alpha1($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Install sequential creditnote extension', 'installCreditNotes');
    $this->addTask('Drop obsolete columns from saved_search table', 'dropSavedSearchColumns');
    $this->addTask('Smart groups: Add api_entity column to civicrm_saved_search', 'addColumn',
      'civicrm_saved_search', 'api_entity', "varchar(255) DEFAULT NULL COMMENT 'Entity name for API based search'"
    );
    $this->addTask('Smart groups: Add api_params column to civicrm_saved_search', 'addColumn',
      'civicrm_saved_search', 'api_params', "text DEFAULT NULL COMMENT 'Parameters for API based search'"
    );
  }

  /**
   * Install sequentialCreditNotes extension.
   *
   * This feature is restructured as a core extension - which is primarily a code cleanup step.
   *
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   *
   * @throws \CiviCRM_API3_Exception
   */
  public static function installCreditNotes(CRM_Queue_TaskContext $ctx) {
    // Install via direct SQL manipulation. Note that:
    // (1) This extension has no activation logic.
    // (2) On new installs, the extension is activated purely via default SQL INSERT.
    // (3) Caches are flushed at the end of the upgrade.
    // ($) Over long term, upgrade steps are more reliable in SQL. API/BAO sometimes don't work mid-upgrade.
    $insert = CRM_Utils_SQL_Insert::into('civicrm_extension')->row([
      'type' => 'module',
      'full_name' => 'sequentialcreditnotes',
      'name' => 'Sequential credit notes',
      'label' => 'Sequential credit notes',
      'file' => 'sequentialcreditnotes',
      'schema_version' => NULL,
      'is_active' => 1,
    ]);
    CRM_Core_DAO::executeQuery($insert->usingReplace()->toSQL());
    return TRUE;
  }

  /**
   * Delete unused columns from civicrm_saved_search.
   *
   * Follow up on https://github.com/civicrm/civicrm-core/pull/14891
   *
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   */
  public static function dropSavedSearchColumns(CRM_Queue_TaskContext $ctx) {
    self::dropColumn($ctx, 'civicrm_saved_search', 'select_tables');
    self::dropColumn($ctx, 'civicrm_saved_search', 'where_tables');
    self::dropColumn($ctx, 'civicrm_saved_search', 'where_clause');
    return TRUE;
  }

}
