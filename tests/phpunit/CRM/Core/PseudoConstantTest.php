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

    $custom_group_name = md5(microtime());
    $api_params = array(
      'version' => 3,
      'title' => $custom_group_name,
      'extends' => 'Individual',
      'is_active' => TRUE,
    );
    $result = civicrm_api('customGroup', 'create', $api_params);

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
      'CRM_Activity_DAO_Activity' => array(
        array(
          'fieldName' => 'activity_type_id',
          'sample' => 'Text Message (SMS)',
          'max' => 50,
        ),
        array(
          'fieldName' => 'status_id',
          'sample' => 'Scheduled',
        ),
      ),
      'CRM_Campaign_DAO_Survey' => array(
        array(
          'fieldName' => 'activity_type_id',
          'sample' => 'Text Message (SMS)',
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
      ),
      'CRM_Financial_DAO_FinancialAccount' => array(
        array(
          'fieldName' => 'financial_account_type_id',
          'sample' => 'Cost of Sales',
        ),
      ),
      'CRM_Event_DAO_Participant' => array(
        array(
          'fieldName' => 'fee_currency',
          'sample' => '$',
          'max' => 200,
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
          'sample' => '$',
          'max' => 200,
        ),
      ),
      'CRM_Contribute_DAO_Contribution' => array(
        array(
          'fieldName' => 'currency',
          'sample' => '$',
          'max' => 200,
        ),
      ),
      'CRM_Contribute_DAO_Product' => array(
        array(
          'fieldName' => 'currency',
          'sample' => '$',
          'max' => 200,
        ),
      ),
      'CRM_Contribute_DAO_ContributionPage' => array(
        array(
          'fieldName' => 'currency',
          'sample' => '$',
          'max' => 200,
        ),
      ),
      'CRM_Contribute_DAO_ContributionRecur' => array(
        array(
          'fieldName' => 'currency',
          'sample' => '$',
          'max' => 200,
        ),
      ),
      'CRM_Event_DAO_Event' => array(
        array(
          'fieldName' => 'currency',
          'sample' => '$',
          'max' => 200,
        ),
      ),
      'CRM_Financial_DAO_FinancialItem' => array(
        array(
          'fieldName' => 'currency',
          'sample' => '$',
          'max' => 200,
        ),
      ),
      'CRM_Financial_DAO_OfficialReceipt' => array(
        array(
          'fieldName' => 'currency',
          'sample' => '$',
          'max' => 200,
        ),
      ),
      'CRM_Financial_DAO_FinancialTrxn' => array(
        array(
          'fieldName' => 'currency',
          'sample' => '$',
          'max' => 200,
        ),
      ),
      'CRM_Grant_DAO_Grant' => array(
        array(
          'fieldName' => 'currency',
          'sample' => '$',
          'max' => 200,
        ),
      ),
      'CRM_Pledge_DAO_PledgePayment' => array(
        array(
          'fieldName' => 'currency',
          'sample' => '$',
          'max' => 200,
        ),
      ),
      'CRM_Pledge_DAO_Pledge' => array(
        array(
          'fieldName' => 'currency',
          'sample' => '$',
          'max' => 200,
        ),
      ),
      'CRM_PCP_DAO_PCP' => array(
        array(
          'fieldName' => 'currency',
          'sample' => '$',
          'max' => 200,
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
      'CRM_Project_DAO_Task' => array(
        array(
          'fieldName' => 'priority_id',
          'sample' => 'Urgent',
        ),
      ),
      'CRM_Activity_DAO_Activity' => array(
        array(
          'fieldName' => 'priority_id',
          'sample' => 'Urgent',
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
      'CRM_Contribute_DAO_Contribution' => array(
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
      ),
      'CRM_Event_DAO_Event' => array(
        array(
          'fieldName' => 'event_type_id',
          'sample' => 'Fundraiser',
        ),
      ),
      'CRM_PCP_DAO_PCP' => array(
        array(
          'fieldName' => 'status_id',
          'sample' => 'Approved',
        ),
      ),
    );

    foreach ($fields as $daoName => $daoFields) {
      foreach ($daoFields as $field) {
        $message = "DAO name: '{$daoName}', field: '{$field['fieldName']}'";

        $optionValues = CRM_Core_PseudoConstant::get($daoName, $field['fieldName']);
        $this->assertNotEmpty($optionValues, $message);

        // Ensure sample value is contained in the returned optionValues.
        $this->assertContains($field['sample'], $optionValues, $message);

        // Exclude test
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
    $result = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'contact_type');
    $this->assertEquals($byName, $result);
    // But we can also fetch by ID
    $result = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'contact_type', array('keyColumn' => 'id', 'labelColumn' => 'name'));
    $this->assertEquals($byId, $result);
    // Make sure flip param works
    $result = CRM_Core_PseudoConstant::get('CRM_Contact_DAO_Contact', 'contact_type', array('keyColumn' => 'id', 'labelColumn' => 'name', 'flip' => TRUE));
    $this->assertEquals(array_flip($byId), $result);
  }
}
