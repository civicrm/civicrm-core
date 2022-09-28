<?php
/**
 * Test Generated example demonstrating the Logging.revert API.
 *
 * @return array
 *   API result array
 */
function logging_revert_example() {
  $params = [
    'log_conn_id' => 'woot',
    'log_date' => '2019-08-20 19:22:48',
  ];

  try{
    $result = civicrm_api3('Logging', 'revert', $params);
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
function logging_revert_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'values' => 1,
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "/home/seamus/buildkit/build/47-test/sites/all/modules/civicrm/tests/phpunit/api/v3/LoggingTest.php"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/Revert
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
