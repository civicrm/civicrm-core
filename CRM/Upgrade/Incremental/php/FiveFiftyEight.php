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
 * Upgrade logic for the 5.58.x series.
 *
 * Each minor version in the series is handled by either a `5.58.x.mysql.tpl` file,
 * or a function in this class named `upgrade_5_58_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_FiveFiftyEight extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_58_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Add option group descriptions', 'addOptionGroupDescriptions');
  }

  public static function addOptionGroupDescriptions($ctx): bool {
    CRM_Core_DAO::executeQuery('ALTER TABLE `civicrm_option_group` MODIFY COLUMN `description` TEXT');
    $values = [
      [
        'group' => 'gender',
        'description' => '{ts escape="sql"}CiviCRM is pre-configured with standard options for individual gender (Male, Female, Other). Modify these options as needed for your installation.{/ts}',
      ],
      [
        'group' => 'individual_prefix',
        'description' => '{ts escape="sql"}CiviCRM is pre-configured with standard options for individual contact prefixes (Ms., Mr., Dr. etc.). Customize these options and add new ones as needed for your installation.{/ts}',
      ],
      [
        'group' => 'mobile_provider',
        'description' => '{ts escape="sql"}When recording mobile phone numbers for contacts, it may be useful to include the Mobile Phone Service Provider (e.g. Cingular, Sprint, etc.). CiviCRM is installed with the most commonly encountered service providers. Administrators may define as many additional providers as needed.{/ts}',
      ],
      [
        'group' => 'instant_messenger_service',
        'description' => '{ts escape="sql"}When recording Instant Messenger (IM) \'screen names\' for contacts, it is useful to include the IM Service Provider (e.g. AOL, Yahoo, etc.). CiviCRM is installed with the most commonly encountered service providers. Administrators may define as many additional providers as needed.{/ts}',
      ],
      [
        'group' => 'individual_suffix',
        'description' => '{ts escape="sql"}CiviCRM is pre-configured with standard options for individual contact name suffixes (Jr., Sr., II etc.). Customize these options and add new ones as needed for your installation.{/ts}',
      ],
      [
        'group' => 'activity_type',
        'description' => '{ts escape="sql"}Activities are \'interactions with contacts\' which you want to record and track. This list is sorted by component and then by weight within the component.{/ts}',
      ],
      [
        'group' => 'payment_instrument',
        'description' => '{ts escape="sql"}You may choose to record the payment method used for each contribution and fee. Reserved payment methods are required - you may modify their labels but they can not be deleted (e.g. Check, Credit Card, Debit Card). If your site requires additional payment methods, you can add them here. You can associate each payment method with a Financial Account which specifies where the payment is going (e.g. a bank account for checks and cash).{/ts}',
      ],
      [
        'group' => 'accept_creditcard',
        'description' => '{ts escape="sql"}The following credit card options will be offered to contributors using Online Contribution pages. You will need to verify which cards are accepted by your chosen Payment Processor and update these entries accordingly.{/ts}<br /><br />'
        . '{ts escape="sql"}IMPORTANT: This page does NOT control credit card/payment method choices for sites and/or contributors using the PayPal Express service (e.g. where billing information is collected on the Payment Processor\'s website).{/ts}',
      ],
      [
        'group' => 'event_type',
        'description' => '{ts escape="sql"}Use Event Types to categorize your events. Event feeds can be filtered by Event Type and participant searches can use Event Type as a criteria.{/ts}',
      ],
      [
        'group' => 'participant_role',
        'description' => '{ts escape="sql"}Define participant roles for events here (e.g. Attendee, Host, Speaker...). You can then assign roles and search for participants by role.{/ts}',
      ],
      [
        'group' => 'from_email_address',
        'description' => '{ts escape="sql"}By default, CiviCRM uses the primary email address of the logged in user as the FROM address when sending emails to contacts. However, you can use this page to define one or more general Email Addresses that can be selected as an alternative. EXAMPLE: <em>"Client Services" &lt;clientservices@example.org&gt;</em>{/ts}',
      ],
    ];
    foreach ($values as $value) {
      CRM_Core_DAO::executeQuery('UPDATE `civicrm_option_group` SET `description` = %1 WHERE `name` = %2', [
        1 => [$value['description'], 'String'],
        2 => [$value['group'], 'String'],
      ]);
    }
    return TRUE;
  }

}
