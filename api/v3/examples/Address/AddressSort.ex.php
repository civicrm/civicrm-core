<?php

/**
 * @file
 */

/**
 * Test Generated example demonstrating the Address.get API.
 *
 * Demonstrates Use of sort filter.
 *
 * @return array
 *   API result array
 */
function address_get_example() {
  $params = [
    'options' => [
      'sort' => 'street_address DESC',
      'limit' => 2,
    ],
    'sequential' => 1,
    'return' => 'street_address',
  ];

  try {
    $result = civicrm_api3('Address', 'get', $params);
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
function address_get_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 2,
    'values' => [
      '0' => [
        'id' => '3',
        'street_address' => 'yzy',
      ],
      '1' => [
        'id' => '2',
        'street_address' => 'Ambachtstraat 23',
      ],
    ],
  ];

  return $expectedResult;
}

/*
 * This example has been generated from the API test suite.
 * The test that created it is called "testGetAddressSort"
 * and can be found at:
 * https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/AddressTest.php
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
