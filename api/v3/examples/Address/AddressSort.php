<?php
/**
 * Test Generated example of using address get API
 * Demonstrates Use of sort filter *
 */
function address_get_example(){
$params = array(
  'options' => array(
      'sort' => 'street_address DESC',
      'limit' => 2,
    ),
  'sequential' => 1,
);

try{
  $result = civicrm_api3('address', 'get', $params);
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
function address_get_expectedresult(){

  $expectedResult = array(
  'is_error' => 0,
  'version' => 3,
  'count' => 2,
  'values' => array(
      '0' => array(
          'id' => '9',
          'contact_id' => '11',
          'location_type_id' => '14',
          'is_primary' => '1',
          'is_billing' => 0,
          'street_address' => 'Ambachtstraat 23',
          'street_number' => '23',
          'street_name' => 'Ambachtstraat',
          'city' => 'Brummen',
          'postal_code' => '6971 BN',
          'country_id' => '1152',
          'manual_geo_code' => 0,
        ),
      '1' => array(
          'id' => '1',
          'contact_id' => '2',
          'location_type_id' => '1',
          'is_primary' => '1',
          'is_billing' => '1',
          'street_address' => '15 Main St',
          'street_number' => '15',
          'street_number_suffix' => 'S',
          'street_name' => 'Main',
          'street_type' => 'St',
          'city' => 'Collinsville',
          'state_province_id' => '1006',
          'postal_code' => '6022',
          'country_id' => '1228',
          'geo_code_1' => '41.8328',
          'geo_code_2' => '-72.9253',
          'manual_geo_code' => 0,
        ),
    ),
);

  return $expectedResult;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testGetAddressSort and can be found in
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
