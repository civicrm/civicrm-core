<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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

require_once 'CiviTest/CiviUnitTestCase.php';

/**
 * Tests for pseudoconstant retrieval
 */
class CRM_Core_PseudoConstantTest extends CiviUnitTestCase {
  function get_info() {
    return array(
      'name'    => 'PseudoConstant',
      'description' => 'Tests for pseudoconstant option values',
      'group'     => 'Core',
    );
  }

  function setUp() {
    parent::setUp();
  }

  /**
   * Assure CRM_Core_PseudoConstant::get() is working properly for a range of
   * DAO fields having a <pseudoconstant> tag in the XML schema.
   */
  function testOptionValues() {

    // Create a custom field group for testing.
    $custom_group_name = md5(microtime());
    $api_params = array(
      'version' => 3,
      'title' => $custom_group_name,
      'extends' => 'Individual',
      'is_active' => TRUE,
    );
    $result = civicrm_api('customGroup', 'create', $api_params);
    $this->assertAPISuccess($result);

    // Add a custom field to the above field group.
    $api_params = array(
      'version' => 3,
      'debug' => 1,
      'custom_group_id' => $result['id'],
      'label' => $custom_group_name,
      'html_type' => 'Select',
      'data_type' => 'String',
      'is_active' => TRUE,
      'option_values' => array(array(
        'label' => 'Foo',
        'value' => 'foo',
        'is_active' => 1,
        'weight' => 0,
      )),
    );
    $result = civicrm_api('custom_field', 'create', $api_params);
    $this->assertAPISuccess($result);
    $customFieldId = $result['id'];

    // Create a Contact Group for testing.
    $group_name = md5(microtime());
    $api_params = array(
      'version' => 3,
      'title' => $group_name,
      'is_active' => TRUE,
    );
    $result = civicrm_api('group', 'create', $api_params);
    $this->assertAPISuccess($result);

    // Create a PaymentProcessor for testing.
    $pp_name = md5(microtime());
    $api_params = array(
      'version' => 3,
      'domain_id' => 1,
      'payment_processor_type_id' => 10,
      'name' => $pp_name,
      'user_name' => $pp_name,
      'class_name' => 'Payment_Dummy',
      'url_site' => 'https://test.com/',
      'url_recur' => 'https://test.com/',
      'is_active' => 1,
    );
    $result = civicrm_api('payment_processor', 'create', $api_params);
    $this->assertAPISuccess($result);

    /**
     * daoName/field combinations to test
     * Format: array[DAO Name] = $properties, where properties is an array whose
     * named members can be:
     * - fieldName: the SQL column name within the DAO table.
     * - sample: Any one value which is expected in the list of option values.
     * - exclude: Any one value which should not be in the list.
     * - max: integer (default = 10) maximum number of option values expected.
     */
    $fields = array(
      'CRM_Contact_DAO_GroupContact' => array(
        array(
          'fieldName' => 'group_id',
          'sample' => $group_name,
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
      ),
      'CRM_Core_DAO_ActionSchedule' => array(
        array(
          'fieldName' => 'group_id',
          'sample' => $group_name,
        ),
      ),
      'CRM_Mailing_Event_DAO_Subscribe' => array(
        array(
          'fieldName' => 'group_id',
          'sample' => $group_name,
        ),
      ),
      'CRM_Activity_DAO_Activity' => array(
        array(
          'fieldName' => 'activity_type_id',
          'sample' => 'Email',
          'max' => 50,
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
      ),
      'CRM_Campaign_DAO_Campaign' => array(
        array(
          'fieldName' => 'status_id',
          'sample' => 'Completed',
          'max' => 50,
        ),
      ),
      'CRM_Campaign_DAO_Survey' => array(
        array(
          'fieldName' => 'activity_type_id',
          'sample' => 'Phone Call',
          'max' => 50,
        ),
      ),
      'CRM_Event_DAO_ParticipantStatusType' => array(
        array(
          'fieldName' => 'visibility_id',
          'sample' => 'Public',
        ),
      ),
      'CRM_Member_DAO_MembershipType' => array(
        array(
          'fieldName' => 'visibility',
          'sample' => 'Public',
        ),
      ),
      'CRM_Price_DAO_Field' => array(
        array(
          'fieldName' => 'visibility_id',
          'sample' => 'Public',
        ),
      ),
      'CRM_Financial_DAO_EntityFinancialAccount' => array(
        array(
          'fieldName' => 'financial_account_id',
          'sample' => 'Member Dues',
          'max' => 15,
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
          'max' => 15,
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
          'max' => 15,
        ),
        array(
          'fieldName' => 'to_financial_account_id',
          'sample' => 'Accounts Receivable',
          'max' => 15,
        ),
        array(
          'fieldName' => 'currency',
          'sample' => array('USD' => 'US Dollar'),
          'max' => 200,
        ),
      ),
      'CRM_Financial_DAO_FinancialAccount' => array(
        array(
          'fieldName' => 'financial_account_type_id',
          'sample' => 'Cost of Sales',
        ),
      ),
      'CRM_Core_DAO_UFField' => array(
        array(
          'fieldName' => 'uf_group_id',
          'sample' => 'Name and Address',
          'max' => 15,
        ),
      ),
      'CRM_Core_DAO_UFJoin' => array(
        array(
          'fieldName' => 'uf_group_id',
          'sample' => 'Name and Address',
          'max' => 15,
        ),
      ),
      'CRM_Contribute_DAO_ContributionSoft' => array(
        array(
          'fieldName' => 'currency',
          'sample' => array('USD' => 'US Dollar'),
          'max' => 200,
        ),
      ),
      'CRM_Contribute_DAO_Product' => array(
        array(
          'fieldName' => 'currency',
          'sample' => array('USD' => 'US Dollar'),
          'max' => 200,
        ),
      ),
      'CRM_Contribute_DAO_ContributionRecur' => array(
        array(
          'fieldName' => 'currency',
          'sample' => array('USD' => 'US Dollar'),
          'max' => 200,
        ),
      ),
      'CRM_Financial_DAO_OfficialReceipt' => array(
        array(
          'fieldName' => 'currency',
          'sample' => array('USD' => 'US Dollar'),
          'max' => 200,
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
      ),
      'CRM_Core_DAO_EntityTag' => array(
        array(
          'fieldName' => 'tag_id',
          'sample' => 'Government Entity',
        ),
      ),
      'CRM_Core_DAO_OptionValue' => array(
        array(
          'fieldName' => 'component_id',
          'sample' => 'CiviContribute',
        ),
      ),
      'CRM_Core_DAO_MailSettings' => array(
        array(
          'fieldName' => 'protocol',
          'sample' => 'Localdir',
        ),
      ),
      'CRM_Core_DAO_Mapping' => array(
        array(
          'fieldName' => 'mapping_type_id',
          'sample' => 'Search Builder',
          'max' => 15,
        ),
      ),
      'CRM_Pledge_DAO_Pledge' => array(
        array(
          'fieldName' => 'honor_type_id',
          'sample' => 'In Honor of',
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
      'CRM_Core_DAO_MappingField' => array(
        array(
          'fieldName' => 'website_type_id',
          'sample' => 'Facebook',
        ),
        array(
          'fieldName' => 'im_provider_id',
          'sample' => 'Yahoo',
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
      ),
      'CRM_Core_DAO_IM' => array(
        array(
          'fieldName' => 'provider_id',
          'sample' => 'Yahoo',
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
      ),
      'CRM_Event_DAO_Event' => array(
        array(
          'fieldName' => 'event_type_id',
          'sample' => 'Fundraiser',
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
          'fieldName' => 'currency',
          'sample' => array('USD' => 'US Dollar'),
          'max' => 200,
        ),
      ),
      'CRM_Member_DAO_Membership' => array(
        array(
          'fieldName' => 'status_id',
          'sample' => 'New',
        ),
      ),
      'CRM_Mailing_DAO_Mailing' => array(
        array(
          'fieldName' => 'approval_status_id',
          'sample' => 'Approved',
        ),
      ),
      'CRM_Grant_DAO_Grant' => array(
        array(
          'fieldName' => 'status_id',
          'sample' => 'Approved',
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
          'fieldName' => 'honor_type_id',
          'sample' => 'In Honor of',
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
      ),
      'CRM_Case_DAO_Case' => array(
        array(
          'fieldName' => 'status_id',
          'sample' => 'Ongoing',
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
        $max = CRM_Utils_Array::value('max', $field, 10);
        $this->assertLessThanOrEqual($max, count($optionValues), $message);
      }
    }
  }

  function testContactTypes() {
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
    $result = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'contact_type', array('keyColumn' => 'id', 'labelColumn' => 'name'));
    $this->assertEquals($byId, $result);
    // Make sure flip param works
    $result = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'contact_type', array('keyColumn' => 'id', 'labelColumn' => 'name', 'flip' => TRUE));
    $this->assertEquals(array_flip($byId), $result);
  }
}
