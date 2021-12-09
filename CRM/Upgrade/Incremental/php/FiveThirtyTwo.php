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
 * Upgrade logic for FiveThirtyTwo
 */
class CRM_Upgrade_Incremental_php_FiveThirtyTwo extends CRM_Upgrade_Incremental_Base {

  /**
   * Install contributioncancelactions extension.
   *
   * This feature is restructured as a core extension - which is primarily a code cleanup step but
   * also permits sites / extensions to disable the core actions to do their own workflows.
   *
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   *
   * @throws \CRM_Core_Exception
   */
  public static function installContributionCancelActions(CRM_Queue_TaskContext $ctx) {
    // Install via direct SQL manipulation. Note that:
    // (1) This extension has no activation logic.
    // (2) On new installs, the extension is activated purely via default SQL INSERT.
    // (3) Caches are flushed at the end of the upgrade.
    // ($) Over long term, upgrade steps are more reliable in SQL. API/BAO sometimes don't work mid-upgrade.
    $insert = CRM_Utils_SQL_Insert::into('civicrm_extension')->row([
      'type' => 'module',
      'full_name' => 'contributioncancelactions',
      'name' => 'contributioncancelactions',
      'label' => 'Contribution cancel actions',
      'file' => 'contributioncancelactions',
      'schema_version' => NULL,
      'is_active' => 1,
    ]);
    CRM_Core_DAO::executeQuery($insert->usingReplace()->toSQL());

    return TRUE;
  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_5_32_alpha1($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Add column civicrm_saved_search.name', 'addColumn', 'civicrm_saved_search', 'name', "varchar(255)   DEFAULT NULL COMMENT 'Unique name of saved search'");
    $this->addTask('Add column civicrm_saved_search.label', 'addColumn', 'civicrm_saved_search', 'label', "varchar(255)   DEFAULT NULL COMMENT 'Administrative label for search'");
    $this->addTask('Add index civicrm_saved_search.UI_name', 'addIndex', 'civicrm_saved_search', 'name', 'UI');
    $this->addTask('Install contribution cancel actions extension', 'installContributionCancelActions');
  }

}
