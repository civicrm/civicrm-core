<?php

/*
 
 */
function email_create_example(){
$params = array( 
  'contact_id' => 3,
  'location_type_id' => 6,
  'email' => 'api@a-team.com',
  'is_primary' => 1,
  'version' => 3,
);

  $result = civicrm_api( 'email','create',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function email_create_expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 3,
  'values' => array( 
      '3' => array( 
          'id' => '3',
          'contact_id' => '3',
          'location_type_id' => '6',
          'email' => 'api@a-team.com',
          'is_primary' => '1',
          'is_billing' => '',
          'on_hold' => '',
          'is_bulkmail' => '',
          'hold_date' => '',
          'reset_date' => '',
          'signature_text' => '',
          'signature_html' => '',
        ),
    ),
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testCreateEmail and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/EmailTest.php
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