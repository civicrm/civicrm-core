<?php

/*
 This demonstrates use of the 'format.is_success' param.
    This param causes only the success or otherwise of the function to be returned as BOOLEAN
 */
function contact_create_example(){
$params = array( 
  'version' => 3,
  'id' => 500,
  'format.is_success' => 1,
);

  $result = civicrm_api( 'contact','create',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function contact_create_expectedresult(){

  $expectedResult = 0;

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testContactCreateFormatIsSuccessFalse and can be found in
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