<?php
/**
 * Test Generated example demonstrating the Contribution.create API.
 *
 * Demonstrates creating contribution with Soft Credit by passing in honor_contact_id.
 *
 * @return array
 *   API result array
 */
function contribution_create_example() {
  $params = [
    'contact_id' => 31,
    'receive_date' => '20120511',
    'total_amount' => '100',
    'financial_type_id' => 1,
    'non_deductible_amount' => '10',
    'fee_amount' => '5',
    'net_amount' => '95',
    'source' => 'SSF',
    'contribution_status_id' => 1,
    'honor_contact_id' => 32,
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
        'contact_id' => '31',
        'financial_type_id' => '1',
        'contribution_page_id' => '',
        'payment_instrument_id' => '4',
        'receive_date' => '20120511000000',
        'non_deductible_amount' => '10',
        'total_amount' => '100',
        'fee_amount' => '5',
        'net_amount' => '95',
        'trxn_id' => '',
        'invoice_id' => '',
        'invoice_number' => '',
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
* The test that created it is called "testCreateContributionWithHonoreeContact"
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
