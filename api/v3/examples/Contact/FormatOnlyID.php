<?php

/*
 This demonstrates use of the 'format.id_only' param.
    /* This param causes the id of the only entity to be returned as an integer.
    /* it will be ignored if there is not exactly 1 result
 */
function contact_get_example(){
$params = array( 
  'version' => 3,
  'id' => 17,
  'format.only_id' => 1,
);

  $result = civicrm_api( 'contact','get',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function contact_get_expectedresult(){

  $expectedResult = 17;

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testContactGetFormatID_only and can be found in
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