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
 * Upgrade logic for FiveFortyFour */
class CRM_Upgrade_Incremental_php_FiveFortyFour extends CRM_Upgrade_Incremental_Base {

  /**
   * Compute any messages which should be displayed beforeupgrade.
   *
   * Note: This function is called iteratively for each incremental upgrade step.
   * There must be a concrete step (eg 'X.Y.Z.mysql.tpl' or 'upgrade_X_Y_Z()').
   *
   * @param string $preUpgradeMessage
   * @param string $rev
   *   a version number, e.g. '4.4.alpha1', '4.4.beta3', '4.4.0'.
   * @param null $currentVer
   */
  public function setPreUpgradeMessage(&$preUpgradeMessage, $rev, $currentVer = NULL): void {
    // Example: Generate a pre-upgrade message.
    // if ($rev == '5.12.34') {
    //   $preUpgradeMessage .= '<p>' . ts('A new permission, "%1", has been added. This permission is now used to control access to the Manage Tags screen.', array(1 => ts('manage tags'))) . '</p>';
    // }
  }

  /**
   * Compute any messages which should be displayed after upgrade.
   *
   * Note: This function is called iteratively for each incremental upgrade step.
   * There must be a concrete step (eg 'X.Y.Z.mysql.tpl' or 'upgrade_X_Y_Z()').
   *
   * @param string $postUpgradeMessage
   *   alterable.
   * @param string $rev
   *   an intermediate version; note that setPostUpgradeMessage is called repeatedly with different $revs.
   */
  public function setPostUpgradeMessage(&$postUpgradeMessage, $rev): void {
    // Example: Generate a post-upgrade message.
    // if ($rev == '5.12.34') {
    //   $postUpgradeMessage .= '<br /><br />' . ts("By default, CiviCRM now disables the ability to import directly from SQL. To use this feature, you must explicitly grant permission 'import SQL datasource'.");
    // }
  }

  /**
   * Upgrade function.
   * @param string $rev
   */
  public function upgrade_5_44_alpha1($rev) {
    // The runSql task populates the new column, so this addColumn task runs first
    $this->addTask('Add case_id column to civicrm_relationship_cache', 'addColumn',
      'civicrm_relationship_cache', 'case_id', "int unsigned DEFAULT NULL COMMENT 'FK to civicrm_case'"
    );
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Add FK to civicrm_relationship_cache.case_id', 'addRelationshipCacheCaseFK');
  }

  /**
   * @param \CRM_Queue_TaskContext $ctx
   * @return bool
   */
  public static function addRelationshipCacheCaseFK(CRM_Queue_TaskContext $ctx): bool {
    if (!self::checkFKExists('civicrm_relationship_cache', 'FK_civicrm_relationship_cache_case_id')) {
      CRM_Core_DAO::executeQuery("
        ALTER TABLE `civicrm_relationship_cache`
          ADD CONSTRAINT `FK_civicrm_relationship_cache_case_id`
            FOREIGN KEY (`case_id`) REFERENCES `civicrm_case` (`id`)
            ON DELETE CASCADE;
      ", [], TRUE, NULL, FALSE, FALSE);
    }
    return TRUE;
  }

}
