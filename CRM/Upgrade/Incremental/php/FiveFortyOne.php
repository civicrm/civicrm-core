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
 * Upgrade logic for FiveFortyOne
 */
class CRM_Upgrade_Incremental_php_FiveFortyOne extends CRM_Upgrade_Incremental_Base {

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
  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_5_41_alpha1($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Change custom data ACLs from "View" to "Edit"', 'changeCustomACLViewToEdit');
  }

  /**
   * Prior to 5.41, there was no functional difference between View and Edit custom data permissions.
   * To avoid unexpected privilege de-escalation post-upgrade, set all View ACLs to Edit.
   *
   * @param CRM_Queue_TaskContext $ctx
   * @return bool
   */
  public static function changeCustomACLViewToEdit(CRM_Queue_TaskContext $ctx) {
    \Civi\Api4\ACL::update(FALSE)
      ->addValue('operation', 'Edit')
      ->addWhere('entity_table', '=', 'civicrm_acl_role')
      ->addWhere('object_table', '=', 'civicrm_custom_group')
      ->addWhere('operation', '=', 'View')
      ->execute();
    return TRUE;
  }

}
