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
    CRM_Upgrade_Incremental_Base::alterColumn($ctx, 'civicrm_option_group', 'description', "TEXT COMMENT 'Option group description.'", TRUE);
    $schema = new CRM_Logging_Schema();
    $schema->fixSchemaDifferences();
    $values = [
      [
        'group' => 'gender',
        'description' => ts('CiviCRM is pre-configured with standard options for individual gender (Male, Female, Other). Modify these options as needed for your installation.'),
      ],
      [
        'group' => 'individual_prefix',
        'description' => ts('CiviCRM is pre-configured with standard options for individual contact prefixes (Ms., Mr., Dr. etc.). Customize these options and add new ones as needed for your installation.'),
      ],
      [
        'group' => 'mobile_provider',
        'description' => ts('When recording mobile phone numbers for contacts, it may be useful to include the Mobile Phone Service Provider (e.g. Cingular, Sprint, etc.). CiviCRM is installed with the most commonly encountered service providers. Administrators may define as many additional providers as needed.'),
      ],
      [
        'group' => 'instant_messenger_service',
        'description' => ts('Commonly-used messaging apps are listed here. Administrators may define as many additional providers as needed.'),
      ],
      [
        'group' => 'individual_suffix',
        'description' => ts('CiviCRM is pre-configured with standard options for individual contact name suffixes (Jr., Sr., II etc.). Customize these options and add new ones as needed for your installation.'),
      ],
      [
        'group' => 'activity_type',
        'description' => ts('Activities track interactions with contacts. Some activity types are reserved for use by automated processes, others can be freely configured.'),
      ],
      [
        'group' => 'payment_instrument',
        'description' => ts('You may choose to record the payment method used for each contribution and fee. Reserved payment methods are required - you may modify their labels but they can not be deleted (e.g. Check, Credit Card, Debit Card). If your site requires additional payment methods, you can add them here. You can associate each payment method with a Financial Account which specifies where the payment is going (e.g. a bank account for checks and cash).'),
      ],
      [
        'group' => 'accept_creditcard',
        'description' => ts('The following credit card options will be offered to contributors using Online Contribution pages. You will need to verify which cards are accepted by your chosen Payment Processor and update these entries accordingly.')
        . ts('IMPORTANT: These options do not control credit card/payment method choices for sites and/or contributors using the PayPal Express service (e.g. where billing information is collected on the Payment Processor\'s website).'),
      ],
      [
        'group' => 'event_type',
        'description' => ts('Use Event Types to categorize your events. Event feeds can be filtered by Event Type and participant searches can use Event Type as a criteria.'),
      ],
      [
        'group' => 'participant_role',
        'description' => ts('Define participant roles for events here (e.g. Attendee, Host, Speaker...). You can then assign roles and search for participants by role.'),
      ],
      [
        'group' => 'from_email_address',
        'description' => ts('By default, CiviCRM uses the primary email address of the logged in user as the FROM address when sending emails to contacts. However, you can use this page to define one or more general Email Addresses that can be selected as an alternative. EXAMPLE: "Client Services" <clientservices@example.org>.'),
      ],
    ];
    foreach ($values as $value) {
      \Civi\Api4\OptionGroup::update(FALSE)
        ->addWhere('name', '=', $value['group'])
        ->addValue('description', $value['description'])
        ->execute();
    }
    return TRUE;
  }

}
