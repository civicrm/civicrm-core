<?php
/**
 * Test Generated example demonstrating the Country.create API.
 *
 * @return array
 *   API result array
 */
function country_create_example() {
  $params = [
    'name' => 'Made Up Land',
    'iso_code' => 'ZZ',
    'region_id' => 1,
  ];

  try{
    $result = civicrm_api3('Country', 'create', $params);
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
function country_create_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 1254,
    'values' => [
      '1254' => [
        'id' => '1254',
        'name' => 'Made Up Land',
        'iso_code' => 'ZZ',
        'country_code' => '',
        'address_format_id' => '',
        'idd_prefix' => '',
        'ndd_prefix' => '',
        'region_id' => '1',
        'is_province_abbreviated' => '',
      ],
    ],
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testCreateCountry"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/CountryTest.php
*
* You can see the outcome of the API tests at
* https://test.civicrm.org/job/CiviCRM-Core-Matrix/
*
* To Learn about the API read
* https://docs.civicrm.org/dev/en/latest/api/
*
* Browse the API on your own site with the API Explorer. It is in the main
* CiviCRM menu, under: Support > Development > API Explorer.
*
* Read more about testing here
* https://docs.civicrm.org/dev/en/latest/testing/
*
* API Standards documentation:
* https://docs.civicrm.org/dev/en/latest/framework/api-architecture/
*/
