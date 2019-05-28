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
    $api_params = array(
      'title' => $custom_group_name,
      'extends' => 'Individual',
      'is_active' => TRUE,
    );
    $result = civicrm_api3('customGroup', 'create', $api_params);

    // Add a custom field to the above field group.
    $api_params = array(
      'debug' => 1,
      'custom_group_id' => $result['id'],
      'label' => $custom_group_name,
      'html_type' => 'Select',
      'data_type' => 'String',
      'is_active' => TRUE,
      'option_values' => array(
        array(
          'label' => 'Foo',
          'value' => 'foo',
          'is_active' => 1,
          'weight' => 0,
        ),
      ),
    );
    $result = civicrm_api3('custom_field', 'create', $api_params);
    $customFieldId = $result['id'];

    // Create a Contact Group for testing.
    $group_name = md5(microtime());
    $api_params = array(
      'title' => $group_name,
      'is_active' => TRUE,
    );
    $result = civicrm_api3('group', 'create', $api_params);

    // Create a PaymentProcessor for testing.
    $pp_name = md5(microtime());
    $api_params = array(
      'domain_id' => 1,
      'payment_processor_type_id' => 'Dummy',
      'name' => $pp_name,
      'user_name' => $pp_name,
      'class_name' => 'Payment_Dummy',
      'url_site' => 'https://test.com/',
      'url_recur' => 'https://test.com/',
      'is_active' => 1,
    );
    $result = civicrm_api3('payment_processor', 'create', $api_params);

    // Create a Campaign for testing.
    $campaign_name = md5(microtime());
    $api_params = array(
      'title' => $campaign_name,
      'is_active' => TRUE,
      'status_id' => 2,
    );
    $result = civicrm_api3('campaign', 'create', $api_params);

    // Create a membership type for testing.
    $membership_type = md5(microtime());
    $api_params = array(
      'name' => $membership_type,
      'is_active' => TRUE,
      'financial_type_id' => 1,
      'domain_id' => 1,
      'member_of_contact_id' => 1,
      'duration_unit' => 'day',
      'duration_interval' => 1,
      'period_type' => 'rolling',
    );
    $result = civicrm_api3('membership_type', 'create', $api_params);

    // Create a contribution page for testing.
    $contribution_page = md5(microtime());
    $api_params = array(
      'title' => $contribution_page,
      'is_active' => TRUE,
      'financial_type_id' => 1,
    );
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
    $fields = array(
      'CRM_ACL_DAO_ACL' => array(
        array(
          'fieldName' => 'operation',
          'sample' => 'View',
        ),
      ),
      'CRM_Contact_DAO_Group' => array(
        array(
          'fieldName' => 'visibility',
          'sample' => 'Public Pages',
        ),
      ),
      'CRM_Contact_DAO_GroupContact' => array(
        array(
          'fieldName' => 'group_id',
          'sample' => $group_name,
        ),
        array(
          'fieldName' => 'status',
          'sample' => 'Added',
        ),
      ),
      'CRM_Contact_DAO_GroupContactCache' => array(
        array(
          'fieldName' => 'group_id',
          'sample' => $group_name,
        ),
      ),
      'CRM_Contact_DAO_GroupOrganization' => array(
        array(
          'fieldName' => 'group_id',
          'sample' => $group_name,
        ),
      ),
      'CRM_Contact_DAO_SubscriptionHistory' => array(
        array(
          'fieldName' => 'group_id',
          'sample' => $group_name,
        ),
        array(
          'fieldName' => 'method',
          'sample' => 'Web',
        ),
        array(
          'fieldName' => 'status',
          'sample' => 'Added',
        ),
      ),
      'CRM_Core_DAO_Cache' => array(
        array(
          'fieldName' => 'component_id',
          'sample' => 'CiviMail',
        ),
      ),
      'CRM_Contact_DAO_ACLContactCache' => array(
        array(
          'fieldName' => 'operation',
          'sample' => 'All',
        ),
      ),
      'CRM_Core_DAO_Setting' => array(
        array(
          'fieldName' => 'component_id',
          'sample' => 'CiviMail',
        ),
      ),
      'CRM_Core_DAO_ActionSchedule' => array(
        array(
          'fieldName' => 'group_id',
          'sample' => $group_name,
        ),
        array(
          'fieldName' => 'start_action_unit',
          'sample' => 'hour',
        ),
        array(
          'fieldName' => 'repetition_frequency_unit',
          'sample' => 'hour',
        ),
        array(
          'fieldName' => 'end_frequency_unit',
          'sample' => 'hour',
        ),
        array(
          'fieldName' => 'mode',
          'sample' => 'Email',
        ),
      ),
      'CRM_Dedupe_DAO_RuleGroup' => array(
        array(
          'fieldName' => 'contact_type',
          'sample' => 'Individual',
        ),
        array(
          'fieldName' => 'used',
          'sample' => 'Unsupervised',
        ),
      ),
      'CRM_Activity_DAO_Activity' => array(
        array(
          'fieldName' => 'activity_type_id',
          'sample' => 'Email',
          'max' => 100,
        ),
        array(
          'fieldName' => 'status_id',
          'sample' => 'Scheduled',
        ),
        array(
          'fieldName' => 'priority_id',
          'sample' => 'Urgent',
        ),
        array(
          'fieldName' => 'engagement_level',
          'sample' => '1',
        ),
        array(
          'fieldName' => 'medium_id',
          'sample' => 'Phone',
        ),
        array(
          'fieldName' => 'campaign_id',
          'sample' => $campaign_name,
        ),
      ),
      'CRM_Campaign_DAO_Campaign' => array(
        array(
          'fieldName' => 'campaign_type_id',
          'sample' => 'Constituent Engagement',
          'max' => 50,
        ),
        array(
          'fieldName' => 'status_id',
          'sample' => 'Completed',
          'max' => 50,
        ),
      ),
      'CRM_Campaign_DAO_Survey' => array(
        array(
          'fieldName' => 'campaign_id',
          'sample' => $campaign_name,
        ),
        array(
          'fieldName' => 'activity_type_id',
          'sample' => 'Phone Call',
          'max' => 100,
        ),
      ),
      'CRM_Campaign_DAO_CampaignGroup' => array(
        array(
          'fieldName' => 'campaign_id',
          'sample' => $campaign_name,
        ),
        array(
          'fieldName' => 'group_type',
          'sample' => 'Include',
        ),
      ),
      'CRM_Contact_DAO_RelationshipType' => array(
        array(
          'fieldName' => 'contact_type_a',
          'sample' => 'Individual',
        ),
        array(
          'fieldName' => 'contact_type_b',
          'sample' => 'Organization',
        ),
      ),
      'CRM_Event_DAO_ParticipantStatusType' => array(
        array(
          'fieldName' => 'class',
          'sample' => 'Waiting',
        ),
        array(
          'fieldName' => 'visibility_id',
          'sample' => 'Public',
        ),
      ),
      'CRM_Price_DAO_LineItem' => array(
        array(
          'fieldName' => 'financial_type_id',
          'sample' => 'Donation',
        ),
      ),
      'CRM_Price_DAO_PriceField' => array(
        array(
          'fieldName' => 'html_type',
          'sample' => 'Select',
        ),
        array(
          'fieldName' => 'visibility_id',
          'sample' => 'Public',
        ),
      ),
      'CRM_Price_DAO_PriceFieldValue' => array(
        array(
          'fieldName' => 'financial_type_id',
          'sample' => 'Donation',
        ),
      ),
      'CRM_Price_DAO_PriceSet' => array(
        array(
          'fieldName' => 'domain_id',
          'sample' => 'Default Domain Name',
        ),
        array(
          'fieldName' => 'extends',
          'sample' => 'CiviEvent',
        ),
        array(
          'fieldName' => 'financial_type_id',
          'sample' => 'Donation',
        ),
      ),
      'CRM_Financial_DAO_EntityFinancialAccount' => array(
        array(
          'fieldName' => 'financial_account_id',
          'sample' => 'Member Dues',
        ),
        array(
          'fieldName' => 'account_relationship',
          'sample' => 'Income Account is',
        ),
      ),
      'CRM_Financial_DAO_FinancialItem' => array(
        array(
          'fieldName' => 'status_id',
          'sample' => 'Partially paid',
        ),
        array(
          'fieldName' => 'financial_account_id',
          'sample' => 'Accounts Receivable',
        ),
        array(
          'fieldName' => 'currency',
          'sample' => array('USD' => 'US Dollar'),
          'max' => 200,
        ),
      ),
      'CRM_Financial_DAO_FinancialTrxn' => array(
        array(
          'fieldName' => 'from_financial_account_id',
          'sample' => 'Accounts Receivable',
        ),
        array(
          'fieldName' => 'to_financial_account_id',
          'sample' => 'Accounts Receivable',
        ),
        array(
          'fieldName' => 'currency',
          'sample' => array('USD' => 'US Dollar'),
          'max' => 200,
        ),
        array(
          'fieldName' => 'payment_instrument_id',
          'sample' => 'Check',
        ),
      ),
      'CRM_Financial_DAO_FinancialAccount' => array(
        array(
          'fieldName' => 'financial_account_type_id',
          'sample' => 'Cost of Sales',
        ),
      ),
      'CRM_Financial_DAO_PaymentProcessor' => array(
        array(
          'fieldName' => 'domain_id',
          'sample' => 'Default Domain Name',
        ),
      ),
      'CRM_Financial_BAO_PaymentProcessorType' => array(
        array(
          'fieldName' => 'billing_mode',
          'sample' => 'form',
        ),
      ),
      'CRM_Core_DAO_UFField' => array(
        array(
          'fieldName' => 'uf_group_id',
          'sample' => 'Name and Address',
        ),
        array(
          'fieldName' => 'visibility',
          'sample' => 'Expose Publicly',
        ),
      ),
      'CRM_Core_DAO_UFJoin' => array(
        array(
          'fieldName' => 'uf_group_id',
          'sample' => 'Name and Address',
        ),
      ),
      'CRM_Core_DAO_UFMatch' => array(
        array(
          'fieldName' => 'domain_id',
          'sample' => 'Default Domain Name',
        ),
      ),
      'CRM_Core_DAO_Job' => array(
        array(
          'fieldName' => 'domain_id',
          'sample' => 'Default Domain Name',
        ),
        array(
          'fieldName' => 'run_frequency',
          'sample' => 'Daily',
        ),
      ),
      'CRM_Core_DAO_JobLog' => array(
        array(
          'fieldName' => 'domain_id',
          'sample' => 'Default Domain Name',
        ),
      ),
      'CRM_Contribute_DAO_ContributionSoft' => array(
        array(
          'fieldName' => 'currency',
          'sample' => array('USD' => 'US Dollar'),
          'max' => 200,
        ),
        array(
          'fieldName' => 'soft_credit_type_id',
          'sample' => 'In Honor of',
        ),
      ),
      'CRM_Contribute_DAO_Product' => array(
        array(
          'fieldName' => 'currency',
          'sample' => array('USD' => 'US Dollar'),
          'max' => 200,
        ),
        array(
          'fieldName' => 'financial_type_id',
          'sample' => 'Donation',
        ),
        array(
          'fieldName' => 'period_type',
          'sample' => 'Rolling',
        ),
        array(
          'fieldName' => 'duration_unit',
          'sample' => 'Day',
        ),
        array(
          'fieldName' => 'frequency_unit',
          'sample' => 'Day',
        ),
      ),
      'CRM_Contribute_DAO_ContributionProduct' => array(
        array(
          'fieldName' => 'financial_type_id',
          'sample' => 'Donation',
        ),
      ),
      'CRM_Contribute_DAO_ContributionRecur' => array(
        array(
          'fieldName' => 'currency',
          'sample' => array('USD' => 'US Dollar'),
          'max' => 200,
        ),
        array(
          'fieldName' => 'frequency_unit',
          'sample' => 'month',
        ),
        array(
          'fieldName' => 'contribution_status_id',
          'sample' => 'Completed',
        ),
        array(
          'fieldName' => 'financial_type_id',
          'sample' => 'Donation',
        ),
        array(
          'fieldName' => 'payment_instrument_id',
          'sample' => 'Check',
        ),
        array(
          'fieldName' => 'campaign_id',
          'sample' => $campaign_name,
        ),
      ),
      'CRM_Pledge_DAO_PledgePayment' => array(
        array(
          'fieldName' => 'currency',
          'sample' => array('USD' => 'US Dollar'),
          'max' => 200,
        ),
      ),
      'CRM_Pledge_DAO_Pledge' => array(
        array(
          'fieldName' => 'currency',
          'sample' => array('USD' => 'US Dollar'),
          'max' => 200,
        ),
        array(
          'fieldName' => 'financial_type_id',
          'sample' => 'Donation',
        ),
        array(
          'fieldName' => 'frequency_unit',
          'sample' => 'month',
        ),
        array(
          'fieldName' => 'campaign_id',
          'sample' => $campaign_name,
        ),
      ),
      'CRM_PCP_DAO_PCP' => array(
        array(
          'fieldName' => 'currency',
          'sample' => array('USD' => 'US Dollar'),
          'max' => 200,
        ),
        array(
          'fieldName' => 'status_id',
          'sample' => 'Approved',
        ),
      ),
      'CRM_Core_DAO_CustomField' => array(
        array(
          'fieldName' => 'custom_group_id',
          'sample' => $custom_group_name,
        ),
        array(
          'fieldName' => 'data_type',
          'sample' => 'Alphanumeric',
        ),
        array(
          'fieldName' => 'html_type',
          'sample' => 'Select Date',
        ),
      ),
      'CRM_Core_DAO_CustomGroup' => array(
        array(
          'fieldName' => 'style',
          'sample' => 'Inline',
        ),
      ),
      'CRM_Core_DAO_Dashboard' => array(
        array(
          'fieldName' => 'domain_id',
          'sample' => 'Default Domain Name',
        ),
      ),
      'CRM_Core_DAO_Tag' => array(
        array(
          'fieldName' => 'used_for',
          'sample' => 'Contacts',
        ),
      ),
      'CRM_Core_DAO_EntityTag' => array(
        array(
          'fieldName' => 'tag_id',
          'sample' => 'Government Entity',
        ),
      ),
      'CRM_Core_DAO_Extension' => array(
        array(
          'fieldName' => 'type',
          'sample' => 'Module',
        ),
      ),
      'CRM_Core_DAO_OptionValue' => array(
        array(
          'fieldName' => 'option_group_id',
          'sample' => 'gender',
          'max' => 200,
        ),
        array(
          'fieldName' => 'component_id',
          'sample' => 'CiviContribute',
        ),
        array(
          'fieldName' => 'domain_id',
          'sample' => 'Default Domain Name',
        ),
      ),
      'CRM_Core_DAO_MailSettings' => array(
        array(
          'fieldName' => 'domain_id',
          'sample' => 'Default Domain Name',
        ),
        array(
          'fieldName' => 'protocol',
          'sample' => 'Localdir',
        ),
      ),
      'CRM_Core_DAO_Managed' => array(
        array(
          'fieldName' => 'cleanup',
          'sample' => 'Always',
        ),
      ),
      'CRM_Core_DAO_Mapping' => array(
        array(
          'fieldName' => 'mapping_type_id',
          'sample' => 'Search Builder',
        ),
      ),
      'CRM_Core_DAO_Navigation' => array(
        array(
          'fieldName' => 'domain_id',
          'sample' => 'Default Domain Name',
        ),
      ),
      'CRM_Core_DAO_Phone' => array(
        array(
          'fieldName' => 'phone_type_id',
          'sample' => 'Phone',
        ),
        array(
          'fieldName' => 'location_type_id',
          'sample' => 'Home',
        ),
      ),
      'CRM_Core_DAO_PrintLabel' => array(
        array(
          'fieldName' => 'label_format_name',
          'sample' => 'Avery 5395',
        ),
        array(
          'fieldName' => 'label_type_id',
          'sample' => 'Event Badge',
        ),
      ),
      'CRM_Core_DAO_Email' => array(
        array(
          'fieldName' => 'location_type_id',
          'sample' => 'Home',
        ),
      ),
      'CRM_Core_DAO_Address' => array(
        array(
          'fieldName' => 'location_type_id',
          'sample' => 'Home',
        ),
      ),
      'CRM_Core_DAO_Website' => array(
        array(
          'fieldName' => 'website_type_id',
          'sample' => 'Facebook',
        ),
      ),
      'CRM_Core_DAO_WordReplacement' => array(
        array(
          'fieldName' => 'match_type',
          'sample' => 'Exact Match',
        ),
        array(
          'fieldName' => 'domain_id',
          'sample' => 'Default Domain Name',
        ),
      ),
      'CRM_Core_DAO_MappingField' => array(
        array(
          'fieldName' => 'website_type_id',
          'sample' => 'Facebook',
        ),
        array(
          'fieldName' => 'im_provider_id',
          'sample' => 'Yahoo',
        ),
        array(
          'fieldName' => 'operator',
          'sample' => '=',
        ),
      ),
      'CRM_Contact_DAO_Contact' => array(
        array(
          'fieldName' => 'prefix_id',
          'sample' => 'Mr.',
        ),
        array(
          'fieldName' => 'suffix_id',
          'sample' => 'Sr.',
        ),
        array(
          'fieldName' => 'gender_id',
          'sample' => 'Male',
        ),
        array(
          'fieldName' => 'preferred_communication_method',
          'sample' => 'Postal Mail',
        ),
        array(
          'fieldName' => 'contact_type',
          'sample' => 'Individual',
          'exclude' => 'Team',
        ),
        array(
          'fieldName' => 'contact_sub_type',
          'sample' => 'Team',
          'exclude' => 'Individual',
        ),
        array(
          'fieldName' => 'preferred_language',
          'sample' => array('en_US' => 'English (United States)'),
          'max' => 250,
        ),
        array(
          'fieldName' => 'preferred_mail_format',
          'sample' => 'Text',
        ),
        array(
          'fieldName' => 'communication_style_id',
          'sample' => 'Formal',
        ),
        array(
          'fieldName' => "custom_$customFieldId",
          'sample' => array('foo' => 'Foo'),
          'max' => 1,
        ),
      ),
      'CRM_Batch_DAO_Batch' => array(
        array(
          'fieldName' => 'type_id',
          'sample' => 'Membership',
        ),
        array(
          'fieldName' => 'status_id',
          'sample' => 'Reopened',
        ),
        array(
          'fieldName' => 'mode_id',
          'sample' => 'Automatic Batch',
        ),
        array(
          'fieldName' => 'payment_instrument_id',
          'sample' => 'Check',
        ),
      ),
      'CRM_Core_DAO_IM' => array(
        array(
          'fieldName' => 'provider_id',
          'sample' => 'Yahoo',
        ),
        array(
          'fieldName' => 'location_type_id',
          'sample' => 'Home',
        ),
      ),
      'CRM_Event_DAO_Participant' => array(
        array(
          'fieldName' => 'status_id',
          'sample' => 'Registered',
        ),
        array(
          'fieldName' => 'role_id',
          'sample' => 'Speaker',
        ),
        array(
          'fieldName' => 'fee_currency',
          'sample' => array('USD' => 'US Dollar'),
          'max' => 200,
        ),
        array(
          'fieldName' => 'campaign_id',
          'sample' => $campaign_name,
        ),
      ),
      'CRM_Event_DAO_Event' => array(
        array(
          'fieldName' => 'event_type_id',
          'sample' => 'Fundraiser',
        ),
        array(
          'fieldName' => 'participant_listing_id',
          'sample' => 'Name and Email',
        ),
        array(
          'fieldName' => 'payment_processor',
          'sample' => $pp_name,
        ),
        array(
          'fieldName' => 'financial_type_id',
          'sample' => 'Donation',
        ),
        array(
          'fieldName' => 'default_role_id',
          'sample' => 'Attendee',
        ),
        array(
          'fieldName' => 'currency',
          'sample' => array('USD' => 'US Dollar'),
          'max' => 200,
        ),
        array(
          'fieldName' => 'campaign_id',
          'sample' => $campaign_name,
        ),
      ),
      'CRM_Core_DAO_Menu' => array(
        array(
          'fieldName' => 'domain_id',
          'sample' => 'Default Domain Name',
        ),
        array(
          'fieldName' => 'component_id',
          'sample' => 'CiviMember',
        ),
      ),
      'CRM_Member_DAO_Membership' => array(
        array(
          'fieldName' => 'membership_type_id',
          'sample' => $membership_type,
        ),
        array(
          'fieldName' => 'status_id',
          'sample' => 'New',
        ),
        array(
          'fieldName' => 'campaign_id',
          'sample' => $campaign_name,
        ),
      ),
      'CRM_Member_DAO_MembershipStatus' => array(
        array(
          'fieldName' => 'start_event',
          'sample' => 'start date',
        ),
        array(
          'fieldName' => 'end_event',
          'sample' => 'member since',
        ),
        array(
          'fieldName' => 'start_event_adjust_unit',
          'sample' => 'month',
        ),
        array(
          'fieldName' => 'end_event_adjust_unit',
          'sample' => 'year',
        ),
      ),
      'CRM_Member_DAO_MembershipType' => array(
        array(
          'fieldName' => 'visibility',
          'sample' => 'Public',
        ),
        array(
          'fieldName' => 'domain_id',
          'sample' => 'Default Domain Name',
        ),
        array(
          'fieldName' => 'financial_type_id',
          'sample' => 'Donation',
        ),
        array(
          'fieldName' => 'duration_unit',
          'sample' => 'lifetime',
        ),
        array(
          'fieldName' => 'period_type',
          'sample' => 'Rolling',
        ),
      ),
      'CRM_Mailing_DAO_Mailing' => array(
        array(
          'fieldName' => 'approval_status_id',
          'sample' => 'Approved',
        ),
        array(
          'fieldName' => 'domain_id',
          'sample' => 'Default Domain Name',
        ),
        array(
          'fieldName' => 'visibility',
          'sample' => 'Public Pages',
        ),
        array(
          'fieldName' => 'campaign_id',
          'sample' => $campaign_name,
        ),
      ),
      'CRM_Mailing_DAO_MailingComponent' => array(
        array(
          'fieldName' => 'component_type',
          'sample' => 'Header',
        ),
      ),
      'CRM_Mailing_DAO_MailingGroup' => array(
        array(
          'fieldName' => 'group_type',
          'sample' => 'Include',
        ),
      ),
      'CRM_Mailing_DAO_MailingJob' => array(
        array(
          'fieldName' => 'status',
          'sample' => 'Scheduled',
        ),
      ),
      'CRM_Mailing_Event_DAO_Bounce' => array(
        array(
          'fieldName' => 'bounce_type_id',
          'sample' => 'Invalid',
        ),
      ),
      'CRM_Mailing_Event_DAO_Subscribe' => array(
        array(
          'fieldName' => 'group_id',
          'sample' => $group_name,
        ),
      ),
      'CRM_Grant_DAO_Grant' => array(
        array(
          'fieldName' => 'status_id',
          'sample' => 'Approved for Payment',
        ),
        array(
          'fieldName' => 'grant_type_id',
          'sample' => 'Emergency',
        ),
        array(
          'fieldName' => 'currency',
          'sample' => array('USD' => 'US Dollar'),
          'max' => 200,
        ),
        array(
          'fieldName' => 'financial_type_id',
          'sample' => 'Donation',
        ),
      ),
      'CRM_Contribute_DAO_Contribution' => array(
        array(
          'fieldName' => 'payment_instrument_id',
          'sample' => 'Credit Card',
        ),
        array(
          'fieldName' => 'financial_type_id',
          'sample' => 'Donation',
        ),
        array(
          'fieldName' => 'currency',
          'sample' => array('USD' => 'US Dollar'),
          'max' => 200,
        ),
        array(
          'fieldName' => 'contribution_status_id',
          'sample' => 'Completed',
        ),
        array(
          'fieldName' => 'contribution_page_id',
          'sample' => $contribution_page,
        ),
        array(
          'fieldName' => 'campaign_id',
          'sample' => $campaign_name,
        ),
      ),
      'CRM_Contribute_DAO_PremiumsProduct' => array(
        array(
          'fieldName' => 'financial_type_id',
          'sample' => 'Donation',
        ),
      ),
      'CRM_Contribute_DAO_ContributionPage' => array(
        array(
          'fieldName' => 'payment_processor',
          'sample' => $pp_name,
        ),
        array(
          'fieldName' => 'financial_type_id',
          'sample' => 'Donation',
        ),
        array(
          'fieldName' => 'currency',
          'sample' => array('USD' => 'US Dollar'),
          'max' => 200,
        ),
        array(
          'fieldName' => 'campaign_id',
          'sample' => $campaign_name,
        ),
      ),
      'CRM_Case_DAO_Case' => array(
        array(
          'fieldName' => 'status_id',
          'sample' => 'Ongoing',
        ),
        array(
          'fieldName' => 'case_type_id',
          'sample' => 'Housing Support',
        ),
      ),
      'CRM_Report_DAO_ReportInstance' => array(
        array(
          'fieldName' => 'domain_id',
          'sample' => 'Default Domain Name',
        ),
      ),
    );

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
    $byName = array(
      'Individual' => 'Individual',
      'Household' => 'Household',
      'Organization' => 'Organization',
    );
    $byId = array(
      1 => 'Individual',
      2 => 'Household',
      3 => 'Organization',
    );
    // By default this should return an array keyed by name
    $result = CRM_Contact_DAO_Contact::buildOptions('contact_type');
    $this->assertEquals($byName, $result);
    // But we can also fetch by ID
    $result = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'contact_type', array(
      'keyColumn' => 'id',
      'labelColumn' => 'name',
    ));
    $this->assertEquals($byId, $result);
    // Make sure flip param works
    $result = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'contact_type', array(
      'keyColumn' => 'id',
      'labelColumn' => 'name',
      'flip' => TRUE,
    ));
    $this->assertEquals(array_flip($byId), $result);
  }

  public function testGetTaxRates() {
    $contact = $this->createLoggedInUser();
    $financialType = $this->callAPISuccess('financial_type', 'create', array(
      'name' => 'Test taxable financial Type',
      'is_reserved' => 0,
      'is_active' => 1,
    ));
    $financialAccount = $this->callAPISuccess('financial_account', 'create', array(
      'name' => 'Test Tax financial account ',
      'contact_id' => $contact,
      'financial_account_type_id' => 2,
      'is_tax' => 1,
      'tax_rate' => 5.00,
      'is_reserved' => 0,
      'is_active' => 1,
      'is_default' => 0,
    ));
    $financialTypeId = $financialType['id'];
    $financialAccountId = $financialAccount['id'];
    $financialAccountParams = array(
      'entity_table' => 'civicrm_financial_type',
      'entity_id' => $financialTypeId,
      'account_relationship' => 10,
      'financial_account_id' => $financialAccountId,
    );
    CRM_Financial_BAO_FinancialTypeAccount::add($financialAccountParams);
    $taxRates = CRM_Core_PseudoConstant::getTaxRates();
    $this->assertEquals('5.00', $taxRates[$financialType['id']]);
  }

}
