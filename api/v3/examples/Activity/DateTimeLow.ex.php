<?php

/**
 * @file
 */

/**
 * Test Generated example demonstrating the Activity.get API.
 *
 * Demonstrates _low filter (at time of writing doesn't work if contact_id is set.
 *
 * @return array
 *   API result array
 */
function activity_get_example() {
  $params = [
    'filter.activity_date_time_low' => '20120101000000',
    'sequential' => 1,
    'return' => 'activity_date_time',
  ];

  try {
    $result = civicrm_api3('Activity', 'get', $params);
  }
  catch (CRM_Core_Exception $e) {
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
function activity_get_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 2,
    'values' => [
      '0' => [
        'id' => '2',
        'activity_date_time' => '2012-02-16 00:00:00',
        'source_contact_id' => '1',
        'source_contact_name' => 'Mr. Anthony Anderson II',
        'source_contact_sort_name' => 'Anderson, Anthony',
      ],
    ],
  ];

  return $expectedResult;
}

/*
 * This example has been generated from the API test suite.
 * The test that created it is called "testGetFilterMaxDate"
 * and can be found at:
 * https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/ActivityTest.php
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
