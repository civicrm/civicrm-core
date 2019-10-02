<?php
/**
 * Test Generated example demonstrating the Contact.getsingle API.
 *
 * This demonstrates use of the 'format.single_entity_array' param.
 * This param causes the only contact to be returned as an array without the other levels.
 * It will be ignored if there is not exactly 1 result
 *
 * @return array
 *   API result array
 */
function contact_getsingle_example() {
  $params = [
    'id' => 3,
  ];

  try{
    $result = civicrm_api3('Contact', 'getsingle', $params);
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
function contact_getsingle_expectedresult() {

  $expectedResult = [
    'contact_id' => '3',
    'contact_type' => 'Individual',
    'contact_sub_type' => '',
    'sort_name' => 'Contact, Test',
    'display_name' => 'Mr. Test Contact II',
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
    'first_name' => 'Test',
    'middle_name' => 'J.',
    'last_name' => 'Contact',
    'prefix_id' => '3',
    'suffix_id' => '3',
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
    'email' => 'anthony_anderson@civicrm.org',
    'on_hold' => 0,
    'im_id' => '',
    'provider_id' => '',
    'im' => '',
    'worldregion_id' => '',
    'world_region' => '',
    'languages' => 'English (United States)',
    'individual_prefix' => 'Mr.',
    'individual_suffix' => 'II',
    'communication_style' => 'Formal',
    'gender' => '',
    'state_province_name' => '',
    'state_province' => '',
    'country' => '',
    'id' => '3',
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testContactGetSingleEntityArray"
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
