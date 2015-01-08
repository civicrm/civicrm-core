<?php



/*
 
 */
function activity_type_delete_example(){
$params = array( 
  'activity_type_id' => 682,
  'version' => 3,
);

  require_once 'api/api.php';
  $result = civicrm_api( 'activity_type','delete',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function activity_type_delete_expectedresult(){

  $expectedResult = array( 
  'is_error' => 1,
  'error_message' => 'DB_DataObject Error: delete: No condition specifed for query',
  'tip' => 'add debug=1 to your API call to have more info about the error',
);

  return $expectedResult  ;
}




/*
* This example has been generated from the API test suite. The test that created it is called
* 
* testActivityTypeDelete and can be found in 
* http://svn.civicrm.org/civicrm/branches/v3.4/tests/phpunit/CiviTest/api/v3/ActivityTypeTest.php
* 
* You can see the outcome of the API tests at 
* http://tests.dev.civicrm.org/trunk/results-api_v3
* and review the wiki at
* http://wiki.civicrm.org/confluence/display/CRMDOC/CiviCRM+Public+APIs
* Read more about testing here
* http://wiki.civicrm.org/confluence/display/CRM/Testing
*/