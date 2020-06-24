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
 * Upgrade logic for FiveThirteen */
class CRM_Upgrade_Incremental_php_FiveThirteen extends CRM_Upgrade_Incremental_Base {

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
    $config = CRM_Core_Config::singleton();
    $campaignEnabled = in_array("CiviCampaign", $config->enableComponents);
    if ($rev == '5.13.alpha1' && $campaignEnabled) {
      $postUpgradeMessage .= '<br /><br />' . ts("If you have created a report based on the Mailing Summary Report template and it outputs or filters on campaigns, You will need to go back to that report and re-save the report after selecting and or setting the campaign filters up again");
    }
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
  public function upgrade_5_13_alpha1($rev) {
    $this->addTask('Add title to civicrm_payment_processor', 'addColumn',
      'civicrm_payment_processor', 'title', "text COMMENT 'Payment Processor Descriptive Name.'", TRUE, '5.13.alpha1'
    );
    $this->addTask('Add cancel reason column to civicrm_contribution_recur', 'addColumn',
      'civicrm_contribution_recur', 'cancel_reason', "text COMMENT 'Free text field for a reason for cancelling'", FALSE
    );
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
  }

}
