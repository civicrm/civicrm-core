<?php

/*
 
 */
function loc_block_create_example(){
$params = array( 
  'version' => 3,
  'address_id' => 2,
  'phone_id' => 2,
  'email_id' => 3,
);

  $result = civicrm_api( 'loc_block','create',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function loc_block_create_expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 2,
  'values' => array( 
      '2' => array( 
          'id' => '2',
          'address_id' => '2',
          'email_id' => '3',
          'phone_id' => '2',
          'im_id' => '',
          'address_2_id' => '',
          'email_2_id' => '',
          'phone_2_id' => '',
          'im_2_id' => '',
        ),
    ),
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testCreateLocBlock and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/LocBlockTest.php
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