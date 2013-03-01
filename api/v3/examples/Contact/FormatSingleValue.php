<?php

/*
 This demonstrates use of the 'format.single_value' param.
    /* This param causes only a single value of the only entity to be returned as an string.
    /* it will be ignored if there is not exactly 1 result
 */
function contact_getvalue_example(){
$params = array( 
  'version' => 3,
  'id' => 17,
  'return' => 'display_name',
);

  $result = civicrm_api( 'contact','getvalue',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function contact_getvalue_expectedresult(){

  $expectedResult = 'Test Contact';

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testContactGetFormatSingleValue and can be found in
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