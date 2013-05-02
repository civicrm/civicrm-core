<?php

/*
 
 */
function membership_status_create_example(){
$params = array( 
  'name' => 'test membership status',
  'version' => 3,
);

  $result = civicrm_api( 'membership_status','create',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function membership_status_create_expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 19,
  'values' => array( 
      '19' => array( 
          'id' => '19',
          'name' => 'test membership status',
          'label' => 'test membership status',
          'start_event' => '',
          'start_event_adjust_unit' => '',
          'start_event_adjust_interval' => '',
          'end_event' => '',
          'end_event_adjust_unit' => '',
          'end_event_adjust_interval' => '',
          'is_current_member' => '',
          'is_admin' => '',
          'weight' => '',
          'is_default' => '',
          'is_active' => '',
          'is_reserved' => '',
        ),
    ),
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testCreate and can be found in
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