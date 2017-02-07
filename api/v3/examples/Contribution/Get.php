<?php
/**
 * Test Generated example demonstrating the Contribution.get API.
 *
 * @return array
 *   API result array
 */
function contribution_get_example() {
  $params = array(
    'contribution_id' => 1,
  );

  try{
    $result = civicrm_api3('Contribution', 'get', $params);
  }
  catch (CiviCRM_API3_Exception $e) {
    // Handle error here.
    $errorMessage = $e->getMessage();
    $errorCode = $e->getErrorCode();
    $errorData = $e->getExtraParams();
    return array(
      'is_error' => 1,
      'error_message' => $errorMessage,
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
function contribution_get_expectedresult() {

  $expectedResult = array(
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 1,
    'values' => array(
      '1' => array(
        'contact_id' => '3',
        'contact_type' => 'Individual',
        'contact_sub_type' => '',
        'sort_name' => 'Anderson, Anthony',
        'display_name' => 'Mr. Anthony Anderson II',
        'contribution_id' => '1',
        'currency' => 'USD',
        'receive_date' => '2010-01-20 00:00:00',
        'non_deductible_amount' => '10.00',
        'total_amount' => '100.00',
        'fee_amount' => '5.00',
        'net_amount' => '95.00',
        'trxn_id' => '23456',
        'invoice_id' => '78910',
        'cancel_date' => '',
        'cancel_reason' => '',
        'receipt_date' => '',
        'thankyou_date' => '',
        'contribution_source' => 'SSF',
        'amount_level' => '',
        'contribution_recur_id' => '',
        'is_test' => 0,
        'is_pay_later' => 0,
        'contribution_status_id' => '1',
        'contribution_check_number' => '',
        'contribution_campaign_id' => '',
        'financial_type_id' => '1',
        'financial_type' => 'Donation',
        'product_id' => '',
        'product_name' => '',
        'sku' => '',
        'contribution_product_id' => '',
        'product_option' => '',
        'fulfilled_date' => '',
        'contribution_start_date' => '',
        'contribution_end_date' => '',
        'financial_account_id' => '1',
        'accounting_code' => '4200',
        'campaign_id' => '',
        'contribution_campaign_title' => '',
        'contribution_note' => '',
        'contribution_batch' => '',
        'contribution_status' => 'Completed',
        'payment_instrument' => 'Check',
        'payment_instrument_id' => '4',
        'instrument_id' => '4',
        'check_number' => '',
        'id' => '1',
        'contribution_type_id' => '1',
      ),
    ),
  );

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testGetContribution"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/ContributionTest.php
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
