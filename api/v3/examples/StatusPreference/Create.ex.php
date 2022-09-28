<?php
/**
 * Test Generated example demonstrating the StatusPreference.create API.
 *
 * @return array
 *   API result array
 */
function status_preference_create_example() {
  $params = [
    'name' => 'test_check',
    'domain_id' => 1,
    'hush_until' => '20151212',
    'ignore_severity' => 'cRItical',
    'check_info' => '',
  ];

  try{
    $result = civicrm_api3('StatusPreference', 'create', $params);
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
function status_preference_create_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 4,
    'values' => [
      '4' => [
        'id' => '4',
        'domain_id' => '1',
        'name' => 'test_check',
        'hush_until' => '20151212000000',
        'ignore_severity' => '5',
        'prefs' => '',
        'check_info' => '',
      ],
    ],
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testCreateSeverityByName"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/StatusPreferenceTest.php
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
