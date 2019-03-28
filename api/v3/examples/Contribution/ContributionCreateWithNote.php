<?php
/**
 * Test Generated example demonstrating the Contribution.create API.
 *
 * Demonstrates creating contribution with Note Entity.
 *
 * @return array
 *   API result array
 */
function contribution_create_example() {
  $params = [
    'contact_id' => 22,
    'receive_date' => '2012-01-01',
    'total_amount' => '100',
    'financial_type_id' => 1,
    'payment_instrument_id' => 1,
    'non_deductible_amount' => '10',
    'fee_amount' => '50',
    'net_amount' => '90',
    'trxn_id' => 12345,
    'invoice_id' => 67890,
    'source' => 'SSF',
    'contribution_status_id' => 1,
    'note' => 'my contribution note',
  ];

  try{
    $result = civicrm_api3('Contribution', 'create', $params);
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
function contribution_create_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 1,
    'values' => [
      '1' => [
        'id' => '1',
        'contact_id' => '22',
        'financial_type_id' => '1',
        'contribution_page_id' => '',
        'payment_instrument_id' => '1',
        'receive_date' => '20120101000000',
        'non_deductible_amount' => '10',
        'total_amount' => '100',
        'fee_amount' => '50',
        'net_amount' => '90',
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
        'contribution_status_id' => '1',
        'address_id' => '',
        'check_number' => '',
        'campaign_id' => '',
        'creditnote_id' => '',
        'tax_amount' => '',
        'revenue_recognition_date' => '',
        'contribution_type_id' => '1',
      ],
    ],
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testCreateContributionWithNote"
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
