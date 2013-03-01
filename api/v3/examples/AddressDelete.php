<?php

/*
 
 */
function address_delete_example(){
$params = array( 
  'contact_id' => 8,
  'location_type_id' => 11,
  'street_name' => 'Ambachtstraat',
  'street_number' => '23',
  'street_address' => 'Ambachtstraat 23',
  'postal_code' => '6971 BN',
  'country_id' => '1152',
  'city' => 'Brummen',
  'is_primary' => 1,
  'version' => 3,
);

  $result = civicrm_api( 'address','delete',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function address_delete_expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'values' => 1,
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testDeleteAddress and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/AddressTest.php
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