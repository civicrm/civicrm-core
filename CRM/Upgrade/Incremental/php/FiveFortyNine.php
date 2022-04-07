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
 * Upgrade logic for the 5.49.x series.
 *
 * Each minor version in the series is handled by either a `5.49.x.mysql.tpl` file,
 * or a function in this class named `upgrade_5_49_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_FiveFortyNine extends CRM_Upgrade_Incremental_Base {

  /**
   * @var string[][]
   * Array (keyed by tableName) of boolean columns to make NOT NULL.
   * @see self::changeBooleanColumn
   */
  private $booleanColumns = [
    'civicrm_event' => [
      'is_public' => "DEFAULT 1 COMMENT 'Public events will be included in the iCal feeds. Access to private event information may be limited using ACLs.'",
      'is_online_registration' => "DEFAULT 0 COMMENT 'If true, include registration link on Event Info page.'",
      'is_monetary' => "DEFAULT 0 COMMENT 'If true, one or more fee amounts must be set and a Payment Processor must be configured for Online Event Registration.'",
      'is_map' => "DEFAULT 0 COMMENT 'Include a map block on the Event Information page when geocode info is available and a mapping provider has been specified?'",
      'is_active' => "DEFAULT 0 COMMENT 'Is this Event enabled or disabled/cancelled?'",
      'is_show_location' => "DEFAULT 1 COMMENT 'If true, show event location.'",
      'is_email_confirm' => "DEFAULT 0 COMMENT 'If true, confirmation is automatically emailed to contact on successful registration.'",
      'is_pay_later' => "DEFAULT 0 COMMENT 'if true - allows the user to send payment directly to the org later'",
      'is_partial_payment' => "DEFAULT 0 COMMENT 'is partial payment enabled for this event'",
      'is_multiple_registrations' => "DEFAULT 0 COMMENT 'if true - allows the user to register multiple participants for event'",
      'allow_same_participant_emails' => "DEFAULT 0 COMMENT 'if true - allows the user to register multiple registrations from same email address.'",
      'has_waitlist' => "DEFAULT 0 COMMENT 'Whether the event has waitlist support.'",
      'requires_approval' => "DEFAULT 0 COMMENT 'Whether participants require approval before they can finish registering.'",
      'allow_selfcancelxfer' => "DEFAULT 0 COMMENT 'Allow self service cancellation or transfer for event?'",
      'is_template' => "DEFAULT 0 COMMENT 'whether the event has template'",
      'is_share' => "DEFAULT 1 COMMENT 'Can people share the event through social media?'",
      'is_confirm_enabled' => "DEFAULT 1 COMMENT 'If false, the event booking confirmation screen gets skipped'",
      'is_billing_required' => "DEFAULT 0 COMMENT 'if true than billing block is required this event'",
    ],
    'civicrm_contribution' => [
      'is_test' => "DEFAULT 0",
      'is_pay_later' => "DEFAULT 0",
      'is_template' => "DEFAULT 0 COMMENT 'Shows this is a template for recurring contributions.'",
    ],
    'civicrm_financial_account' => [
      'is_header_account' => "DEFAULT 0 COMMENT 'Is this a header account which does not allow transactions to be posted against it directly, but only to its sub-accounts?'",
      'is_deductible' => "DEFAULT 0 COMMENT 'Is this account tax-deductible?'",
      'is_tax' => "DEFAULT 0 COMMENT 'Is this account for taxes?'",
      'is_reserved' => "DEFAULT 0 COMMENT 'Is this a predefined system object?'",
      'is_active' => "DEFAULT 1 COMMENT 'Is this property active?'",
      'is_default' => "DEFAULT 0 COMMENT 'Is this account the default one (or default tax one) for its financial_account_type?'",
    ],
    'civicrm_premiums' => [
      'premiums_display_min_contribution' => 'DEFAULT 0',
    ],
    'civicrm_membership_status' => [
      'is_current_member' => "DEFAULT 0 COMMENT 'Does this status aggregate to current members (e.g. New, Renewed, Grace might all be TRUE... while Unrenewed, Lapsed, Inactive would be FALSE).'",
      'is_admin' => "DEFAULT 0 COMMENT 'Is this status for admin/manual assignment only.'",
      'is_default' => "DEFAULT 0 COMMENT 'Assign this status to a membership record if no other status match is found.'",
      'is_active' => "DEFAULT 1 COMMENT 'Is this membership_status enabled.'",
      'is_reserved' => "DEFAULT 0 COMMENT 'Is this membership_status reserved.'",
    ],
    'civicrm_campaign' => [
      'is_active' => "DEFAULT 1 COMMENT 'Is this Campaign enabled or disabled/cancelled?'",
    ],
    'civicrm_survey' => [
      'is_active' => "DEFAULT 1 COMMENT 'Is this survey enabled or disabled/cancelled?'",
      'is_default' => "DEFAULT 0 COMMENT 'Is this default survey?'",
      'bypass_confirm' => "DEFAULT 0 COMMENT 'Bypass the email verification.'",
      'is_share' => "DEFAULT 1 COMMENT 'Can people share the petition through social media?'",
    ],
    'civicrm_participant_status_type' => [
      'is_reserved' => "DEFAULT 0 COMMENT 'whether this is a status type required by the system'",
      'is_active' => "DEFAULT 1 COMMENT 'whether this status type is active'",
      'is_counted' => "DEFAULT 0 COMMENT 'whether this status type is counted against event size limit'",
    ],
    'civicrm_event_carts' => [
      'completed' => "DEFAULT 0",
    ],
    'civicrm_dedupe_rule_group' => [
      'is_reserved' => "DEFAULT 0 COMMENT 'Is this a reserved rule - a rule group that has been optimized and cannot be changed by the admin'",
    ],
    'civicrm_case_type' => [
      'is_active' => "DEFAULT 1 COMMENT 'Is this case type enabled?'",
      'is_reserved' => "DEFAULT 0 COMMENT 'Is this case type a predefined system type?'",
    ],
    'civicrm_tell_friend' => [
      'is_active' => "DEFAULT 1",
    ],
    'civicrm_pledge_block' => [
      'is_pledge_interval' => "DEFAULT 0 COMMENT 'Is frequency interval exposed on the contribution form.'",
    ],
    'civicrm_pcp' => [
      'is_active' => "DEFAULT 1 COMMENT 'Is Personal Campaign Page enabled/active?'",
      'is_notify' => "DEFAULT 0 COMMENT 'Notify owner via email when someone donates to page?'",
    ],
    'civicrm_cxn' => [
      'is_active' => "DEFAULT 1 COMMENT 'Is connection currently enabled?'",
    ],
    'civicrm_contribution_page' => [
      'is_credit_card_only' => "DEFAULT 0 COMMENT 'if true - processing logic must reject transaction at confirmation stage if pay method != credit card'",
      'is_monetary' => "DEFAULT 1 COMMENT 'if true - allows real-time monetary transactions otherwise non-monetary transactions'",
      'is_recur' => "DEFAULT 0 COMMENT 'if true - allows recurring contributions, valid only for PayPal_Standard'",
      'is_confirm_enabled' => "DEFAULT 1 COMMENT 'if false, the confirm page in contribution pages gets skipped'",
      'is_recur_interval' => "DEFAULT 0 COMMENT 'if true - supports recurring intervals'",
      'is_recur_installments' => "DEFAULT 0 COMMENT 'if true - asks user for recurring installments'",
      'adjust_recur_start_date' => "DEFAULT 0 COMMENT 'if true - user is able to adjust payment start date'",
      'is_pay_later' => "DEFAULT 0 COMMENT 'if true - allows the user to send payment directly to the org later'",
      'is_allow_other_amount' => "DEFAULT 0 COMMENT 'if true, page will include an input text field where user can enter their own amount'",
      'is_email_receipt' => "DEFAULT 0 COMMENT 'if true, receipt is automatically emailed to contact on success'",
      'is_active' => "DEFAULT 1 COMMENT 'Is this property active?'",
      'amount_block_is_active' => "DEFAULT 1 COMMENT 'Is this property active?'",
      'is_share' => "DEFAULT 1 COMMENT 'Can people share the contribution page through social media?'",
      'is_billing_required' => "DEFAULT 0 COMMENT 'if true - billing block is required for online contribution page'",
    ],
    'civicrm_contribution_widget' => [
      'is_active' => "DEFAULT 1 COMMENT 'Is this property active?'",
    ],
    'civicrm_payment_processor' => [
      'is_active' => "DEFAULT 1 COMMENT 'Is this processor active?'",
      'is_default' => "DEFAULT 0 COMMENT 'Is this processor the default?'",
      'is_test' => "DEFAULT 0 COMMENT 'Is this processor for a test site?'",
      'is_recur' => "DEFAULT 0 COMMENT 'Can process recurring contributions'",
    ],
    'civicrm_sms_provider' => [
      'is_default' => "DEFAULT 0",
      'is_active' => "DEFAULT 1",
    ],
    'civicrm_membership_block' => [
      'display_min_fee' => "DEFAULT 1 COMMENT 'Display minimum membership fee'",
      'is_separate_payment' => "DEFAULT 1 COMMENT 'Should membership transactions be processed separately'",
      'is_required' => "DEFAULT 0 COMMENT 'Is membership sign up optional'",
      'is_active' => "DEFAULT 1 COMMENT 'Is this membership_block enabled'",
    ],
    'civicrm_case' => [
      'is_deleted' => "DEFAULT 0",
    ],
    'civicrm_report_instance' => [
      'is_active' => "DEFAULT 0 COMMENT 'Is this entry active?'",
      'is_reserved' => "DEFAULT 0",
    ],
    'civicrm_price_set' => [
      'is_active' => "DEFAULT 1 COMMENT 'Is this price set active'",
      'is_quick_config' => "DEFAULT 0 COMMENT 'Is set if edited on Contribution or Event Page rather than through Manage Price Sets'",
      'is_reserved' => "DEFAULT 0 COMMENT 'Is this a predefined system price set  (i.e. it can not be deleted, edited)?'",
    ],
    'civicrm_dashboard_contact' => [
      'is_active' => "DEFAULT 1 COMMENT 'Is this widget active?'",
    ],
    'civicrm_mailing' => [
      'url_tracking' => "DEFAULT 0 COMMENT 'Should we track URL click-throughs for this mailing?'",
      'forward_replies' => "DEFAULT 0 COMMENT 'Should we forward replies back to the author?'",
      'auto_responder' => "DEFAULT 0 COMMENT 'Should we enable the auto-responder?'",
      'open_tracking' => "DEFAULT 0 COMMENT 'Should we track when recipients open/read this mailing?'",
      'is_completed' => "DEFAULT 0 COMMENT 'Has at least one job associated with this mailing finished?'",
      'override_verp' => "DEFAULT 0 COMMENT 'Overwrite the VERP address in Reply-To'",
      'is_archived' => "DEFAULT 0 COMMENT 'Is this mailing archived?'",
      'dedupe_email' => "DEFAULT 0 COMMENT 'Remove duplicate emails?'",
    ],
    'civicrm_mailing_job' => [
      'is_test' => "DEFAULT 0 COMMENT 'Is this job for a test mail?'",
    ],
    'civicrm_contribution_recur' => [
      'is_test' => "DEFAULT 0",
      'is_email_receipt' => "DEFAULT 1 COMMENT 'if true, receipt is automatically emailed to contact on each successful payment'",
    ],
    'civicrm_membership' => [
      'is_override' => "DEFAULT 0 COMMENT 'Admin users may set a manual status which overrides the calculated status. When this flag is true, automated status update scripts should NOT modify status for the record.'",
      'is_test' => "DEFAULT 0",
      'is_pay_later' => "DEFAULT 0",
    ],
    'civicrm_activity' => [
      'is_test' => "DEFAULT 0",
      'is_auto' => "DEFAULT 0",
      'is_current_revision' => "DEFAULT 1",
      'is_deleted' => "DEFAULT 0",
      'is_star' => "DEFAULT 0 COMMENT 'Activity marked as favorite.'",
    ],
    'civicrm_price_field' => [
      'is_enter_qty' => "DEFAULT 0 COMMENT 'Enter a quantity for this field?'",
      'is_display_amounts' => "DEFAULT 1 COMMENT 'Should the price be displayed next to the label for each option?'",
      'is_active' => "DEFAULT 1 COMMENT 'Is this price field active'",
      'is_required' => "DEFAULT 1 COMMENT 'Is this price field required (value must be > 1)'",
    ],
    'civicrm_price_field_value' => [
      'is_default' => "DEFAULT 0 COMMENT 'Is this default price field option'",
      'is_active' => "DEFAULT 1 COMMENT 'Is this price field value active'",
    ],
    'civicrm_pcp_block' => [
      'is_approval_needed' => "DEFAULT 0 COMMENT 'Does Personal Campaign Page require manual activation by administrator? (is inactive by default after setup)?'",
      'is_tellfriend_enabled' => "DEFAULT 0 COMMENT 'Does Personal Campaign Page allow using tell a friend?'",
      'is_active' => "DEFAULT 1 COMMENT 'Is Personal Campaign Page Block enabled/active?'",
    ],
    'civicrm_contribution_soft' => [
      'pcp_display_in_roll' => "DEFAULT 0",
    ],
    'civicrm_participant' => [
      'is_test' => "DEFAULT 0",
      'is_pay_later' => "DEFAULT 0",
    ],
    'civicrm_msg_template' => [
      'is_active' => "DEFAULT 1",
      'is_default' => "DEFAULT 1 COMMENT 'is this the default message template for the workflow referenced by workflow_id?'",
      'is_reserved' => "DEFAULT 0 COMMENT 'is this the reserved message template which we ship for the workflow referenced by workflow_id?'",
      'is_sms' => "DEFAULT 0 COMMENT 'Is this message template used for sms?'",
    ],
    'civicrm_prevnext_cache' => [
      'is_selected' => "DEFAULT 0",
    ],
    'civicrm_contact' => [
      'do_not_email' => "DEFAULT 0",
      'do_not_phone' => "DEFAULT 0",
      'do_not_mail' => "DEFAULT 0",
      'do_not_sms' => "DEFAULT 0",
      'do_not_trade' => "DEFAULT 0",
    ],
    'civicrm_relationship_type' => [
      'is_reserved' => "DEFAULT 0 COMMENT 'Is this relationship type a predefined system type (can not be changed or de-activated)?'",
      'is_active' => "DEFAULT 1 COMMENT 'Is this relationship type currently active (i.e. can be used when creating or editing relationships)?'",
    ],
    'civicrm_contact_type' => [
      'is_active' => "DEFAULT 1 COMMENT 'Is this entry active?'",
      'is_reserved' => "DEFAULT 0 COMMENT 'Is this contact type a predefined system type'",
    ],
    'civicrm_mailing_component' => [
      'is_active' => "DEFAULT 1 COMMENT 'Is this property active?'",
    ],
    'civicrm_country' => [
      'is_province_abbreviated' => "DEFAULT 0 COMMENT 'Should state/province be displayed as abbreviation for contacts from this country?'",
      'is_active' => "DEFAULT 1 COMMENT 'Is this Country active?'",
    ],
    'civicrm_custom_group' => [
      'collapse_display' => "DEFAULT 0 COMMENT 'Will this group be in collapsed or expanded mode on initial display ?'",
      'is_active' => "DEFAULT 1 COMMENT 'Is this property active?'",
      'is_multiple' => "DEFAULT 0 COMMENT 'Does this group hold multiple values?'",
      'collapse_adv_display' => "DEFAULT 0 COMMENT 'Will this group be in collapsed or expanded mode on advanced search display ?'",
      'is_reserved' => "DEFAULT 0 COMMENT 'Is this a reserved Custom Group?'",
      'is_public' => "DEFAULT 1 COMMENT 'Is this property public?'",
    ],
    'civicrm_custom_field' => [
      'is_required' => "DEFAULT 0 COMMENT 'Is a value required for this property.'",
      'is_searchable' => "DEFAULT 0 COMMENT 'Is this property searchable.'",
      'is_search_range' => "DEFAULT 0 COMMENT 'Is this property range searchable.'",
      'is_view' => "DEFAULT 0 COMMENT 'Is this property set by PHP Code? A code field is viewable but not editable'",
      'in_selector' => "DEFAULT 0 COMMENT 'Should the multi-record custom field values be displayed in tab table listing'",
    ],
    'civicrm_email' => [
      'is_primary' => "DEFAULT 0 COMMENT 'Is this the primary email address'",
      'is_billing' => "DEFAULT 0 COMMENT 'Is this the billing?'",
    ],
    'civicrm_im' => [
      'is_primary' => "DEFAULT 0 COMMENT 'Is this the primary IM for this contact and location.'",
      'is_billing' => "DEFAULT 0 COMMENT 'Is this the billing?'",
    ],
    'civicrm_job' => [
      'is_active' => "DEFAULT 1 COMMENT 'Is this job active?'",
    ],
    'civicrm_mail_settings' => [
      'is_default' => "DEFAULT 0 COMMENT 'whether this is the default set of settings for this domain'",
      'is_ssl' => "DEFAULT 1 COMMENT 'whether to use SSL or not'",
      'is_non_case_email_skipped' => "DEFAULT 0 COMMENT 'Enabling this option will have CiviCRM skip any emails that do not have the Case ID or Case Hash so that the system will only process emails that can be placed on case records. Any emails that are not processed will be moved to the ignored folder.'",
      'is_contact_creation_disabled_if_no_match' => "DEFAULT 0",
    ],
    'civicrm_menu' => [
      'is_active' => "DEFAULT 1 COMMENT 'Is this menu item active?'",
      'is_public' => "DEFAULT 1 COMMENT 'Is this menu accessible to the public?'",
      'is_exposed' => "DEFAULT 1 COMMENT 'Is this menu exposed to the navigation system?'",
      'is_ssl' => "DEFAULT 1 COMMENT 'Should this menu be exposed via SSL if enabled?'",
      'skipBreadcrumb' => "DEFAULT 0 COMMENT 'skip this url being exposed to breadcrumb'",
    ],
    'civicrm_phone' => [
      'is_primary' => "DEFAULT 0 COMMENT 'Is this the primary phone for this contact and location.'",
      'is_billing' => "DEFAULT 0 COMMENT 'Is this the billing?'",
    ],
    'civicrm_state_province' => [
      'is_active' => "DEFAULT 1 COMMENT 'Is this StateProvince active?'",
    ],
    'civicrm_tag' => [
      'is_selectable' => "DEFAULT 1 COMMENT 'Is this tag selectable / displayed'",
      'is_reserved' => "DEFAULT 0",
      'is_tagset' => "DEFAULT 0",
    ],
    'civicrm_openid' => [
      'is_primary' => "DEFAULT 0 COMMENT 'Is this the primary email for this contact and location.'",
    ],
    'civicrm_setting' => [
      'is_domain' => "DEFAULT 0 COMMENT 'Is this setting a contact specific or site wide setting?'",
    ],
    'civicrm_print_label' => [
      'is_default' => "DEFAULT 1 COMMENT 'Is this default?'",
      'is_active' => "DEFAULT 1 COMMENT 'Is this option active?'",
      'is_reserved' => "DEFAULT 1 COMMENT 'Is this reserved label?'",
    ],
    'civicrm_word_replacement' => [
      'is_active' => "DEFAULT 1 COMMENT 'Is this entry active?'",
    ],
    'civicrm_status_pref' => [
      'is_active' => "DEFAULT 1 COMMENT 'Is this status check active?'",
    ],
    'civicrm_group' => [
      'is_active' => "DEFAULT 1 COMMENT 'Is this entry active?'",
      'is_hidden' => "DEFAULT 0 COMMENT 'Is this group hidden?'",
      'is_reserved' => "DEFAULT 0",
    ],
    'civicrm_report_instance' => [
      'is_active' => "DEFAULT 1 COMMENT 'Is this entry active?'",
    ],
    'civicrm_county' => [
      'is_active' => "DEFAULT 1 COMMENT 'Is this County active?'",
    ],
    'civicrm_dashboard' => [
      'is_active' => "DEFAULT 0 COMMENT 'Is this dashlet active?'",
      'is_reserved' => "DEFAULT 0 COMMENT 'Is this dashlet reserved?'",
    ],
    'civicrm_uf_group' => [
      'is_active' => "DEFAULT 1 COMMENT 'Is this form currently active? If false, hide all related fields for all sharing contexts.'",
      'add_captcha' => "DEFAULT 0 COMMENT 'Should a CAPTCHA widget be included this Profile form.'",
      'is_map' => "DEFAULT 0 COMMENT 'Do we want to map results from this profile.'",
      'is_edit_link' => "DEFAULT 0 COMMENT 'Should edit link display in profile selector'",
      'is_uf_link' => "DEFAULT 0 COMMENT 'Should we display a link to the website profile in profile selector'",
      'is_update_dupe' => "DEFAULT 0 COMMENT 'Should we update the contact record if we find a duplicate'",
      'is_cms_user' => "DEFAULT 0 COMMENT 'Should we create a cms user for this profile '",
      'is_reserved' => "DEFAULT 0 COMMENT 'Is this group reserved for use by some other CiviCRM functionality?'",
      'is_proximity_search' => "DEFAULT 0 COMMENT 'Should we include proximity search feature in this profile search form?'",
      'add_cancel_button' => "DEFAULT 1 COMMENT 'Should a Cancel button be included in this Profile form.'",
    ],
    'civicrm_uf_field' => [
      'is_active' => "DEFAULT 1 COMMENT 'Is this field currently shareable? If false, hide the field for all sharing contexts.'",
      'is_view' => "DEFAULT 0 COMMENT 'the field is view only and not editable in user forms.'",
      'is_required' => "DEFAULT 0 COMMENT 'Is this field required when included in a user or registration form?'",
      'in_selector' => "DEFAULT 0 COMMENT 'Is this field included as a column in the selector table?'",
      'is_searchable' => "DEFAULT 0 COMMENT 'Is this field included search form of profile?'",
      'is_reserved' => "DEFAULT 0 COMMENT 'Is this field reserved for use by some other CiviCRM functionality?'",
      'is_multi_summary' => "DEFAULT 0 COMMENT 'Include in multi-record listing?'",
    ],
    'civicrm_uf_join' => [
      'is_active' => "DEFAULT 1 COMMENT 'Is this join currently active?'",
    ],
    'civicrm_action_schedule' => [
      'limit_to' => "DEFAULT 1 COMMENT 'Is this the recipient criteria limited to OR in addition to?'",
      'is_repeat' => "DEFAULT 0",
      'is_active' => "DEFAULT 1 COMMENT 'Is this option active?'",
      'record_activity' => "DEFAULT 0 COMMENT 'Record Activity for this reminder?'",
    ],
    'civicrm_action_log' => [
      'is_error' => "DEFAULT 0 COMMENT 'Was there any error sending the reminder?'",
    ],
    'civicrm_relationship' => [
      'is_active' => "DEFAULT 1 COMMENT 'is the relationship active ?'",
    ],
    'civicrm_relationship_cache' => [
      'is_active' => "DEFAULT 1 COMMENT 'is the relationship active ?'",
    ],
    'civicrm_financial_trxn' => [
      'is_payment' => "DEFAULT 0 COMMENT 'Is this entry either a payment or a reversal of a payment?'",
    ],
    'civicrm_address' => [
      'is_primary' => "DEFAULT 0 COMMENT 'Is this the primary address.'",
      'is_billing' => "DEFAULT 0 COMMENT 'Is this the billing address.'",
      'manual_geo_code' => "DEFAULT 0 COMMENT 'Is this a manually entered geo code'",
    ],
  ];

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_49_alpha1($rev): void {
    $this->addTask('Add civicrm_contact_type.icon column', 'addColumn',
      'civicrm_contact_type', 'icon', "varchar(255) DEFAULT NULL COMMENT 'crm-i icon class representing this contact type'"
    );
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    foreach ($this->booleanColumns as $tableName => $columns) {
      foreach ($columns as $columnName => $defn) {
        $this->addTask("Update $tableName.$columnName to be NOT NULL", 'changeBooleanColumn', $tableName, $columnName, $defn);
      }
    }
    $this->addTask('Add civicrm_option_group.option_value_fields column', 'addColumn',
      'civicrm_option_group', 'option_value_fields', "varchar(128) DEFAULT \"name,label,description\" COMMENT 'Which optional columns from the option_value table are in use by this group.'");
    $this->addTask('Populate civicrm_option_group.option_value_fields column', 'fillOptionValueFields');
  }

  /**
   * Converts a boolean table column to be NOT NULL
   * @param CRM_Queue_TaskContext $ctx
   * @param string $tableName
   * @param string $columnName
   * @param string $defn
   */
  public static function changeBooleanColumn(CRM_Queue_TaskContext $ctx, $tableName, $columnName, $defn) {
    CRM_Core_DAO::executeQuery("UPDATE `$tableName` SET `$columnName` = 0 WHERE `$columnName` IS NULL", [], TRUE, NULL, FALSE, FALSE);
    CRM_Core_DAO::executeQuery("ALTER TABLE `$tableName` CHANGE `$columnName` `$columnName` tinyint NOT NULL $defn", [], TRUE, NULL, FALSE, FALSE);
    return TRUE;
  }

  public static function fillOptionValueFields(CRM_Queue_TaskContext $ctx) {
    // By default every option group uses 'name,description'
    // Note: description doesn't make sense for every group, but historically Civi has been lax
    // about restricting its use.
    CRM_Core_DAO::executeQuery("UPDATE `civicrm_option_group` SET `option_value_fields` = 'name,label,description'", [], TRUE, NULL, FALSE, FALSE);

    $groupsWithDifferentFields = [
      'name,label,description,color' => [
        'activity_status',
        'case_status',
      ],
      'name,label,description,icon' => [
        'activity_type',
      ],
    ];
    foreach ($groupsWithDifferentFields as $fields => $names) {
      $in = '"' . implode('","', $names) . '"';
      CRM_Core_DAO::executeQuery("UPDATE `civicrm_option_group` SET `option_value_fields` = '$fields' WHERE `name` IN ($in)", [], TRUE, NULL, FALSE, FALSE);
    }
    return TRUE;
  }

}
