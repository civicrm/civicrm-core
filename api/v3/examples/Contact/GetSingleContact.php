<?php

/*
 This demonstrates use of the 'format.single_entity_array' param.
    /* This param causes the only contact to be returned as an array without the other levels.
    /* it will be ignored if there is not exactly 1 result
 */
function contact_getsingle_example(){
$params = array( 
  'version' => 3,
  'id' => 17,
);

  $result = civicrm_api( 'contact','getsingle',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function contact_getsingle_expectedresult(){

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
  'preferred_mail_format' => '',
  'first_name' => 'Test',
  'middle_name' => '',
  'last_name' => 'Contact',
  'job_title' => '',
  'birth_date' => '',
  'is_deceased' => 0,
  'deceased_date' => '',
  'household_name' => '',
  'organization_name' => '',
  'sic_code' => '',
  'contact_is_deleted' => 0,
  'gender_id' => '',
  'gender' => '',
  'prefix_id' => '',
  'prefix' => '',
  'suffix_id' => '',
  'suffix' => '',
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
  'state_province_name' => '',
  'state_province' => '',
  'country_id' => '',
  'country' => '',
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
  'id' => '17',
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testContactGetSingle_entity_array and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/ContactTest.php
*
* You can see the outcome of the API tests at
* http://tests.dev.civicrm.org/trunk/results-api_v3
*
* To Learn about the API read
* http://book.civicrm.org/developer/current/techniques/api/
*
* and review the wiki at
* http://wiki.civicrm.org/confluence/display/CRMDOC/CiviCRM+Public+APIs
*
* Read more about testing here
* http://wiki.civicrm.org/confluence/display/CRM/Testing
*
* API Standards documentation:
* http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
*/