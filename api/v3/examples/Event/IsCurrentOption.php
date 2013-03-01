<?php

/*
 demonstrates use of is.Current option
 */
function event_get_example(){
$params = array( 
  'version' => 3,
  'isCurrent' => 1,
);

  $result = civicrm_api( 'event','get',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function event_get_expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 3,
  'values' => array( 
      '3' => array( 
          'id' => '3',
          'title' => 'Annual CiviCRM meet 2',
          'event_title' => 'Annual CiviCRM meet 2',
          'summary' => 'If you have any CiviCRM realted issues or want to track where CiviCRM is heading, Sign up now',
          'description' => 'This event is intended to give brief idea about progess of CiviCRM and giving solutions to common user issues',
          'event_description' => 'This event is intended to give brief idea about progess of CiviCRM and giving solutions to common user issues',
          'event_type_id' => '1',
          'participant_listing_id' => 0,
          'is_public' => '1',
          'start_date' => '2013-02-05 00:00:00',
          'event_start_date' => '2013-02-05 00:00:00',
          'end_date' => '2013-02-11 00:00:00',
          'event_end_date' => '2013-02-11 00:00:00',
          'is_online_registration' => '1',
          'registration_start_date' => '2010-06-01 00:00:00',
          'registration_end_date' => '2010-10-15 00:00:00',
          'max_participants' => '100',
          'event_full_text' => 'Sorry! We are already full',
          'is_monetary' => 0,
          'is_map' => 0,
          'is_active' => '1',
          'is_show_location' => 0,
          'default_role_id' => '1',
          'is_email_confirm' => 0,
          'is_pay_later' => 0,
          'is_partial_payment' => 0,
          'is_multiple_registrations' => 0,
          'allow_same_participant_emails' => 0,
          'is_template' => 0,
          'created_date' => '2013-02-04 22:31:23',
          'is_share' => '1',
        ),
    ),
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testGetIsCurrent and can be found in
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