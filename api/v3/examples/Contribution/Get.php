<?php
/**
 * Test Generated example of using contribution get API
 * *
 */
function contribution_get_example(){
$params = array(
  'contribution_id' => 1,
);

try{
  $result = civicrm_api3('contribution', 'get', $params);
}
catch (CiviCRM_API3_Exception $e) {
  // handle error here
  $errorMessage = $e->getMessage();
  $errorCode = $e->getErrorCode();
  $errorData = $e->getExtraParams();
  return array('error' => $errorMessage, 'error_code' => $errorCode, 'error_data' => $errorData);
}

return $result;
}

/**
 * Function returns array of result expected from previous function
 */
function contribution_get_expectedresult(){

  $expectedResult = array(
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 1,
  'values' => array(
      '1' => array(
          'contact_id' => '1',
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
          'is_test' => 0,
          'is_pay_later' => 0,
          'contribution_status_id' => '1',
          'check_number' => '',
          'contribution_campaign_id' => '',
          'financial_type_id' => '1',
          'financial_type' => 'Donation',
          'instrument_id' => '87',
          'payment_instrument' => 'Check',
          'product_id' => '',
          'product_name' => '',
          'sku' => '',
          'contribution_product_id' => '',
          'product_option' => '',
          'fulfilled_date' => '',
          'contribution_start_date' => '',
          'contribution_end_date' => '',
          'contribution_recur_id' => '',
          'financial_account_id' => '1',
          'accounting_code' => '4200',
          'contribution_note' => '',
          'contribution_batch' => '',
          'contribution_status' => 'Completed',
          'contribution_payment_instrument' => 'Check',
          'contribution_check_number' => '',
          'id' => '1',
          'contribution_type_id' => '1',
        ),
    ),
);

  return $expectedResult;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testGetContributionLegacyBehaviour and can be found in
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/ContributionTest.php
*
* You can see the outcome of the API tests at
* https://test.civicrm.org/job/CiviCRM-master-git/
*
* To Learn about the API read
* http://wiki.civicrm.org/confluence/display/CRMDOC/Using+the+API
*
* Browse the api on your own site with the api explorer
* http://MYSITE.ORG/path/to/civicrm/api/explorer
*
* Read more about testing here
* http://wiki.civicrm.org/confluence/display/CRM/Testing
*
* API Standards documentation:
* http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
*/
