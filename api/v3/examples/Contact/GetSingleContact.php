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
  $params = array(
    'id' => 17,
  );

  try{
    $result = civicrm_api3('Contact', 'getsingle', $params);
  }
  catch (CiviCRM_API3_Exception $e) {
    // Handle error here.
    $errorMessage = $e->getMessage();
    $errorCode = $e->getErrorCode();
    $errorData = $e->getExtraParams();
    return array(
      'is_error' => 1,
      'error_message' => $errorMessage,
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
function contact_getsingle_expectedresult() {

  $expectedResult = array(
    'contact_id' => '17',
    'contact_type' => 'Individual',
    'contact_sub_type' => '',
    'sort_name' => '',
    'display_name' => 'Test Contact',
    'do_not_email' => '',
    'do_not_phone' => '',
    'do_not_mail' => '',
    'do_not_sms' => '',
    'do_not_trade' => '',
    'is_opt_out' => 0,
    'legal_identifier' => '',
    'external_identifier' => '',
    'nick_name' => '',
    'legal_name' => '',
    'image_URL' => '',
    'preferred_communication_method' => '',
    'preferred_language' => '',
    'preferred_mail_format' => '',
    'first_name' => 'Test',
    'middle_name' => '',
    'last_name' => 'Contact',
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
    'email_id' => '',
    'email' => '',
    'on_hold' => '',
    'im_id' => '',
    'provider_id' => '',
    'im' => '',
    'worldregion_id' => '',
    'world_region' => '',
    'languages' => '',
    'individual_prefix' => '',
    'individual_suffix' => '',
    'communication_style' => '',
    'gender' => '',
    'state_province_name' => '',
    'state_province' => '',
    'country' => '',
    'id' => '17',
  );

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testContactGetSingleEntityArray"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/ContactTest.php
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
