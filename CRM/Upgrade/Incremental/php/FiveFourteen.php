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
 * Upgrade logic for FiveFourteen */
class CRM_Upgrade_Incremental_php_FiveFourteen extends CRM_Upgrade_Incremental_Base {

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

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_5_14_alpha1($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', array(1 => $rev)), 'runSql', $rev);

    // Only need to rebuild view if CiviCase is enabled: otherwise will be
    // rebuilt when component is enabled
    $config = CRM_Core_Config::singleton();
    if (in_array('CiviCase', $config->enableComponents)) {
      $this->addTask('Rebuild case activity views', 'rebuildCaseActivityView', $rev);
    }
    // Additional tasks here...
    // Note: do not use ts() in the addTask description because it adds unnecessary strings to transifex.
    // The above is an exception because 'Upgrade DB to %1: SQL' is generic & reusable.
  }

  /**
   * Rebuild the view of recent and upcoming case activities
   *
   * See https://github.com/civicrm/civicrm-core/pull/14086 and
   * https://lab.civicrm.org/dev/core/issues/832
   *
   * @param CRM_Queue_TaskContext $ctx
   * @return bool
   */
  public static function rebuildCaseActivityView($ctx) {
    if (!CRM_Case_BAO_Case::createCaseViews()) {
      CRM_Core_Error::debug_log_message(ts("Could not create the MySQL views for CiviCase. Your mysql user needs to have the 'CREATE VIEW' permission"));
      return FALSE;
    }
    return TRUE;
  }

}
