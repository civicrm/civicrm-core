<?php

/**
 * @file
 */

/**
 * Test Generated example demonstrating the OpenID.create API.
 *
 * @return array
 *   API result array
 */
function open_i_d_create_example() {
  $params = [
    'contact_id' => 5,
    'openid' => 'My OpenID handle',
    'sequential' => 1,
  ];

  try {
    $result = civicrm_api3('OpenID', 'create', $params);
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
function open_i_d_create_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 3,
    'values' => [
      '0' => [
        'id' => '3',
        'contact_id' => '5',
        'location_type_id' => '1',
        'openid' => 'My OpenID handle',
        'allowed_to_login' => '',
        'is_primary' => '1',
      ],
    ],
  ];

  return $expectedResult;
}

/*
 * This example has been generated from the API test suite.
 * The test that created it is called "testCreateOpenIDDefaultLocation"
 * and can be found at:
 * https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/OpenIDTest.php
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
