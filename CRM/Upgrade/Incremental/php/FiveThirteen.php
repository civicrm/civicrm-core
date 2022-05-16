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
 * Upgrade logic for FiveThirteen
 */
class CRM_Upgrade_Incremental_php_FiveThirteen extends CRM_Upgrade_Incremental_Base {

  /**
   * Compute any messages which should be displayed after upgrade.
   *
   * @param string $postUpgradeMessage
   *   alterable.
   * @param string $rev
   *   an intermediate version; note that setPostUpgradeMessage is called repeatedly with different $revs.
   */
  public function setPostUpgradeMessage(&$postUpgradeMessage, $rev) {
    if ($rev == '5.13.alpha1' && CRM_Core_Component::isEnabled('CiviCampaign')) {
      $postUpgradeMessage .= '<br /><br />' . ts("If you have created a report based on the Mailing Summary Report template and it outputs or filters on campaigns, You will need to go back to that report and re-save the report after selecting and or setting the campaign filters up again");
    }
  }

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
