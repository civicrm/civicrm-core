<?php

/*
 
 */
function profile_apply_example(){
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

  $result = civicrm_api( 'profile','apply',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function profile_apply_expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'version' => 3,
  'count' => 10,
  'values' => array( 
      'contact_type' => 'Individual',
      'contact_sub_type' => '',
      'contact_id' => 1,
      'profile_id' => 25,
      'version' => 3,
      'first_name' => 'abc2',
      'last_name' => 'xyz2',
      'email' => array( 
          '1' => array( 
              'location_type_id' => '1',
              'is_primary' => 1,
              'email' => 'abc2.xyz2@gmail.com',
            ),
        ),
      'phone' => array( 
          '2' => array( 
              'location_type_id' => '1',
              'is_primary' => 1,
              'phone_type_id' => '1',
              'phone' => '022 321 826',
            ),
        ),
      'address' => array( 
          '1' => array( 
              'location_type_id' => '1',
              'is_primary' => 1,
              'country_id' => '1013',
              'state_province_id' => '1000',
            ),
        ),
    ),
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testProfileApply and can be found in
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