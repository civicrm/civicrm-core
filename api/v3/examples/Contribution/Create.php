<?php
/**
 * Test Generated example demonstrating the Contribution.create API.
 *
 * @return array
 *   API result array
 */
function contribution_create_example() {
  $params = array(
    'contact_id' => 1,
    'receive_date' => '20120511',
    'total_amount' => '100',
    'financial_type_id' => 1,
    'contribution_page_id' => 1,
    'trxn_id' => 12345,
    'is_pay_later' => 1,
    'invoice_id' => 67890,
    'source' => 'SSF',
    'contribution_status_id' => 2,
  );

  try{
    $result = civicrm_api3('Contribution', 'create', $params);
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
function contribution_create_expectedresult() {

  $expectedResult = array(
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 1,
    'values' => array(
      '1' => array(
        'id' => '1',
        'contact_id' => '1',
        'financial_type_id' => '1',
        'contribution_page_id' => '1',
        'payment_instrument_id' => '4',
        'receive_date' => '20120511000000',
        'non_deductible_amount' => '',
        'total_amount' => '100',
        'fee_amount' => 0,
        'net_amount' => '100',
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
        'is_pay_later' => '1',
        'contribution_status_id' => '2',
        'address_id' => '',
        'check_number' => '',
        'campaign_id' => '',
        'creditnote_id' => '',
        'tax_amount' => '',
        'revenue_recognition_date' => '',
        'contribution_type_id' => '1',
        'invoice_number' => '',
      ),
    ),
  );

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testCreateContributionPayLaterOnline"
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
