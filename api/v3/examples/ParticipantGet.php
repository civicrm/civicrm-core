<?php

/*
 
 */
function participant_get_example(){
$params = array( 
  'id' => 1,
  'version' => 3,
);

  $result = civicrm_api( 'participant','get',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function participant_get_expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 1,
  'values' => array( 
      '1' => array( 
          'contact_id' => '2',
          'contact_type' => 'Individual',
          'contact_sub_type' => '',
          'sort_name' => 'Anderson, Anthony',
          'display_name' => 'Mr. Anthony Anderson II',
          'event_id' => '5',
          'event_title' => 'Annual CiviCRM meet',
          'event_start_date' => '2008-10-21 00:00:00',
          'event_end_date' => '2008-10-23 00:00:00',
          'participant_id' => '1',
          'participant_fee_level' => '',
          'participant_fee_amount' => '',
          'participant_fee_currency' => '',
          'event_type' => 'Conference',
          'participant_status_id' => '2',
          'participant_status' => 'Attended',
          'participant_role_id' => '1',
          'participant_register_date' => '2007-02-19 00:00:00',
          'participant_source' => 'Wimbeldon',
          'participant_note' => '',
          'participant_is_pay_later' => 0,
          'participant_is_test' => 0,
          'participant_registered_by_id' => '',
          'participant_discount_name' => '',
          'participant_campaign_id' => '',
          'id' => '1',
        ),
    ),
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testGetParamsAsIdOnly and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/ParticipantTest.php
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