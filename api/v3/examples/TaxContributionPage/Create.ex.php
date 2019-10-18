<?php
/**
 * Test Generated example of using tax_contribution_page create API.
 *
 * @return array
 *   API result array
 */
function tax_contribution_page_create_example() {
  $params = [
    'contact_id' => 1,
    'receive_date' => '20120511',
    'total_amount' => '100',
    'financial_type_id' => 11,
    'contribution_page_id' => 1,
    'trxn_id' => 12345,
    'invoice_id' => 67890,
    'source' => 'SSF',
    'contribution_status_id' => 2,
  ];

  try{
    $result = civicrm_api3('tax_contribution_page', 'create', $params);
  }
  catch (CiviCRM_API3_Exception $e) {
    // Handle error here.
    $errorMessage = $e->getMessage();
    $errorCode = $e->getErrorCode();
    $errorData = $e->getExtraParams();
    return [
      'error' => $errorMessage,
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
function tax_contribution_page_create_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 1,
    'values' => [
      '1' => [
        'id' => '1',
        'contact_id' => '1',
        'financial_type_id' => '11',
        'contribution_page_id' => '1',
        'payment_instrument_id' => '4',
        'receive_date' => '20120511000000',
        'non_deductible_amount' => '',
        'total_amount' => '120',
        'fee_amount' => 0,
        'net_amount' => '120',
        'trxn_id' => '12345',
        'invoice_id' => '67890',
        'currency' => 'USD',
        'cancel_date' => '',
        'cancel_reason' => '',
        'receipt_date' => '',
        'thankyou_date' => '',
        'source' => 'SSF',
        'amount_level' => '',
        'contribution_recur_id' => '',
        'is_test' => '',
        'is_pay_later' => '',
        'contribution_status_id' => '2',
        'address_id' => '',
        'check_number' => '',
        'campaign_id' => '',
        'creditnote_id' => '',
        'tax_amount' => '20',
        'contribution_type_id' => '11',
      ],
    ],
  ];

  return $expectedResult;
}

/**
* This example has been generated from the API test suite.
* The test that created it is called
* testCreateContributionPendingOnline
* and can be found in
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/TaxContributionPageTest.php
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
