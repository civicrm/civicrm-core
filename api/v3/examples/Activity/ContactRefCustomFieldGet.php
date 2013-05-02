<?php

/*
 Get with Contact Ref Custom Field
 */
function activity_create_example(){
$params = array( 
  'source_contact_id' => 17,
  'activity_type_id' => '44',
  'subject' => 'test activity type id',
  'activity_date_time' => '2011-06-02 14:36:13',
  'status_id' => 2,
  'priority_id' => 1,
  'duration' => 120,
  'location' => 'Pensulvania',
  'details' => 'a test activity',
  'version' => 3,
  'custom_2' => '17',
);

  $result = civicrm_api( 'activity','create',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function activity_create_expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 1,
  'values' => array( 
      '1' => array( 
          'id' => '1',
          'source_contact_id' => '17',
          'activity_type_id' => '44',
          'subject' => 'test activity type id',
          'activity_date_time' => '2011-06-02 14:36:13',
          'duration' => '120',
          'location' => 'Pensulvania',
          'details' => 'a test activity',
          'status_id' => '2',
          'priority_id' => '1',
          'is_test' => 0,
          'is_auto' => 0,
          'is_current_revision' => '1',
          'is_deleted' => 0,
          'custom_2_id' => '17',
          'custom_2_1_id' => '17',
          'custom_2' => 'Contact, Test',
          'custom_2_1' => 'Contact, Test',
        ),
    ),
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testActivityCreateCustomContactRefField and can be found in
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