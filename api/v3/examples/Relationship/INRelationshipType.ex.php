<?php

/**
 * @file
 */

/**
 * Test Generated example demonstrating the Relationship.get API.
 *
 * Demonstrates use of IN filter.
 *
 * @return array
 *   API result array
 */
function relationship_get_example() {
  $params = [
    'relationship_type_id' => [
      'IN' => [
        '0' => 56,
        '1' => 57,
      ],
    ],
  ];

  try {
    $result = civicrm_api3('Relationship', 'get', $params);
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
function relationship_get_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 2,
    'values' => [
      '2' => [
        'id' => '2',
        'contact_id_a' => '3',
        'contact_id_b' => '5',
        'relationship_type_id' => '56',
        'start_date' => '2013-07-29 00:00:00',
        'is_active' => '1',
        'is_permission_a_b' => 0,
        'is_permission_b_a' => 0,
      ],
      '3' => [
        'id' => '3',
        'contact_id_a' => '3',
        'contact_id_b' => '5',
        'relationship_type_id' => '57',
        'start_date' => '2013-07-29 00:00:00',
        'is_active' => '1',
        'is_permission_a_b' => 0,
        'is_permission_b_a' => 0,
      ],
    ],
  ];

  return $expectedResult;
}

/*
 * This example has been generated from the API test suite.
 * The test that created it is called "testGetTypeOperators"
 * and can be found at:
 * https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/RelationshipTest.php
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
