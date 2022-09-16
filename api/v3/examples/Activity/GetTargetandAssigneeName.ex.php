<?php

/**
 * @file
 */

/**
 * Test Generated example demonstrating the Activity.getsingle API.
 *
 * Demonstrates retrieving activity target & source contact names.
 *
 * @return array
 *   API result array
 */
function activity_getsingle_example() {
  $params = [
    'id' => 1,
    'return' => [
      '0' => 'source_contact_name',
      '1' => 'target_contact_name',
      '2' => 'assignee_contact_name',
      '3' => 'subject',
    ],
  ];

  try {
    $result = civicrm_api3('Activity', 'getsingle', $params);
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
function activity_getsingle_expectedresult() {

  $expectedResult = [
    'id' => '1',
    'subject' => 'Make-it-Happen Meeting',
    'assignee_contact_id' => [],
    'assignee_contact_name' => [
      '5' => 'C Shore',
    ],
    'assignee_contact_sort_name' => [
      '5' => 'Shore, C',
    ],
    'source_contact_id' => '6',
    'source_contact_name' => 'D Bug',
    'source_contact_sort_name' => 'Bug, D',
    'target_contact_id' => [
      '1' => '4',
    ],
    'target_contact_name' => [
      '3' => 'A Cat',
      '4' => 'B Good',
    ],
    'target_contact_sort_name' => [
      '3' => 'Cat, A',
      '4' => 'Good, B',
    ],
  ];

  return $expectedResult;
}

/*
 * This example has been generated from the API test suite.
 * The test that created it is called "testActivityReturnTargetAssigneeName"
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
