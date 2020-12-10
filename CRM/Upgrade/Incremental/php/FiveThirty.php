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
 * Upgrade logic for FiveThirty */
class CRM_Upgrade_Incremental_php_FiveThirty extends CRM_Upgrade_Incremental_Base {

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
  //    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
  //    $this->addTask('Do the foo change', 'taskFoo', ...);
  //    // Additional tasks here...
  //    // Note: do not use ts() in the addTask description because it adds unnecessary strings to transifex.
  //    // The above is an exception because 'Upgrade DB to %1: SQL' is generic & reusable.
  //  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_5_30_alpha1($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Add core (required) extension Financial ACLs', 'installFinancialAcls');
  }

  // public static function taskFoo(CRM_Queue_TaskContext $ctx, ...) {
  //   return TRUE;
  // }

  /**
   * Install financialacls extension.
   *
   * This feature is restructured as a core extension - which is primarily a code cleanup step.
   *
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   *
   * @throws \CiviCRM_API3_Exception
   * @throws \CRM_Core_Exception
   */
  public static function installFinancialAcls(CRM_Queue_TaskContext $ctx) {
    // Install via direct SQL manipulation. Note that:
    // (1) This extension has no activation logic.
    // (2) On new installs, the extension is activated purely via default SQL INSERT.
    // (3) Caches are flushed at the end of the upgrade.
    // ($) Over long term, upgrade steps are more reliable in SQL. API/BAO sometimes don't work mid-upgrade.
    $insert = CRM_Utils_SQL_Insert::into('civicrm_extension')->row([
      'type' => 'module',
      'full_name' => 'financialacls',
      'name' => 'financialacls',
      'label' => 'Financial ACLs',
      'file' => 'financialacls',
      'schema_version' => NULL,
      'is_active' => 1,
    ]);
    CRM_Core_DAO::executeQuery($insert->usingReplace()->toSQL());

    return TRUE;
  }

}
