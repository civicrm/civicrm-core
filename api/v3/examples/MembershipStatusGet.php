<?php

/*
 
 */
function membership_status_get_example(){
$params = array( 
  'name' => 'test status',
  'version' => 3,
);

  $result = civicrm_api( 'membership_status','get',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function membership_status_get_expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 10,
  'values' => array( 
      '10' => array( 
          'id' => '10',
          'name' => 'test status',
          'label' => 'test status',
          'start_event' => 'start_date',
          'end_event' => 'end_date',
          'is_current_member' => '1',
          'is_admin' => 0,
          'is_default' => 0,
          'is_active' => '1',
          'is_reserved' => 0,
        ),
    ),
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testGet and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/MembershipStatusTest.php
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