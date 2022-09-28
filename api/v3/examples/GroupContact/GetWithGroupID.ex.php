<?php

/**
 * @file
 */

/**
 * Test Generated example demonstrating the GroupContact.get API.
 *
 * Get all from group and display contacts.
 *
 * @return array
 *   API result array
 */
function group_contact_get_example() {
  $params = [
    'group_id' => 3,
    'api.group.get' => 1,
    'sequential' => 1,
  ];

  try {
    $result = civicrm_api3('GroupContact', 'get', $params);
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
function group_contact_get_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 2,
    'values' => [
      '0' => [
        'id' => '2',
        'group_id' => '3',
        'contact_id' => '4',
        'status' => 'Added',
        'api.group.get' => [
          'is_error' => 0,
          'version' => 3,
          'count' => 1,
          'id' => 3,
          'values' => [
            '0' => [
              'id' => '3',
              'name' => 'Test Group 1',
              'title' => 'New Test Group Created',
              'description' => 'New Test Group Created',
              'is_active' => '1',
              'visibility' => 'Public Pages',
              'group_type' => [
                '0' => '1',
                '1' => '2',
              ],
              'is_hidden' => 0,
              'is_reserved' => 0,
            ],
          ],
        ],
      ],
    ],
  ];

  return $expectedResult;
}

/*
 * This example has been generated from the API test suite.
 * The test that created it is called "testGetGroupID"
 * and can be found at:
 * https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/GroupContactTest.php
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
