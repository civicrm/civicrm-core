<?php
/**
 * Test Generated example demonstrating the Payment.create API.
 *
 * Payment with line item
 *
 * @return array
 *   API result array
 */
function payment_create_example() {
  $params = [
    'contribution_id' => 1,
    'total_amount' => 50,
    'line_item' => [
      '0' => [
        '1' => 10,
      ],
      '1' => [
        '2' => 40,
      ],
    ],
  ];

  try{
    $result = civicrm_api3('Payment', 'create', $params);
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
function payment_create_expectedresult() {

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
        'trxn_date' => '20170207024648',
        'total_amount' => '50',
        'fee_amount' => '',
        'net_amount' => '50',
        'currency' => 'USD',
        'is_payment' => '1',
        'trxn_id' => '',
        'trxn_result_code' => '',
        'status_id' => '1',
        'payment_processor_id' => '',
      ],
    ],
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testCreatePaymentLineItems"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/PaymentTest.php
*
* You can see the outcome of the API tests at
* https://test.civicrm.org/job/CiviCRM-master-git/
*
* To Learn about the API read
* http://wiki.civicrm.org/confluence/display/CRMDOC/Using+the+API
*
* Browse the api on your own site with the api explorer
* http://MYSITE.ORG/path/to/civicrm/api
*
* Read more about testing here
* http://wiki.civicrm.org/confluence/display/CRM/Testing
*
* API Standards documentation:
* http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
*/
