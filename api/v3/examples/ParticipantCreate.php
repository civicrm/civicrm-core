<?php

/*
 
 */
function participant_create_example(){
$params = array( 
  'contact_id' => 4,
  'event_id' => 1,
  'status_id' => 1,
  'role_id' => 1,
  'register_date' => '2007-07-21 00:00:00',
  'source' => 'Online Event Registration: API Testing',
  'version' => 3,
  'custom_1' => 'custom string',
);

  $result = civicrm_api( 'participant','create',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function participant_create_expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 4,
  'values' => array( 
      '4' => array( 
          'id' => '4',
          'contact_id' => '4',
          'event_id' => '1',
          'status_id' => '1',
          'role_id' => '1',
          'register_date' => '20070721000000',
          'source' => 'Online Event Registration: API Testing',
          'fee_level' => '',
          'is_test' => '',
          'is_pay_later' => '',
          'fee_amount' => '',
          'registered_by_id' => '',
          'discount_id' => '',
          'fee_currency' => '',
          'campaign_id' => '',
          'discount_amount' => '',
          'cart_id' => '',
          'must_wait' => '',
        ),
    ),
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testCreateWithCustom and can be found in
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