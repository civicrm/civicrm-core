<?php

/*
 
 */
function im_get_example(){
$params = array( 
  'version' => 3,
  'contact_id' => 1,
  'name' => 'My Yahoo IM Handle',
  'location_type_id' => 1,
  'provider_id' => 1,
);

  $result = civicrm_api( 'im','get',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function im_get_expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 1,
  'values' => array( 
      '1' => array( 
          'id' => '1',
          'contact_id' => '1',
          'location_type_id' => '1',
          'name' => 'My Yahoo IM Handle',
          'provider_id' => '1',
          'is_primary' => 0,
          'is_billing' => 0,
        ),
    ),
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testGetIm and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/ImTest.php
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