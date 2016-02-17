<?php
/**
 * Test Generated example demonstrating the Order.cancel API.
 *
 * @return array
 *   API result array
 */
function order_cancel_example() {
  $params = array(
    'contribution_id' => 1,
  );

  try{
    $result = civicrm_api3('Order', 'cancel', $params);
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
function order_cancel_expectedresult() {

  $expectedResult = array(
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 1,
    'values' => array(
      '1' => array(
        'id' => '1',
        'contact_id' => '14',
        'financial_type_id' => '1',
        'contribution_page_id' => '',
        'payment_instrument_id' => '4',
        'receive_date' => '2010-01-20 00:00:00',
        'non_deductible_amount' => '0.00',
        'total_amount' => '100.00',
        'fee_amount' => '0.00',
        'net_amount' => '100.00',
        'trxn_id' => '',
        'invoice_id' => '',
        'currency' => 'USD',
        'cancel_date' => '',
        'cancel_reason' => '',
        'receipt_date' => '',
        'thankyou_date' => '',
        'source' => '',
        'amount_level' => '',
        'contribution_recur_id' => '',
        'is_test' => 0,
        'is_pay_later' => 0,
        'contribution_status_id' => '3',
        'address_id' => '',
        'check_number' => '',
        'campaign_id' => '',
        'creditnote_id' => 'CN_1',
        'tax_amount' => '',
        'contribution_type_id' => '1',
      ),
    ),
  );

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testCancelOrder"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/OrderTest.php
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
