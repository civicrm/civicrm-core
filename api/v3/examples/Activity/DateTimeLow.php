<?php

/*
 demonstrates _low filter (at time of writing doesn't work if contact_id is set
 */
function activity_get_example(){
$params = array( 
  'version' => 3,
  'filter.activity_date_time_low' => '20120101000000',
  'sequential' => 1,
);

  $result = civicrm_api( 'activity','get',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function activity_get_expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 2,
  'values' => array( 
      '0' => array( 
          'id' => '2',
          'source_contact_id' => '17',
          'activity_type_id' => '44',
          'subject' => 'Make-it-Happen Meeting',
          'activity_date_time' => '2012-02-16 00:00:00',
          'duration' => '120',
          'location' => 'Pensulvania',
          'details' => 'a test activity',
          'status_id' => '1',
          'priority_id' => '1',
          'is_test' => 0,
          'is_auto' => 0,
          'is_current_revision' => '1',
          'is_deleted' => 0,
        ),
    ),
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testGetFilterMaxDate and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/ActivityTest.php
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