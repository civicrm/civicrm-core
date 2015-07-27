<?php
/**
 * Test Generated example demonstrating the Contact.get API.
 *
 * Demonstrates use of Replace in a nested API call.
 *
 * @return array
 *   API result array
 */
function contact_get_example() {
  $params = array(
    'id' => 10,
    'api.email.replace' => array(
      'values' => array(
        '0' => array(
          'location_type_id' => 20,
          'email' => '1-1@example.com',
          'is_primary' => 1,
        ),
        '1' => array(
          'location_type_id' => 20,
          'email' => '1-2@example.com',
          'is_primary' => 0,
        ),
        '2' => array(
          'location_type_id' => 20,
          'email' => '1-3@example.com',
          'is_primary' => 0,
        ),
        '3' => array(
          'location_type_id' => 21,
          'email' => '2-1@example.com',
          'is_primary' => 0,
        ),
        '4' => array(
          'location_type_id' => 21,
          'email' => '2-2@example.com',
          'is_primary' => 0,
        ),
      ),
    ),
  );

  try{
    $result = civicrm_api3('Contact', 'get', $params);
  }
  catch (CiviCRM_API3_Exception $e) {
    // Handle error here.
    $errorMessage = $e->getMessage();
    $errorCode = $e->getErrorCode();
    $errorData = $e->getExtraParams();
    return array(
      'error' => $errorMessage,
      'error_code' => $errorCode,
      'error_data' => $errorData,
    );
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

  $expectedResult = array(
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 10,
    'values' => array(
      '10' => array(
        'contact_id' => '10',
        'contact_type' => 'Organization',
        'contact_sub_type' => '',
        'sort_name' => 'Unit Test Organization',
        'display_name' => 'Unit Test Organization',
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
        'first_name' => '',
        'middle_name' => '',
        'last_name' => '',
        'prefix_id' => '',
        'suffix_id' => '',
        'formal_title' => '',
        'communication_style_id' => '',
        'job_title' => '',
        'gender_id' => '',
        'birth_date' => '',
        'is_deceased' => 0,
        'deceased_date' => '',
        'household_name' => '',
        'organization_name' => 'Unit Test Organization',
        'sic_code' => '',
        'contact_is_deleted' => 0,
        'current_employer' => '',
        'address_id' => '',
        'street_address' => '',
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
        'email_id' => '',
        'email' => '',
        'on_hold' => '',
        'im_id' => '',
        'provider_id' => '',
        'im' => '',
        'worldregion_id' => '',
        'world_region' => '',
        'individual_prefix' => '',
        'individual_suffix' => '',
        'communication_style' => '',
        'gender' => '',
        'state_province_name' => '',
        'state_province' => '',
        'country' => '',
        'id' => '10',
        'api.email.replace' => array(
          'is_error' => 0,
          'version' => 3,
          'count' => 5,
          'values' => array(
            '0' => array(
              'id' => '18',
              'contact_id' => '10',
              'location_type_id' => '20',
              'email' => '1-1@example.com',
              'is_primary' => '1',
              'is_billing' => '',
              'on_hold' => '',
              'is_bulkmail' => '',
              'hold_date' => '',
              'reset_date' => '',
              'signature_text' => '',
              'signature_html' => '',
            ),
            '1' => array(
              'id' => '19',
              'contact_id' => '10',
              'location_type_id' => '20',
              'email' => '1-2@example.com',
              'is_primary' => 0,
              'is_billing' => '',
              'on_hold' => '',
              'is_bulkmail' => '',
              'hold_date' => '',
              'reset_date' => '',
              'signature_text' => '',
              'signature_html' => '',
            ),
            '2' => array(
              'id' => '20',
              'contact_id' => '10',
              'location_type_id' => '20',
              'email' => '1-3@example.com',
              'is_primary' => 0,
              'is_billing' => '',
              'on_hold' => '',
              'is_bulkmail' => '',
              'hold_date' => '',
              'reset_date' => '',
              'signature_text' => '',
              'signature_html' => '',
            ),
            '3' => array(
              'id' => '21',
              'contact_id' => '10',
              'location_type_id' => '21',
              'email' => '2-1@example.com',
              'is_primary' => 0,
              'is_billing' => '',
              'on_hold' => '',
              'is_bulkmail' => '',
              'hold_date' => '',
              'reset_date' => '',
              'signature_text' => '',
              'signature_html' => '',
            ),
            '4' => array(
              'id' => '22',
              'contact_id' => '10',
              'location_type_id' => '21',
              'email' => '2-2@example.com',
              'is_primary' => 0,
              'is_billing' => '',
              'on_hold' => '',
              'is_bulkmail' => '',
              'hold_date' => '',
              'reset_date' => '',
              'signature_text' => '',
              'signature_html' => '',
            ),
          ),
        ),
      ),
    ),
  );

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testReplaceEmailsInChain"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/EmailTest.php
*
* You can see the outcome of the API tests at
* https://test.civicrm.org/job/CiviCRM-master-git/
*
* To Learn about the API read
* http://wiki.civicrm.org/confluence/display/CRMDOC/Using+the+API
*
* Browse the api on your own site with the api explorer
* http://MYSITE.ORG/path/to/civicrm/api
*
* Read more about testing here
* http://wiki.civicrm.org/confluence/display/CRM/Testing
*
* API Standards documentation:
* http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
*/
