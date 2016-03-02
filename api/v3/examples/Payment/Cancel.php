<?php
/**
 * Test Generated example demonstrating the Payment.cancel API.
 *
 * @return array
 *   API result array
 */
function payment_cancel_example() {
  $params = array(
    'id' => 2,
    'check_permissions' => TRUE,
  );

  try{
    $result = civicrm_api3('Payment', 'cancel', $params);
  }
  catch (CiviCRM_API3_Exception $e) {
    // Handle error here.
    $errorMessage = $e->getMessage();
    $errorCode = $e->getErrorCode();
    $errorData = $e->getExtraParams();
    return array(
      'error' => $errorMessage,
      'error_code' => $errorCode,
      'error_data' => $errorData,
    );
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

  $expectedResult = array(
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 3,
    'values' => array(
      '3' => array(
        'id' => 3,
        'from_financial_account_id' => '7',
        'to_financial_account_id' => '6',
        'trxn_date' => '20160217204840',
        'total_amount' => '-150',
        'fee_amount' => '0.00',
        'net_amount' => '150.00',
        'currency' => 'USD',
        'is_payment' => 1,
        'trxn_id' => '',
        'trxn_result_code' => '',
        'status_id' => '7',
        'payment_processor_id' => '',
      ),
    ),
  );

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testCancelPayment"
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
