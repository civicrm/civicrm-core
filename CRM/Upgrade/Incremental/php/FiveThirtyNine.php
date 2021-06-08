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
 * Upgrade logic for FiveThirtyNine */
class CRM_Upgrade_Incremental_php_FiveThirtyNine extends CRM_Upgrade_Incremental_Base {

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
  public function setPreUpgradeMessage(&$preUpgradeMessage, $rev, $currentVer = NULL) {
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
  public function setPostUpgradeMessage(&$postUpgradeMessage, $rev) {
    // Example: Generate a post-upgrade message.
    // if ($rev == '5.12.34') {
    //   $postUpgradeMessage .= '<br /><br />' . ts("By default, CiviCRM now disables the ability to import directly from SQL. To use this feature, you must explicitly grant permission 'import SQL datasource'.");
    // }
  }

  public function upgrade_5_39_alpha1($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('core-issue#2486  - Add product_id foreign key to civicrm_contribution_product', 'addContributionProductFK');
  }

  /**
   * @param \CRM_Queue_TaskContext $ctx
   *
   * @return bool
   */
  public static function addContributionProductFK(CRM_Queue_TaskContext $ctx): bool {
    if (!self::checkFKExists('civicrm_contribution_product', 'FK_civicrm_contribution_product_product_id')) {
      CRM_Core_DAO::executeQuery("
        ALTER TABLE `civicrm_contribution_product`
          ADD CONSTRAINT `FK_civicrm_contribution_product_product_id`
            FOREIGN KEY (`product_id`) REFERENCES `civicrm_product` (`id`)
            ON DELETE CASCADE;
      ", [], TRUE, NULL, FALSE, FALSE);
    }

    return TRUE;
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

  // public static function taskFoo(CRM_Queue_TaskContext $ctx, ...) {
  //   return TRUE;
  // }

}
