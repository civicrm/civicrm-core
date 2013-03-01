<?php

/*
 
 */
function profile_get_example(){
$params = array( 
  'profile_id' => 25,
  'contact_id' => 1,
  'version' => 3,
);

  $result = civicrm_api( 'profile','get',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function profile_get_expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'values' => array( 
      'first_name' => 'abc1',
      'last_name' => 'xyz1',
      'email-Primary' => 'abc1.xyz1@yahoo.com',
      'phone-1-1' => '021 512 755',
      'country-1' => '1228',
      'state_province-1' => '1021',
    ),
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testProfileGet and can be found in
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