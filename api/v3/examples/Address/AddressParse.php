<?php
/**
 * Test Generated example of using address create API
 * Demonstrates Use of address parsing param *
 */
function address_create_example(){
$params = array(
  'street_parsing' => 1,
  'street_address' => '54A Excelsior Ave. Apt 1C',
  'location_type_id' => 7,
  'contact_id' => 4,
);

try{
  $result = civicrm_api3('address', 'create', $params);
}
catch (CiviCRM_API3_Exception $e) {
  // handle error here
  $errorMessage = $e->getMessage();
  $errorCode = $e->getErrorCode();
  $errorData = $e->getExtraParams();
  return array('error' => $errorMessage, 'error_code' => $errorCode, 'error_data' => $errorData);
}

return $result;
}

/**
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

  return $expectedResult;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testCreateAddressParsing and can be found in
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/AddressTest.php
*
* You can see the outcome of the API tests at
* https://test.civicrm.org/job/CiviCRM-master-git/
*
* To Learn about the API read
* http://wiki.civicrm.org/confluence/display/CRMDOC/Using+the+API
*
* Browse the api on your own site with the api explorer
* http://MYSITE.ORG/path/to/civicrm/api/explorer
*
* Read more about testing here
* http://wiki.civicrm.org/confluence/display/CRM/Testing
*
* API Standards documentation:
* http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
*/
