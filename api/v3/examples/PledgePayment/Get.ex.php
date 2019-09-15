<?php
/**
 * Test Generated example demonstrating the PledgePayment.get API.
 *
 * @return array
 *   API result array
 */
function pledge_payment_get_example() {
  $params = [];

  try{
    $result = civicrm_api3('PledgePayment', 'get', $params);
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
function pledge_payment_get_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 5,
    'values' => [
      '1' => [
        'id' => '1',
        'pledge_id' => '1',
        'scheduled_amount' => '20.00',
        'currency' => 'USD',
        'scheduled_date' => '20130728085413',
        'reminder_count' => 0,
        'status_id' => '2',
      ],
      '2' => [
        'id' => '2',
        'pledge_id' => '1',
        'scheduled_amount' => '20.00',
        'currency' => 'USD',
        'scheduled_date' => '20130728085413',
        'reminder_count' => 0,
        'status_id' => '2',
      ],
      '3' => [
        'id' => '3',
        'pledge_id' => '1',
        'scheduled_amount' => '20.00',
        'currency' => 'USD',
        'scheduled_date' => '20130728085413',
        'reminder_count' => 0,
        'status_id' => '2',
      ],
      '4' => [
        'id' => '4',
        'pledge_id' => '1',
        'scheduled_amount' => '20.00',
        'currency' => 'USD',
        'scheduled_date' => '20130728085413',
        'reminder_count' => 0,
        'status_id' => '2',
      ],
      '5' => [
        'id' => '5',
        'pledge_id' => '1',
        'scheduled_amount' => '20.00',
        'currency' => 'USD',
        'scheduled_date' => '20130728085413',
        'reminder_count' => 0,
        'status_id' => '2',
      ],
    ],
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testGetPledgePayment"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/PledgePaymentTest.php
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
