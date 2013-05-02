<?php

/*
 
 */
function job_create_example(){
$params = array( 
  'version' => 3,
  'sequential' => 1,
  'name' => 'API_Test_Job',
  'description' => 'A long description written by hand in cursive',
  'run_frequency' => 'Daily',
  'api_entity' => 'ApiTestEntity',
  'api_action' => 'apitestaction',
  'parameters' => 'Semi-formal explanation of runtime job parameters',
  'is_active' => 1,
);

  $result = civicrm_api( 'job','create',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function job_create_expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 1,
  'values' => array( 
      '0' => array( 
          'id' => '1',
          'domain_id' => '1',
          'run_frequency' => 'Daily',
          'last_run' => '',
          'name' => 'API_Test_Job',
          'description' => 'A long description written by hand in cursive',
          'api_entity' => 'ApiTestEntity',
          'api_action' => 'apitestaction',
          'parameters' => 'Semi-formal explanation of runtime job parameters',
          'is_active' => '1',
        ),
    ),
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testCreate and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/JobTest.php
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