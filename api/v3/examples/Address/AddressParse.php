<?php

/*
 Demonstrates Use of address parsing param
 */
function address_create_example(){
$params = array( 
  'version' => 3,
  'street_parsing' => 1,
  'street_address' => '54A Excelsior Ave. Apt 1C',
  'location_type_id' => 7,
  'contact_id' => 4,
);

  $result = civicrm_api( 'address','create',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function address_create_expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 3,
  'values' => array( 
      '3' => array( 
          'id' => '3',
          'contact_id' => '4',
          'location_type_id' => '7',
          'is_primary' => '1',
          'is_billing' => 0,
          'street_address' => '54A Excelsior Ave. Apt 1C',
          'street_number' => '54',
          'street_number_suffix' => 'A',
          'street_name' => 'Excelsior Ave.',
          'street_unit' => 'Apt 1C',
          'manual_geo_code' => 0,
        ),
    ),
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testCreateAddressParsing and can be found in
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