<?php
/**
 * Test Generated example demonstrating the Address.get API.
 *
 * Demonstrates Use of sort filter.
 *
 * @return array
 *   API result array
 */
function address_get_example() {
  $params = [
    'options' => [
      'sort' => 'street_address DESC',
      'limit' => 2,
    ],
    'sequential' => 1,
  ];

  try{
    $result = civicrm_api3('Address', 'get', $params);
  }
  catch (CiviCRM_API3_Exception $e) {
    // Handle error here.
    $errorMessage = $e->getMessage();
    $errorCode = $e->getErrorCode();
    $errorData = $e->getExtraParams();
    return [
      'is_error' => 1,
      'error_message' => $errorMessage,
      'error_code' => $errorCode,
      'error_data' => $errorData,
    ];
  }

  return $result;
}

/**
 * Function returns array of result expected from previous function.
 *
 * @return array
 *   API result array
 */
function address_get_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 2,
    'values' => [
      '0' => [
        'id' => '2',
        'contact_id' => '19',
        'location_type_id' => '17',
        'is_primary' => '1',
        'is_billing' => 0,
        'street_address' => 'yzy',
        'street_number' => '23',
        'street_name' => 'Ambachtstraat',
        'city' => 'Brummen',
        'postal_code' => '6971 BN',
        'country_id' => '1152',
        'manual_geo_code' => 0,
      ],
      '1' => [
        'id' => '1',
        'contact_id' => '19',
        'location_type_id' => '17',
        'is_primary' => 0,
        'is_billing' => 0,
        'street_address' => 'Ambachtstraat 23',
        'street_number' => '23',
        'street_name' => 'Ambachtstraat',
        'city' => 'Brummen',
        'postal_code' => '6971 BN',
        'country_id' => '1152',
        'manual_geo_code' => 0,
      ],
    ],
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testGetAddressSort"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/AddressTest.php
*
* You can see the outcome of the API tests at
* https://test.civicrm.org/job/CiviCRM-master-git/
*
* To Learn about the API read
* http://wiki.civicrm.org/confluence/display/CRMDOC/Using+the+API
*
* Browse the api on your own site with the api explorer
* http://MYSITE.ORG/path/to/civicrm/api
*
* Read more about testing here
* http://wiki.civicrm.org/confluence/display/CRM/Testing
*
* API Standards documentation:
* http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
*/
