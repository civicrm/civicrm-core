<?php

/*
 
 */
function profile_set_example(){
$params = array( 
  'profile_id' => 25,
  'contact_id' => 1,
  'version' => 3,
  'first_name' => 'abc2',
  'last_name' => 'xyz2',
  'email-Primary' => 'abc2.xyz2@gmail.com',
  'phone-1-1' => '022 321 826',
  'country-1' => '1013',
  'state_province-1' => '1000',
);

  $result = civicrm_api( 'profile','set',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function profile_set_expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 1,
  'values' => array( 
      '1' => array( 
          'id' => '1',
          'contact_type' => 'Individual',
          'contact_sub_type' => '',
          'do_not_email' => 0,
          'do_not_phone' => 0,
          'do_not_mail' => 0,
          'do_not_sms' => 0,
          'do_not_trade' => 0,
          'is_opt_out' => 0,
          'legal_identifier' => '',
          'external_identifier' => '',
          'sort_name' => 'xyz2, abc2',
          'display_name' => 'abc2 xyz2',
          'nick_name' => '',
          'legal_name' => '',
          'image_URL' => '',
          'preferred_communication_method' => '',
          'preferred_language' => 'en_US',
          'preferred_mail_format' => 'Both',
          'hash' => '67eac7789eaee00',
          'api_key' => '',
          'first_name' => 'abc2',
          'middle_name' => '',
          'last_name' => 'xyz2',
          'prefix_id' => '',
          'suffix_id' => '',
          'email_greeting_id' => '1',
          'email_greeting_custom' => '',
          'email_greeting_display' => 'Dear abc1',
          'postal_greeting_id' => '1',
          'postal_greeting_custom' => '',
          'postal_greeting_display' => 'Dear abc1',
          'addressee_id' => '1',
          'addressee_custom' => '',
          'addressee_display' => 'abc1 xyz1',
          'job_title' => '',
          'gender_id' => '',
          'birth_date' => '',
          'is_deceased' => 0,
          'deceased_date' => '',
          'household_name' => '',
          'primary_contact_id' => '',
          'organization_name' => '',
          'sic_code' => '',
          'user_unique_id' => '',
          'created_date' => '2013-02-05 11:40:49',
          'modified_date' => '2012-11-14 16:02:35',
        ),
    ),
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testProfileSet and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/ProfileTest.php
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