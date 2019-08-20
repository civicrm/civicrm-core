<?php
/**
 * Test Generated example demonstrating the Grant.create API.
 *
 * @return array
 *   API result array
 */
function grant_create_example() {
  $params = [
    'contact_id' => 3,
    'application_received_date' => 'now',
    'decision_date' => 'next Monday',
    'amount_total' => '500',
    'status_id' => 1,
    'rationale' => 'Just Because',
    'currency' => 'USD',
    'grant_type_id' => 1,
  ];

  try{
    $result = civicrm_api3('Grant', 'create', $params);
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
function grant_create_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 1,
    'values' => [
      '1' => [
        'id' => '1',
        'contact_id' => '3',
        'application_received_date' => '20130728084957',
        'decision_date' => '20130805000000',
        'money_transfer_date' => '',
        'grant_due_date' => '',
        'grant_report_received' => '',
        'grant_type_id' => '1',
        'amount_total' => '500',
        'amount_requested' => '',
        'amount_granted' => '',
        'currency' => 'USD',
        'rationale' => 'Just Because',
        'status_id' => '1',
        'financial_type_id' => '',
      ],
    ],
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testCreateGrant"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/GrantTest.php
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
