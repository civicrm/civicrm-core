<?php

/*
 Example demonstrates setting & retrieving the target & source
 */
function activity__example(){
$params = array( 
  'source_contact_id' => 17,
  'subject' => 'Make-it-Happen Meeting',
  'activity_date_time' => '20110316',
  'duration' => 120,
  'location' => 'Pensulvania',
  'details' => 'a test activity',
  'status_id' => 1,
  'activity_type_id' => 1,
  'version' => 3,
  'priority_id' => 1,
  'target_contact_id' => 17,
  'assignee_contact_id' => 17,
);

  $result = civicrm_api( 'activity','',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function activity__expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 1,
  'values' => array( 
      '1' => array( 
          'id' => '1',
          'source_contact_id' => '17',
          'source_record_id' => '',
          'activity_type_id' => '1',
          'subject' => 'Make-it-Happen Meeting',
          'activity_date_time' => '20110316000000',
          'duration' => '120',
          'location' => 'Pensulvania',
          'phone_id' => '',
          'phone_number' => '',
          'details' => 'a test activity',
          'status_id' => '1',
          'priority_id' => '1',
          'parent_id' => '',
          'is_test' => '',
          'medium_id' => '',
          'is_auto' => '',
          'relationship_id' => '',
          'is_current_revision' => '',
          'original_id' => '',
          'result' => '',
          'is_deleted' => '',
          'campaign_id' => '',
          'engagement_level' => '',
          'weight' => '',
        ),
    ),
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testActivityReturnTargetAssignee and can be found in
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