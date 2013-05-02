<?php

/*
 demonstrates use of return is_full 
 */
function event_getsingle_example(){
$params = array( 
  'id' => 1,
  'version' => 3,
  'return.is_full' => 1,
);

  $result = civicrm_api( 'event','getsingle',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function event_getsingle_expectedresult(){

  $expectedResult = array( 
  'id' => '1',
  'title' => 'Annual CiviCRM meet',
  'event_title' => 'Annual CiviCRM meet',
  'event_type_id' => '1',
  'participant_listing_id' => 0,
  'is_public' => '1',
  'start_date' => '2008-10-21 00:00:00',
  'event_start_date' => '2008-10-21 00:00:00',
  'is_online_registration' => 0,
  'max_participants' => '1',
  'is_monetary' => 0,
  'is_map' => 0,
  'is_active' => '1',
  'is_show_location' => '1',
  'default_role_id' => '1',
  'is_email_confirm' => 0,
  'is_pay_later' => 0,
  'is_partial_payment' => 0,
  'is_multiple_registrations' => 0,
  'allow_same_participant_emails' => 0,
  'is_template' => 0,
  'created_date' => '2013-02-04 22:31:24',
  'is_share' => '1',
  'available_places' => 0,
  'is_full' => '1',
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testGetSingleReturnIsFull and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/EventTest.php
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