<?php
/**
 * Test Generated example demonstrating the Pcp.get API.
 *
 * Demonstrates get + delete in the same call.
 *
 * @return array
 *   API result array
 */
function pcp_get_example() {
  $params = [
    'title' => 'Pcp title',
    'api.Pcp.delete' => 1,
  ];

  try{
    $result = civicrm_api3('Pcp', 'get', $params);
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
function pcp_get_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 2,
    'values' => [
      '3' => [
        'id' => '3',
        'contact_id' => '1',
        'status_id' => 0,
        'title' => 'Pcp title',
        'page_id' => '1',
        'page_type' => 'contribute',
        'pcp_block_id' => '1',
        'is_thermometer' => 0,
        'is_honor_roll' => 0,
        'currency' => 'USD',
        'is_active' => 0,
        'is_notify' => 0,
        'api.Pcp.delete' => [
          'is_error' => 0,
          'version' => 3,
          'count' => 1,
          'values' => 1,
        ],
      ],
      '5' => [
        'id' => '5',
        'contact_id' => '1',
        'status_id' => 0,
        'title' => 'Pcp title',
        'page_id' => '1',
        'page_type' => 'contribute',
        'pcp_block_id' => '1',
        'is_thermometer' => 0,
        'is_honor_roll' => 0,
        'currency' => 'USD',
        'is_active' => 0,
        'is_notify' => 0,
        'api.Pcp.delete' => [
          'is_error' => 0,
          'version' => 3,
          'count' => 1,
          'values' => 1,
        ],
      ],
    ],
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testGetPcpChainDelete"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/PcpTest.php
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
