<?php
/**
 * Test Generated example demonstrating the Contribution.get API.
 *
 * @return array
 *   API result array
 */
function contribution_get_example() {
  $params = [
    'contribution_id' => 1,
  ];

  try{
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
        'contact_type' => 'Individual',
        'contact_sub_type' => '',
        'sort_name' => 'Anderson, Anthony',
        'display_name' => 'Mr. Anthony Anderson II',
        'contribution_id' => '1',
        'currency' => 'USD',
        'contribution_recur_id' => '',
        'contribution_status_id' => '1',
        'contribution_campaign_id' => '',
        'payment_instrument_id' => '4',
        'receive_date' => '2010-01-20 00:00:00',
        'non_deductible_amount' => '10.00',
        'total_amount' => '100.00',
        'fee_amount' => '5.00',
        'net_amount' => '95.00',
        'trxn_id' => '23456',
        'invoice_id' => '78910',
        'invoice_number' => 'INV_1',
        'contribution_cancel_date' => '',
        'cancel_reason' => '',
        'receipt_date' => '',
        'thankyou_date' => '',
        'contribution_source' => 'SSF',
        'amount_level' => '',
        'is_test' => 0,
        'is_pay_later' => 0,
        'contribution_check_number' => '',
        'financial_account_id' => '1',
        'accounting_code' => '4200',
        'campaign_id' => '',
        'contribution_campaign_title' => '',
        'financial_type_id' => '1',
        'financial_type' => 'Donation',
        'contribution_note' => '',
        'contribution_batch' => '',
        'contribution_recur_status' => 'Completed',
        'payment_instrument' => 'Check',
        'contribution_status' => 'Completed',
        'check_number' => '',
        'instrument_id' => '4',
        'cancel_date' => '',
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
