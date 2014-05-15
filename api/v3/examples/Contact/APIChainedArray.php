<?php
/**
 * Test Generated example of using contact get API
 * /*this demonstrates the usage of chained api functions. In this case no notes or custom fields have been created  *
 */
function contact_get_example(){
$params = array(
  'id' => 1,
  'api.website.get' => array(),
  'api.Contribution.get' => array(
      'total_amount' => '120.00',
    ),
  'api.CustomValue.get' => 1,
  'api.Note.get' => 1,
);

try{
  $result = civicrm_api3('contact', 'get', $params);
}
catch (CiviCRM_API3_Exception $e) {
  // handle error here
  $errorMessage = $e->getMessage();
  $errorCode = $e->getErrorCode();
  $errorData = $e->getExtraParams();
  return array('error' => $errorMessage, 'error_code' => $errorCode, 'error_data' => $errorData);
}

return $result;
}

/**
 * Function returns array of result expected from previous function
 */
function contact_get_expectedresult(){

  $expectedResult = array(
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 1,
  'values' => array(
      '1' => array(
          'contact_id' => '1',
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
          'address_id' => '2',
          'street_address' => '1 my road',
          'supplemental_address_1' => '',
          'supplemental_address_2' => '',
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
          'individual_prefix' => '',
          'individual_suffix' => '',
          'gender' => '',
          'state_province_name' => '',
          'state_province' => '',
          'country' => '',
          'id' => '1',
          'api.website.get' => array(
              'is_error' => 0,
              'version' => 3,
              'count' => 1,
              'id' => 1,
              'values' => array(
                  '0' => array(
                      'id' => '1',
                      'contact_id' => '1',
                      'url' => 'http://civicrm.org',
                    ),
                ),
            ),
          'api.Contribution.get' => array(
              'is_error' => 0,
              'version' => 3,
              'count' => 1,
              'id' => 2,
              'values' => array(
                  '0' => array(
                      'contact_id' => '1',
                      'contact_type' => 'Individual',
                      'contact_sub_type' => '',
                      'sort_name' => 'xyz3, abc3',
                      'display_name' => 'abc3 xyz3',
                      'contribution_id' => '2',
                      'currency' => 'USD',
                      'receive_date' => '2011-01-01 00:00:00',
                      'non_deductible_amount' => '10.00',
                      'total_amount' => '120.00',
                      'fee_amount' => '50.00',
                      'net_amount' => '90.00',
                      'trxn_id' => '12335',
                      'invoice_id' => '67830',
                      'cancel_date' => '',
                      'cancel_reason' => '',
                      'receipt_date' => '',
                      'thankyou_date' => '',
                      'contribution_source' => 'SSF',
                      'amount_level' => '',
                      'is_test' => 0,
                      'is_pay_later' => 0,
                      'contribution_status_id' => '1',
                      'check_number' => '',
                      'contribution_campaign_id' => '',
                      'financial_type_id' => '1',
                      'financial_type' => 'Donation',
                      'instrument_id' => '81',
                      'payment_instrument' => 'Credit Card',
                      'product_id' => '',
                      'product_name' => '',
                      'sku' => '',
                      'contribution_product_id' => '',
                      'product_option' => '',
                      'fulfilled_date' => '',
                      'contribution_start_date' => '',
                      'contribution_end_date' => '',
                      'contribution_recur_id' => '',
                      'financial_account_id' => '1',
                      'accounting_code' => '4200',
                      'contribution_note' => '',
                      'contribution_batch' => '',
                      'contribution_status' => 'Completed',
                      'contribution_payment_instrument' => 'Credit Card',
                      'contribution_check_number' => '',
                      'id' => '2',
                      'contribution_type_id' => '1',
                    ),
                ),
            ),
          'api.CustomValue.get' => array(
              'is_error' => 0,
              'version' => 3,
              'count' => 0,
              'values' => array(),
            ),
          'api.Note.get' => array(
              'is_error' => 0,
              'version' => 3,
              'count' => 0,
              'values' => array(),
            ),
        ),
    ),
);

  return $expectedResult;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testGetIndividualWithChainedArrays and can be found in
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/ContactTest.php
*
* You can see the outcome of the API tests at
* https://test.civicrm.org/job/CiviCRM-master-git/
*
* To Learn about the API read
* http://wiki.civicrm.org/confluence/display/CRMDOC/Using+the+API
*
* Browse the api on your own site with the api explorer
* http://MYSITE.ORG/path/to/civicrm/api/explorer
*
* Read more about testing here
* http://wiki.civicrm.org/confluence/display/CRM/Testing
*
* API Standards documentation:
* http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
*/
