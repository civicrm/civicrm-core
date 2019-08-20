<?php
/**
 * Test Generated example demonstrating the Payment.cancel API.
 *
 * @return array
 *   API result array
 */
function payment_cancel_example() {
  $params = [
    'id' => 2,
    'check_permissions' => TRUE,
  ];

  try{
    $result = civicrm_api3('Payment', 'cancel', $params);
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
function payment_cancel_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 3,
    'values' => [
      '3' => [
        'id' => '3',
        'from_financial_account_id' => '7',
        'to_financial_account_id' => '6',
        'trxn_date' => '20190820192757',
        'total_amount' => '-150',
        'fee_amount' => '0.00',
        'net_amount' => '-150',
        'currency' => 'USD',
        'is_payment' => '1',
        'trxn_id' => '',
        'trxn_result_code' => '',
        'status_id' => '7',
        'payment_processor_id' => '',
      ],
    ],
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testCancelPayment"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/PaymentTest.php
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
