<?php

/*
 
 */
function grant_delete_example(){
$params = array( 
  'version' => 3,
  'contact_id' => 5,
  'application_received_date' => 'now',
  'decision_date' => 'next Monday',
  'amount_total' => '500',
  'status_id' => 1,
  'rationale' => 'Just Because',
  'currency' => 'USD',
  'grant_type_id' => 1,
);

  $result = civicrm_api( 'grant','delete',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function grant_delete_expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'values' => true,
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testDeleteGrant and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/GrantTest.php
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