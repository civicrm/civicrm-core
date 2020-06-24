<?php
/**
 * Test Generated example demonstrating the LocBlock.get API.
 *
 * Get entities and location block in 1 api call
 *
 * @return array
 *   API result array
 */
function loc_block_get_example() {
  $params = [
    'id' => 3,
    'return' => 'all',
  ];

  try{
    $result = civicrm_api3('LocBlock', 'get', $params);
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
function loc_block_get_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 3,
    'values' => [
      '3' => [
        'id' => '3',
        'address_id' => '3',
        'email_id' => '4',
        'phone_id' => '3',
        'phone_2_id' => '4',
        'address' => [
          'id' => '3',
          'location_type_id' => '1',
          'is_primary' => 0,
          'is_billing' => 0,
          'street_address' => '987654321',
          'manual_geo_code' => 0,
        ],
        'email' => [
          'id' => '4',
          'location_type_id' => '1',
          'email' => 'test2@loc.block',
          'is_primary' => 0,
          'is_billing' => 0,
          'on_hold' => 0,
          'is_bulkmail' => 0,
        ],
        'phone' => [
          'id' => '3',
          'location_type_id' => '1',
          'is_primary' => 0,
          'is_billing' => 0,
          'phone' => '987654321',
          'phone_numeric' => '987654321',
        ],
        'phone_2' => [
          'id' => '4',
          'location_type_id' => '1',
          'is_primary' => 0,
          'is_billing' => 0,
          'phone' => '456-7890',
          'phone_numeric' => '4567890',
        ],
      ],
    ],
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testCreateLocBlockEntities"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/LocBlockTest.php
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
