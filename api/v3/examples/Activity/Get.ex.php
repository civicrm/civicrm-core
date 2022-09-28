<?php

/**
 * @file
 */

/**
 * Test Generated example demonstrating the Activity.get API.
 *
 * @return array
 *   API result array
 */
function activity_get_example() {
  $params = [
    'activity_type_id' => 9999,
    'sequential' => 1,
    'return.custom_1' => 1,
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
    'id' => 1,
    'values' => [
      '0' => [
        'id' => '1',
        'activity_type_id' => '9999',
        'subject' => 'test activity type id',
        'activity_date_time' => '2011-06-02 14:36:13',
        'duration' => '120',
        'location' => 'Pennsylvania',
        'details' => 'a test activity',
        'status_id' => '2',
        'priority_id' => '1',
        'is_test' => 0,
        'is_auto' => 0,
        'is_current_revision' => '1',
        'is_deleted' => 0,
        'is_star' => 0,
        'created_date' => '2013-07-28 08:49:19',
        'modified_date' => '2012-11-14 16:02:35',
        'custom_1' => 'custom string',
        'source_contact_id' => '1',
        'source_contact_name' => 'Mr. Anthony Anderson II',
        'source_contact_sort_name' => 'Anderson, Anthony',
        'custom_1_1' => 'custom string',
      ],
    ],
  ];

  return $expectedResult;
}

/*
 * This example has been generated from the API test suite.
 * The test that created it is called "testActivityGetGoodIDCustom"
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
