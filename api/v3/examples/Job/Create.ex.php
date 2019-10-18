<?php
/**
 * Test Generated example demonstrating the Job.create API.
 *
 * @return array
 *   API result array
 */
function job_create_example() {
  $params = [
    'sequential' => 1,
    'name' => 'API_Test_Job',
    'description' => 'A long description written by hand in cursive',
    'run_frequency' => 'Daily',
    'api_entity' => 'ApiTestEntity',
    'api_action' => 'apitestaction',
    'parameters' => 'Semi-formal explanation of runtime job parameters',
    'is_active' => 1,
  ];

  try{
    $result = civicrm_api3('Job', 'create', $params);
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
function job_create_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 30,
    'values' => [
      '0' => [
        'id' => '30',
        'domain_id' => '1',
        'run_frequency' => 'Daily',
        'last_run' => '',
        'scheduled_run_date' => '',
        'name' => 'API_Test_Job',
        'description' => 'A long description written by hand in cursive',
        'api_entity' => 'ApiTestEntity',
        'api_action' => 'apitestaction',
        'parameters' => 'Semi-formal explanation of runtime job parameters',
        'is_active' => '1',
      ],
    ],
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testCreate"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/JobTest.php
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
