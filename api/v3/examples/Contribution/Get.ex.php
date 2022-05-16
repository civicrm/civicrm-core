<?php

/**
 * @file
 */

/**
 * Test Generated example demonstrating the Contribution.get API.
 *
 * @return array
 *   API result array
 */
function contribution_get_example() {
  $params = [
    'contribution_id' => 1,
    'return' => [
      '0' => 'invoice_number',
      '1' => 'contribution_source',
      '2' => 'contact_id',
      '3' => 'receive_date',
      '4' => 'total_amount',
      '5' => 'financial_type_id',
      '6' => 'non_deductible_amount',
      '7' => 'fee_amount',
      '8' => 'net_amount',
      '9' => 'trxn_id',
      '10' => 'invoice_id',
      '11' => 'source',
      '12' => 'contribution_status_id',
    ],
  ];

  try {
    $result = civicrm_api3('Contribution', 'get', $params);
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
function contribution_get_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 1,
    'values' => [
      '1' => [
        'contact_id' => '3',
        'contribution_recur_id' => '',
        'contribution_status_id' => '1',
        'contribution_id' => '1',
        'financial_type_id' => '1',
        'receive_date' => '2010-01-20 00:00:00',
        'non_deductible_amount' => '10.00',
        'total_amount' => '100.00',
        'fee_amount' => '5.00',
        'net_amount' => '95.00',
        'trxn_id' => '23456',
        'invoice_id' => '78910',
        'invoice_number' => 'INV_1',
        'contribution_source' => 'SSF',
        'contribution_recur_status' => 'Completed',
        'contribution_status' => 'Completed',
        'id' => '1',
        'contribution_type_id' => '1',
      ],
    ],
  ];

  return $expectedResult;
}

/*
 * This example has been generated from the API test suite.
 * The test that created it is called "testGetContribution"
 * and can be found at:
 * https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/ContributionTest.php
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
