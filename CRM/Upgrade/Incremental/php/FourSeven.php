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
 * Upgrade logic for 4.7
 *
 * NOTE: All incremental steps up to the last step of 4.7.x have been removed.
 */
class CRM_Upgrade_Incremental_php_FourSeven extends CRM_Upgrade_Incremental_Base {

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
    if ($rev == '4.7.32') {
      $preUpgradeMessage .= '<p>' . ts('A new %1 permission has been added. It is not granted by default. If you use SMS, you may wish to review your permissions.', [1 => 'send SMS']) . '</p>';
    }
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
  }

  /**
   * Upgrade function.
   *
   * NOTE: 4.7.31 was the last public release of 4.7.x. The version 4.7.32 indicates an internal step that was de-facto 5.0.
   *
   * @param string $rev
   */
  public function upgrade_4_7_32($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);

    $this->addTask('CRM-21733: Add status_override_end_date field to civicrm_membership table', 'addColumn', 'civicrm_membership', 'status_override_end_date',
      "date DEFAULT NULL COMMENT 'The end date of membership status override if (Override until selected date) override type is selected.'");
  }

}
