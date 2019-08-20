<?php
/**
 * Test Generated example demonstrating the Contact.get API.
 *
 * This demonstrates the usage of chained api functions.
 * In this case no notes or custom fields have been created.
 *
 * @return array
 *   API result array
 */
function contact_get_example() {
  $params = [
    'id' => 3,
    'api.website.get' => [],
    'api.Contribution.get' => [
      'total_amount' => '120.00',
    ],
    'api.CustomValue.get' => 1,
    'api.Note.get' => 1,
  ];

  try{
    $result = civicrm_api3('Contact', 'get', $params);
  }
  catch (CiviCRM_API3_Exception $e) {
    // Handle error here.
    $errorMessage = $e->getMessage();
    $errorCode = $e->getErrorCode();
    $errorData = $e->getExtraParams();
    return [
      'is_error' => 1,
      'error_message' => $errorMessage,
      'error_code' => $errorCode,
      'error_data' => $errorData,
    ];
  }

  return $result;
}

/**
 * Function returns array of result expected from previous function.
 *
 * @return array
 *   API result array
 */
function contact_get_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 3,
    'values' => [
      '3' => [
        'contact_id' => '3',
        'contact_type' => 'Individual',
        'contact_sub_type' => '',
        'sort_name' => 'xyz3, abc3',
        'display_name' => 'abc3 xyz3',
        'do_not_email' => 0,
        'do_not_phone' => 0,
        'do_not_mail' => 0,
        'do_not_sms' => 0,
        'do_not_trade' => 0,
        'is_opt_out' => 0,
        'legal_identifier' => '',
        'external_identifier' => '',
        'nick_name' => '',
        'legal_name' => '',
        'image_URL' => '',
        'preferred_communication_method' => '',
        'preferred_language' => 'en_US',
        'preferred_mail_format' => 'Both',
        'first_name' => 'abc3',
        'middle_name' => '',
        'last_name' => 'xyz3',
        'prefix_id' => '',
        'suffix_id' => '',
        'formal_title' => '',
        'communication_style_id' => '1',
        'job_title' => '',
        'gender_id' => '',
        'birth_date' => '',
        'is_deceased' => 0,
        'deceased_date' => '',
        'household_name' => '',
        'organization_name' => '',
        'sic_code' => '',
        'contact_is_deleted' => 0,
        'current_employer' => '',
        'address_id' => '',
        'street_address' => '',
        'supplemental_address_1' => '',
        'supplemental_address_2' => '',
        'supplemental_address_3' => '',
        'city' => '',
        'postal_code_suffix' => '',
        'postal_code' => '',
        'geo_code_1' => '',
        'geo_code_2' => '',
        'state_province_id' => '',
        'country_id' => '',
        'phone_id' => '',
        'phone_type_id' => '',
        'phone' => '',
        'email_id' => '1',
        'email' => 'man3@yahoo.com',
        'on_hold' => 0,
        'im_id' => '',
        'provider_id' => '',
        'im' => '',
        'worldregion_id' => '',
        'world_region' => '',
        'languages' => 'English (United States)',
        'individual_prefix' => '',
        'individual_suffix' => '',
        'communication_style' => 'Formal',
        'gender' => '',
        'state_province_name' => '',
        'state_province' => '',
        'country' => '',
        'id' => '3',
        'api.website.get' => [
          'is_error' => 0,
          'version' => 3,
          'count' => 1,
          'id' => 1,
          'values' => [
            '0' => [
              'id' => '1',
              'contact_id' => '3',
              'url' => 'http://civicrm.org',
            ],
          ],
        ],
        'api.Contribution.get' => [
          'is_error' => 0,
          'version' => 3,
          'count' => 1,
          'id' => 2,
          'values' => [
            '0' => [
              'contact_id' => '3',
              'contact_type' => 'Individual',
              'contact_sub_type' => '',
              'sort_name' => 'xyz3, abc3',
              'display_name' => 'abc3 xyz3',
              'contribution_id' => '2',
              'currency' => 'USD',
              'contribution_recur_id' => '',
              'contribution_status_id' => '1',
              'contribution_campaign_id' => '',
              'payment_instrument_id' => '1',
              'receive_date' => '2011-01-01 00:00:00',
              'non_deductible_amount' => '10.00',
              'total_amount' => '120.00',
              'fee_amount' => '50.00',
              'net_amount' => '90.00',
              'trxn_id' => '12335',
              'invoice_id' => '67830',
              'invoice_number' => '',
              'contribution_cancel_date' => '',
              'cancel_reason' => '',
              'receipt_date' => '',
              'thankyou_date' => '',
              'contribution_source' => 'SSF',
              'amount_level' => '',
              'is_test' => 0,
              'is_pay_later' => 0,
              'contribution_check_number' => '',
              'financial_account_id' => '1',
              'accounting_code' => '4200',
              'campaign_id' => '',
              'contribution_campaign_title' => '',
              'financial_type_id' => '1',
              'financial_type' => 'Donation',
              'contribution_note' => '',
              'contribution_batch' => '',
              'contribution_recur_status' => 'Completed',
              'payment_instrument' => 'Credit Card',
              'contribution_status' => 'Completed',
              'check_number' => '',
              'instrument_id' => '1',
              'cancel_date' => '',
              'id' => '2',
              'contribution_type_id' => '1',
            ],
          ],
        ],
        'api.CustomValue.get' => [
          'is_error' => 0,
          'version' => 3,
          'count' => 0,
          'values' => [],
        ],
        'api.Note.get' => [
          'is_error' => 0,
          'version' => 3,
          'count' => 0,
          'values' => [],
        ],
      ],
    ],
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testGetIndividualWithChainedArrays"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/ContactTest.php
*
* You can see the outcome of the API tests at
* https://test.civicrm.org/job/CiviCRM-Core-Matrix/
*
* To Learn about the API read
* https://docs.civicrm.org/dev/en/latest/api/
*
* Browse the API on your own site with the API Explorer. It is in the main
* CiviCRM menu, under: Support > Development > API Explorer.
*
* Read more about testing here
* https://docs.civicrm.org/dev/en/latest/testing/
*
* API Standards documentation:
* https://docs.civicrm.org/dev/en/latest/framework/api-architecture/
*/
