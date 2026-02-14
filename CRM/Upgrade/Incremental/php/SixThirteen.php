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
 * Upgrade logic for the 6.13.x series.
 *
 * Each minor version in the series is handled by either a `6.13.x.mysql.tpl` file,
 * or a function in this class named `upgrade_6_13_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_SixThirteen extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_6_13_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Replace {$email} with "" in membership_autorenew_billing', 'updateMessageToken', 'membership_autorenew_billing', '$email', '', $rev);
    $this->addTask('Replace {$email} with "" in contribution_recurring_billing', 'updateMessageToken', 'contribution_recurring_billing', '$email', '', $rev);
    $this->addTask('Replace {$email} with "{contact.email_primary.email}" in contribution_online_receipt', 'updateMessageToken', 'contribution_online_receipt', '$email', 'contact.email_primary.email', $rev);
    $this->addTask('Update quicksearch options to support non-primary search', 'updateQuicksearchOptionsPrimary');
  }

  /**
   * Convert `quicksearch_options` setting to new format that supports non-primary search.
   *
   * @param \CRM_Queue_TaskContext $ctx
   * @return bool
   */
  public static function updateQuicksearchOptionsPrimary($ctx): bool {
    $settingValue = Civi::settings()->get('quicksearch_options');
    $oldDefault = [
      'sort_name',
      'id',
      'external_identifier',
      'first_name',
      'last_name',
      'email_primary.email',
      'phone_primary.phone_numeric',
      'address_primary.street_address',
      'address_primary.city',
      'address_primary.postal_code',
      'job_title',
    ];
    // Already set to the default, just revert & we're done
    if ($settingValue === $oldDefault || $settingValue === Civi::settings()->getDefault('quicksearch_options')) {
      Civi::settings()->revert('quicksearch_options');
      return TRUE;
    }

    // Update old values to new format which uses explicit joins
    $updateNeeded = FALSE;

    $map = [
      'email_primary.email' => 'Email.email',
      'phone_primary.phone_numeric' => 'Phone.phone_numeric',
      'address_primary.street_address' => 'Address.street_address',
      'address_primary.city' => 'Address.city',
      'address_primary.postal_code' => 'Address.postal_code',
    ];

    foreach ($settingValue as $index => $value) {
      if (isset($map[$value])) {
        $updateNeeded = TRUE;
        $settingValue[$index] = $map[$value];
      }
    }
    if ($updateNeeded) {
      Civi::settings()->set('quicksearch_options', $settingValue);
    }
    return TRUE;
  }

}
