<?php
/**
 * Test Generated example demonstrating the Address.create API.
 *
 * @return array
 *   API result array
 */
function address_create_example() {
  $params = array(
    'contact_id' => 3,
    'location_type_id' => 6,
    'street_name' => 'Ambachtstraat',
    'street_number' => '23',
    'street_address' => 'Ambachtstraat 23',
    'postal_code' => '6971 BN',
    'country_id' => '1152',
    'city' => 'Brummen',
    'is_primary' => 1,
  );

  try{
    $result = civicrm_api3('Address', 'create', $params);
  }
  catch (CiviCRM_API3_Exception $e) {
    // Handle error here.
    $errorMessage = $e->getMessage();
    $errorCode = $e->getErrorCode();
    $errorData = $e->getExtraParams();
    return array(
      'error' => $errorMessage,
      'error_code' => $errorCode,
      'error_data' => $errorData,
    );
  }

  return $result;
}

/**
 * Function returns array of result expected from previous function.
 *
 * @return array
 *   API result array
 */
function address_create_expectedresult() {

  $expectedResult = array(
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 2,
    'values' => array(
      '2' => array(
        'id' => '2',
        'contact_id' => '3',
        'location_type_id' => '6',
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
    ),
  );

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testCreateAddress"
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
