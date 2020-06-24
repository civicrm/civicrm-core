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
 * Upgrade logic for FiveEleven */
class CRM_Upgrade_Incremental_php_FiveEleven extends CRM_Upgrade_Incremental_Base {

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
  public function upgrade_5_11_alpha1($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Update smart groups where jcalendar fields have been converted to datepicker', 'updateSmartGroups', [
      'datepickerConversion' => [
        'grant_application_received_date',
        'grant_decision_date',
        'grant_money_transfer_date',
        'grant_due_date',
      ],
    ]);
    if (Civi::settings()->get('civimail_multiple_bulk_emails')) {
      $this->addTask('Update any on hold groups to reflect field change', 'updateOnHold', $rev);
    }
  }

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_5_11_beta1($rev) {
    if (Civi::settings()->get('civimail_multiple_bulk_emails')) {
      $this->addTask('Update any on hold groups to reflect field change', 'updateOnHold', $rev);
    }
  }

  /**
   * Update on hold groups -note the core function layout for this sort of upgrade changed in 5.12 - don't copy this.
   */
  public function updateOnHold($ctx, $version) {
    $groupUpdateObject = new CRM_Upgrade_Incremental_SmartGroups($version);
    $groupUpdateObject->convertEqualsStringToInArray('on_hold');
    return TRUE;
  }

}
