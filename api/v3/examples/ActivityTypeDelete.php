<?php

/*
 
 */
function activity_type_delete_example(){
$params = array( 
  'activity_type_id' => 725,
  'version' => 3,
);

  $result = civicrm_api( 'activity_type','delete',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function activity_type_delete_expectedresult(){

  $expectedResult = array( 
  'is_error' => 1,
  'error_message' => 'Undefined index: activity_type_id',
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testActivityTypeDelete and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/ActivityTypeTest.php
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