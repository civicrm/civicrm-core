<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 * Tests for pseudoconstant retrieval
 * @group headless
 */
class CRM_Core_PseudoConstantTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();

    $this->loadAllFixtures();

    CRM_Core_BAO_ConfigSetting::enableComponent('CiviCase');
    CRM_Core_BAO_ConfigSetting::enableComponent('CiviCampaign');
  }

  /**
   * Assure CRM_Core_PseudoConstant::get() is working properly for a range of
   * DAO fields having a <pseudoconstant> tag in the XML schema.
   */
  public function testOptionValues() {

    // Create a custom field group for testing.
    $custom_group_name = md5(microtime());
    $api_params = [
      'title' => $custom_group_name,
      'extends' => 'Individual',
      'is_active' => TRUE,
    ];
    $result = civicrm_api3('customGroup', 'create', $api_params);

    // Add a custom field to the above field group.
    $api_params = [
      'debug' => 1,
      'custom_group_id' => $result['id'],
      'label' => $custom_group_name,
      'html_type' => 'Select',
      'data_type' => 'String',
      'is_active' => TRUE,
      'option_values' => [
        [
          'label' => 'Foo',
          'value' => 'foo',
          'is_active' => 1,
          'weight' => 0,
        ],
      ],
    ];
    $result = civicrm_api3('custom_field', 'create', $api_params);
    $customFieldId = $result['id'];

    // Create a Contact Group for testing.
    $group_name = md5(microtime());
    $api_params = [
      'title' => $group_name,
      'is_active' => TRUE,
    ];
    $result = civicrm_api3('group', 'create', $api_params);

    // Create a PaymentProcessor for testing.
    $pp_name = md5(microtime());
    $api_params = [
      'domain_id' => 1,
      'payment_processor_type_id' => 'Dummy',
      'name' => $pp_name,
      'user_name' => $pp_name,
      'class_name' => 'Payment_Dummy',
      'url_site' => 'https://test.com/',
      'url_recur' => 'https://test.com/',
      'is_active' => 1,
    ];
    $result = civicrm_api3('payment_processor', 'create', $api_params);

    // Create a Campaign for testing.
    $campaign_name = md5(microtime());
    $api_params = [
      'title' => $campaign_name,
      'is_active' => TRUE,
      'status_id' => 2,
    ];
    $result = civicrm_api3('campaign', 'create', $api_params);

    // Create a membership type for testing.
    $membership_type = md5(microtime());
    $api_params = [
      'name' => $membership_type,
      'is_active' => TRUE,
      'financial_type_id' => 1,
      'domain_id' => 1,
      'member_of_contact_id' => 1,
      'duration_unit' => 'day',
      'duration_interval' => 1,
      'period_type' => 'rolling',
    ];
    $result = civicrm_api3('membership_type', 'create', $api_params);

    // Create a contribution page for testing.
    $contribution_page = md5(microtime());
    $api_params = [
      'title' => $contribution_page,
      'is_active' => TRUE,
      'financial_type_id' => 1,
    ];
    $result = civicrm_api3('contribution_page', 'create', $api_params);

    /**
     * daoName/field combinations to test
     * Format: array[DAO Name] = $properties, where properties is an array whose
     * named members can be:
     * - fieldName: the SQL column name within the DAO table.
     * - sample: Any one value which is expected in the list of option values.
     * - exclude: Any one value which should not be in the list.
     * - max: integer (default = 20) maximum number of option values expected.
     */
    $fields = [
      'CRM_ACL_DAO_ACL' => [
        [
          'fieldName' => 'operation',
          'sample' => 'View',
        ],
      ],
      'CRM_Contact_DAO_Group' => [
        [
          'fieldName' => 'visibility',
          'sample' => 'Public Pages',
        ],
      ],
      'CRM_Contact_DAO_GroupContact' => [
        [
          'fieldName' => 'group_id',
          'sample' => $group_name,
        ],
        [
          'fieldName' => 'status',
          'sample' => 'Added',
        ],
      ],
      'CRM_Contact_DAO_GroupContactCache' => [
        [
          'fieldName' => 'group_id',
          'sample' => $group_name,
        ],
      ],
      'CRM_Contact_DAO_GroupOrganization' => [
        [
          'fieldName' => 'group_id',
          'sample' => $group_name,
        ],
      ],
      'CRM_Contact_DAO_SubscriptionHistory' => [
        [
          'fieldName' => 'group_id',
          'sample' => $group_name,
        ],
        [
          'fieldName' => 'method',
          'sample' => 'Web',
        ],
        [
          'fieldName' => 'status',
          'sample' => 'Added',
        ],
      ],
      'CRM_Core_DAO_Cache' => [
        [
          'fieldName' => 'component_id',
          'sample' => 'CiviMail',
        ],
      ],
      'CRM_Contact_DAO_ACLContactCache' => [
        [
          'fieldName' => 'operation',
          'sample' => 'All',
        ],
      ],
      'CRM_Core_DAO_Setting' => [
        [
          'fieldName' => 'component_id',
          'sample' => 'CiviMail',
        ],
      ],
      'CRM_Core_DAO_ActionSchedule' => [
        [
          'fieldName' => 'group_id',
          'sample' => $group_name,
        ],
        [
          'fieldName' => 'start_action_unit',
          'sample' => 'hour',
        ],
        [
          'fieldName' => 'repetition_frequency_unit',
          'sample' => 'hour',
        ],
        [
          'fieldName' => 'end_frequency_unit',
          'sample' => 'hour',
        ],
        [
          'fieldName' => 'mode',
          'sample' => 'Email',
        ],
      ],
      'CRM_Dedupe_DAO_RuleGroup' => [
        [
          'fieldName' => 'contact_type',
          'sample' => 'Individual',
        ],
        [
          'fieldName' => 'used',
          'sample' => 'Unsupervised',
        ],
      ],
      'CRM_Activity_DAO_Activity' => [
        [
          'fieldName' => 'activity_type_id',
          'sample' => 'Email',
          'max' => 100,
        ],
        [
          'fieldName' => 'status_id',
          'sample' => 'Scheduled',
        ],
        [
          'fieldName' => 'priority_id',
          'sample' => 'Urgent',
        ],
        [
          'fieldName' => 'engagement_level',
          'sample' => '1',
        ],
        [
          'fieldName' => 'medium_id',
          'sample' => 'Phone',
        ],
        [
          'fieldName' => 'campaign_id',
          'sample' => $campaign_name,
        ],
      ],
      'CRM_Campaign_DAO_Campaign' => [
        [
          'fieldName' => 'campaign_type_id',
          'sample' => 'Constituent Engagement',
          'max' => 50,
        ],
        [
          'fieldName' => 'status_id',
          'sample' => 'Completed',
          'max' => 50,
        ],
      ],
      'CRM_Campaign_DAO_Survey' => [
        [
          'fieldName' => 'campaign_id',
          'sample' => $campaign_name,
        ],
        [
          'fieldName' => 'activity_type_id',
          'sample' => 'Phone Call',
          'max' => 100,
        ],
      ],
      'CRM_Campaign_DAO_CampaignGroup' => [
        [
          'fieldName' => 'campaign_id',
          'sample' => $campaign_name,
        ],
        [
          'fieldName' => 'group_type',
          'sample' => 'Include',
        ],
      ],
      'CRM_Contact_DAO_RelationshipType' => [
        [
          'fieldName' => 'contact_type_a',
          'sample' => 'Individual',
        ],
        [
          'fieldName' => 'contact_type_b',
          'sample' => 'Organization',
        ],
      ],
      'CRM_Event_DAO_ParticipantStatusType' => [
        [
          'fieldName' => 'class',
          'sample' => 'Waiting',
        ],
        [
          'fieldName' => 'visibility_id',
          'sample' => 'Public',
        ],
      ],
      'CRM_Price_DAO_LineItem' => [
        [
          'fieldName' => 'financial_type_id',
          'sample' => 'Donation',
        ],
      ],
      'CRM_Price_DAO_PriceField' => [
        [
          'fieldName' => 'html_type',
          'sample' => 'Select',
        ],
        [
          'fieldName' => 'visibility_id',
          'sample' => 'Public',
        ],
      ],
      'CRM_Price_DAO_PriceFieldValue' => [
        [
          'fieldName' => 'financial_type_id',
          'sample' => 'Donation',
        ],
      ],
      'CRM_Price_DAO_PriceSet' => [
        [
          'fieldName' => 'domain_id',
          'sample' => 'Default Domain Name',
        ],
        [
          'fieldName' => 'extends',
          'sample' => 'CiviEvent',
        ],
        [
          'fieldName' => 'financial_type_id',
          'sample' => 'Donation',
        ],
      ],
      'CRM_Financial_DAO_EntityFinancialAccount' => [
        [
          'fieldName' => 'financial_account_id',
          'sample' => 'Member Dues',
        ],
        [
          'fieldName' => 'account_relationship',
          'sample' => 'Income Account is',
        ],
      ],
      'CRM_Financial_DAO_FinancialItem' => [
        [
          'fieldName' => 'status_id',
          'sample' => 'Partially paid',
        ],
        [
          'fieldName' => 'financial_account_id',
          'sample' => 'Accounts Receivable',
        ],
        [
          'fieldName' => 'currency',
          'sample' => ['USD' => 'US Dollar'],
          'max' => 200,
        ],
      ],
      'CRM_Financial_DAO_FinancialTrxn' => [
        [
          'fieldName' => 'from_financial_account_id',
          'sample' => 'Accounts Receivable',
        ],
        [
          'fieldName' => 'to_financial_account_id',
          'sample' => 'Accounts Receivable',
        ],
        [
          'fieldName' => 'currency',
          'sample' => ['USD' => 'US Dollar'],
          'max' => 200,
        ],
        [
          'fieldName' => 'payment_instrument_id',
          'sample' => 'Check',
        ],
      ],
      'CRM_Financial_DAO_FinancialAccount' => [
        [
          'fieldName' => 'financial_account_type_id',
          'sample' => 'Cost of Sales',
        ],
      ],
      'CRM_Financial_DAO_PaymentProcessor' => [
        [
          'fieldName' => 'domain_id',
          'sample' => 'Default Domain Name',
        ],
      ],
      'CRM_Financial_BAO_PaymentProcessorType' => [
        [
          'fieldName' => 'billing_mode',
          'sample' => 'form',
        ],
      ],
      'CRM_Core_DAO_UFField' => [
        [
          'fieldName' => 'uf_group_id',
          'sample' => 'Name and Address',
        ],
        [
          'fieldName' => 'visibility',
          'sample' => 'Expose Publicly',
        ],
      ],
      'CRM_Core_DAO_UFJoin' => [
        [
          'fieldName' => 'uf_group_id',
          'sample' => 'Name and Address',
        ],
      ],
      'CRM_Core_DAO_UFMatch' => [
        [
          'fieldName' => 'domain_id',
          'sample' => 'Default Domain Name',
        ],
      ],
      'CRM_Core_DAO_Job' => [
        [
          'fieldName' => 'domain_id',
          'sample' => 'Default Domain Name',
        ],
        [
          'fieldName' => 'run_frequency',
          'sample' => 'Daily',
        ],
      ],
      'CRM_Core_DAO_JobLog' => [
        [
          'fieldName' => 'domain_id',
          'sample' => 'Default Domain Name',
        ],
      ],
      'CRM_Contribute_DAO_ContributionSoft' => [
        [
          'fieldName' => 'currency',
          'sample' => ['USD' => 'US Dollar'],
          'max' => 200,
        ],
        [
          'fieldName' => 'soft_credit_type_id',
          'sample' => 'In Honor of',
        ],
      ],
      'CRM_Contribute_DAO_Product' => [
        [
          'fieldName' => 'currency',
          'sample' => ['USD' => 'US Dollar'],
          'max' => 200,
        ],
        [
          'fieldName' => 'financial_type_id',
          'sample' => 'Donation',
        ],
        [
          'fieldName' => 'period_type',
          'sample' => 'Rolling',
        ],
        [
          'fieldName' => 'duration_unit',
          'sample' => 'Day',
        ],
        [
          'fieldName' => 'frequency_unit',
          'sample' => 'Day',
        ],
      ],
      'CRM_Contribute_DAO_ContributionProduct' => [
        [
          'fieldName' => 'financial_type_id',
          'sample' => 'Donation',
        ],
      ],
      'CRM_Contribute_DAO_ContributionRecur' => [
        [
          'fieldName' => 'currency',
          'sample' => ['USD' => 'US Dollar'],
          'max' => 200,
        ],
        [
          'fieldName' => 'frequency_unit',
          'sample' => 'month',
        ],
        [
          'fieldName' => 'contribution_status_id',
          'sample' => 'Completed',
        ],
        [
          'fieldName' => 'financial_type_id',
          'sample' => 'Donation',
        ],
        [
          'fieldName' => 'payment_instrument_id',
          'sample' => 'Check',
        ],
        [
          'fieldName' => 'campaign_id',
          'sample' => $campaign_name,
        ],
      ],
      'CRM_Pledge_DAO_PledgePayment' => [
        [
          'fieldName' => 'currency',
          'sample' => ['USD' => 'US Dollar'],
          'max' => 200,
        ],
      ],
      'CRM_Pledge_DAO_Pledge' => [
        [
          'fieldName' => 'currency',
          'sample' => ['USD' => 'US Dollar'],
          'max' => 200,
        ],
        [
          'fieldName' => 'financial_type_id',
          'sample' => 'Donation',
        ],
        [
          'fieldName' => 'frequency_unit',
          'sample' => 'month',
        ],
        [
          'fieldName' => 'campaign_id',
          'sample' => $campaign_name,
        ],
      ],
      'CRM_PCP_DAO_PCP' => [
        [
          'fieldName' => 'currency',
          'sample' => ['USD' => 'US Dollar'],
          'max' => 200,
        ],
        [
          'fieldName' => 'status_id',
          'sample' => 'Approved',
        ],
      ],
      'CRM_Core_DAO_CustomField' => [
        [
          'fieldName' => 'custom_group_id',
          'sample' => $custom_group_name,
        ],
        [
          'fieldName' => 'data_type',
          'sample' => 'Alphanumeric',
        ],
        [
          'fieldName' => 'html_type',
          'sample' => 'Select Date',
        ],
      ],
      'CRM_Core_DAO_CustomGroup' => [
        [
          'fieldName' => 'style',
          'sample' => 'Inline',
        ],
      ],
      'CRM_Core_DAO_Dashboard' => [
        [
          'fieldName' => 'domain_id',
          'sample' => 'Default Domain Name',
        ],
      ],
      'CRM_Core_DAO_Tag' => [
        [
          'fieldName' => 'used_for',
          'sample' => 'Contacts',
        ],
      ],
      'CRM_Core_DAO_EntityTag' => [
        [
          'fieldName' => 'tag_id',
          'sample' => 'Government Entity',
        ],
      ],
      'CRM_Core_DAO_Extension' => [
        [
          'fieldName' => 'type',
          'sample' => 'Module',
        ],
      ],
      'CRM_Core_DAO_OptionValue' => [
        [
          'fieldName' => 'option_group_id',
          'sample' => 'gender',
          'max' => 200,
        ],
        [
          'fieldName' => 'component_id',
          'sample' => 'CiviContribute',
        ],
        [
          'fieldName' => 'domain_id',
          'sample' => 'Default Domain Name',
        ],
      ],
      'CRM_Core_DAO_MailSettings' => [
        [
          'fieldName' => 'domain_id',
          'sample' => 'Default Domain Name',
        ],
        [
          'fieldName' => 'protocol',
          'sample' => 'Localdir',
        ],
      ],
      'CRM_Core_DAO_Managed' => [
        [
          'fieldName' => 'cleanup',
          'sample' => 'Always',
        ],
      ],
      'CRM_Core_DAO_Mapping' => [
        [
          'fieldName' => 'mapping_type_id',
          'sample' => 'Search Builder',
        ],
      ],
      'CRM_Core_DAO_Navigation' => [
        [
          'fieldName' => 'domain_id',
          'sample' => 'Default Domain Name',
        ],
      ],
      'CRM_Core_DAO_Phone' => [
        [
          'fieldName' => 'phone_type_id',
          'sample' => 'Phone',
        ],
        [
          'fieldName' => 'location_type_id',
          'sample' => 'Home',
        ],
      ],
      'CRM_Core_DAO_PrintLabel' => [
        [
          'fieldName' => 'label_format_name',
          'sample' => 'Avery 5395',
        ],
        [
          'fieldName' => 'label_type_id',
          'sample' => 'Event Badge',
        ],
      ],
      'CRM_Core_DAO_Email' => [
        [
          'fieldName' => 'location_type_id',
          'sample' => 'Home',
        ],
      ],
      'CRM_Core_DAO_Address' => [
        [
          'fieldName' => 'location_type_id',
          'sample' => 'Home',
        ],
      ],
      'CRM_Core_DAO_Website' => [
        [
          'fieldName' => 'website_type_id',
          'sample' => 'Facebook',
        ],
      ],
      'CRM_Core_DAO_WordReplacement' => [
        [
          'fieldName' => 'match_type',
          'sample' => 'Exact Match',
        ],
        [
          'fieldName' => 'domain_id',
          'sample' => 'Default Domain Name',
        ],
      ],
      'CRM_Core_DAO_MappingField' => [
        [
          'fieldName' => 'website_type_id',
          'sample' => 'Facebook',
        ],
        [
          'fieldName' => 'im_provider_id',
          'sample' => 'Yahoo',
        ],
        [
          'fieldName' => 'operator',
          'sample' => '=',
        ],
      ],
      'CRM_Contact_DAO_Contact' => [
        [
          'fieldName' => 'prefix_id',
          'sample' => 'Mr.',
        ],
        [
          'fieldName' => 'suffix_id',
          'sample' => 'Sr.',
        ],
        [
          'fieldName' => 'gender_id',
          'sample' => 'Male',
        ],
        [
          'fieldName' => 'preferred_communication_method',
          'sample' => 'Postal Mail',
        ],
        [
          'fieldName' => 'contact_type',
          'sample' => 'Individual',
          'exclude' => 'Team',
        ],
        [
          'fieldName' => 'contact_sub_type',
          'sample' => 'Team',
          'exclude' => 'Individual',
        ],
        [
          'fieldName' => 'preferred_language',
          'sample' => ['en_US' => 'English (United States)'],
          'max' => 250,
        ],
        [
          'fieldName' => 'preferred_mail_format',
          'sample' => 'Text',
        ],
        [
          'fieldName' => 'communication_style_id',
          'sample' => 'Formal',
        ],
        [
          'fieldName' => "custom_$customFieldId",
          'sample' => ['foo' => 'Foo'],
          'max' => 1,
        ],
      ],
      'CRM_Batch_DAO_Batch' => [
        [
          'fieldName' => 'type_id',
          'sample' => 'Membership',
        ],
        [
          'fieldName' => 'status_id',
          'sample' => 'Reopened',
        ],
        [
          'fieldName' => 'mode_id',
          'sample' => 'Automatic Batch',
        ],
        [
          'fieldName' => 'payment_instrument_id',
          'sample' => 'Check',
        ],
      ],
      'CRM_Core_DAO_IM' => [
        [
          'fieldName' => 'provider_id',
          'sample' => 'Yahoo',
        ],
        [
          'fieldName' => 'location_type_id',
          'sample' => 'Home',
        ],
      ],
      'CRM_Event_DAO_Participant' => [
        [
          'fieldName' => 'status_id',
          'sample' => 'Registered',
        ],
        [
          'fieldName' => 'role_id',
          'sample' => 'Speaker',
        ],
        [
          'fieldName' => 'fee_currency',
          'sample' => ['USD' => 'US Dollar'],
          'max' => 200,
        ],
        [
          'fieldName' => 'campaign_id',
          'sample' => $campaign_name,
        ],
      ],
      'CRM_Event_DAO_Event' => [
        [
          'fieldName' => 'event_type_id',
          'sample' => 'Fundraiser',
        ],
        [
          'fieldName' => 'participant_listing_id',
          'sample' => 'Name and Email',
        ],
        [
          'fieldName' => 'payment_processor',
          'sample' => $pp_name,
        ],
        [
          'fieldName' => 'financial_type_id',
          'sample' => 'Donation',
        ],
        [
          'fieldName' => 'default_role_id',
          'sample' => 'Attendee',
        ],
        [
          'fieldName' => 'currency',
          'sample' => ['USD' => 'US Dollar'],
          'max' => 200,
        ],
        [
          'fieldName' => 'campaign_id',
          'sample' => $campaign_name,
        ],
      ],
      'CRM_Core_DAO_Menu' => [
        [
          'fieldName' => 'domain_id',
          'sample' => 'Default Domain Name',
        ],
        [
          'fieldName' => 'component_id',
          'sample' => 'CiviMember',
        ],
      ],
      'CRM_Member_DAO_Membership' => [
        [
          'fieldName' => 'membership_type_id',
          'sample' => $membership_type,
        ],
        [
          'fieldName' => 'status_id',
          'sample' => 'New',
        ],
        [
          'fieldName' => 'campaign_id',
          'sample' => $campaign_name,
        ],
      ],
      'CRM_Member_DAO_MembershipStatus' => [
        [
          'fieldName' => 'start_event',
          'sample' => 'start date',
        ],
        [
          'fieldName' => 'end_event',
          'sample' => 'member since',
        ],
        [
          'fieldName' => 'start_event_adjust_unit',
          'sample' => 'month',
        ],
        [
          'fieldName' => 'end_event_adjust_unit',
          'sample' => 'year',
        ],
      ],
      'CRM_Member_DAO_MembershipType' => [
        [
          'fieldName' => 'visibility',
          'sample' => 'Public',
        ],
        [
          'fieldName' => 'domain_id',
          'sample' => 'Default Domain Name',
        ],
        [
          'fieldName' => 'financial_type_id',
          'sample' => 'Donation',
        ],
        [
          'fieldName' => 'duration_unit',
          'sample' => 'lifetime',
        ],
        [
          'fieldName' => 'period_type',
          'sample' => 'Rolling',
        ],
      ],
      'CRM_Mailing_DAO_Mailing' => [
        [
          'fieldName' => 'approval_status_id',
          'sample' => 'Approved',
        ],
        [
          'fieldName' => 'domain_id',
          'sample' => 'Default Domain Name',
        ],
        [
          'fieldName' => 'visibility',
          'sample' => 'Public Pages',
        ],
        [
          'fieldName' => 'campaign_id',
          'sample' => $campaign_name,
        ],
      ],
      'CRM_Mailing_DAO_MailingComponent' => [
        [
          'fieldName' => 'component_type',
          'sample' => 'Header',
        ],
      ],
      'CRM_Mailing_DAO_MailingGroup' => [
        [
          'fieldName' => 'group_type',
          'sample' => 'Include',
        ],
      ],
      'CRM_Mailing_DAO_MailingJob' => [
        [
          'fieldName' => 'status',
          'sample' => 'Scheduled',
        ],
      ],
      'CRM_Mailing_Event_DAO_Bounce' => [
        [
          'fieldName' => 'bounce_type_id',
          'sample' => 'Invalid',
        ],
      ],
      'CRM_Mailing_Event_DAO_Subscribe' => [
        [
          'fieldName' => 'group_id',
          'sample' => $group_name,
        ],
      ],
      'CRM_Grant_DAO_Grant' => [
        [
          'fieldName' => 'status_id',
          'sample' => 'Approved for Payment',
        ],
        [
          'fieldName' => 'grant_type_id',
          'sample' => 'Emergency',
        ],
        [
          'fieldName' => 'currency',
          'sample' => ['USD' => 'US Dollar'],
          'max' => 200,
        ],
        [
          'fieldName' => 'financial_type_id',
          'sample' => 'Donation',
        ],
      ],
      'CRM_Contribute_DAO_Contribution' => [
        [
          'fieldName' => 'payment_instrument_id',
          'sample' => 'Credit Card',
        ],
        [
          'fieldName' => 'financial_type_id',
          'sample' => 'Donation',
        ],
        [
          'fieldName' => 'currency',
          'sample' => ['USD' => 'US Dollar'],
          'max' => 200,
        ],
        [
          'fieldName' => 'contribution_status_id',
          'sample' => 'Completed',
        ],
        [
          'fieldName' => 'contribution_page_id',
          'sample' => $contribution_page,
        ],
        [
          'fieldName' => 'campaign_id',
          'sample' => $campaign_name,
        ],
      ],
      'CRM_Contribute_DAO_PremiumsProduct' => [
        [
          'fieldName' => 'financial_type_id',
          'sample' => 'Donation',
        ],
      ],
      'CRM_Contribute_DAO_ContributionPage' => [
        [
          'fieldName' => 'payment_processor',
          'sample' => $pp_name,
        ],
        [
          'fieldName' => 'financial_type_id',
          'sample' => 'Donation',
        ],
        [
          'fieldName' => 'currency',
          'sample' => ['USD' => 'US Dollar'],
          'max' => 200,
        ],
        [
          'fieldName' => 'campaign_id',
          'sample' => $campaign_name,
        ],
      ],
      'CRM_Case_DAO_Case' => [
        [
          'fieldName' => 'status_id',
          'sample' => 'Ongoing',
        ],
        [
          'fieldName' => 'case_type_id',
          'sample' => 'Housing Support',
        ],
      ],
      'CRM_Report_DAO_ReportInstance' => [
        [
          'fieldName' => 'domain_id',
          'sample' => 'Default Domain Name',
        ],
      ],
    ];

    foreach ($fields as $daoName => $daoFields) {
      foreach ($daoFields as $field) {
        $message = "DAO name: '{$daoName}', field: '{$field['fieldName']}'";

        $optionValues = $daoName::buildOptions($field['fieldName']);
        $this->assertNotEmpty($optionValues, $message);

        // Ensure sample value is contained in the returned optionValues.
        if (!is_array($field['sample'])) {
          $this->assertContains($field['sample'], $optionValues, $message);
        }
        // If sample is an array, we check keys and values
        else {
          foreach ($field['sample'] as $key => $value) {
            $this->assertArrayHasKey($key, $optionValues, $message);
            $this->assertEquals(CRM_Utils_Array::value($key, $optionValues), $value, $message);
          }
        }

        // Ensure exclude value is not contained in the optionValues
        if (!empty($field['exclude'])) {
          $this->assertNotContains($field['exclude'], $optionValues, $message);
        }

        // Ensure count of optionValues is not extraordinarily high.
        $max = CRM_Utils_Array::value('max', $field, 20);
        $this->assertLessThanOrEqual($max, count($optionValues), $message);
      }
    }
  }

  public function testContactTypes() {
    $byName = [
      'Individual' => 'Individual',
      'Household' => 'Household',
      'Organization' => 'Organization',
    ];
    $byId = [
      1 => 'Individual',
      2 => 'Household',
      3 => 'Organization',
    ];
    // By default this should return an array keyed by name
    $result = CRM_Contact_DAO_Contact::buildOptions('contact_type');
    $this->assertEquals($byName, $result);
    // But we can also fetch by ID
    $result = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'contact_type', [
      'keyColumn' => 'id',
      'labelColumn' => 'name',
    ]);
    $this->assertEquals($byId, $result);
    // Make sure flip param works
    $result = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'contact_type', [
      'keyColumn' => 'id',
      'labelColumn' => 'name',
      'flip' => TRUE,
    ]);
    $this->assertEquals(array_flip($byId), $result);
  }

  public function testGetTaxRates() {
    $contact = $this->createLoggedInUser();
    $financialType = $this->callAPISuccess('financial_type', 'create', [
      'name' => 'Test taxable financial Type',
      'is_reserved' => 0,
      'is_active' => 1,
    ]);
    $financialAccount = $this->callAPISuccess('financial_account', 'create', [
      'name' => 'Test Tax financial account ',
      'contact_id' => $contact,
      'financial_account_type_id' => 2,
      'is_tax' => 1,
      'tax_rate' => 5.00,
      'is_reserved' => 0,
      'is_active' => 1,
      'is_default' => 0,
    ]);
    $financialTypeId = $financialType['id'];
    $financialAccountId = $financialAccount['id'];
    $financialAccountParams = [
      'entity_table' => 'civicrm_financial_type',
      'entity_id' => $financialTypeId,
      'account_relationship' => 10,
      'financial_account_id' => $financialAccountId,
    ];
    CRM_Financial_BAO_FinancialTypeAccount::add($financialAccountParams);
    $taxRates = CRM_Core_PseudoConstant::getTaxRates();
    $this->assertEquals('5.00', $taxRates[$financialType['id']]);
  }

}
