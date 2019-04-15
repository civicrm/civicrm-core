<?php
/**
 * Test Generated example demonstrating the ContributionRecur.create API.
 *
 * @return array
 *   API result array
 */
function contribution_recur_create_example() {
  $params = [
    'contact_id' => 3,
    'installments' => '12',
    'frequency_interval' => '1',
    'amount' => '500',
    'contribution_status_id' => 1,
    'start_date' => '2012-01-01 00:00:00',
    'currency' => 'USD',
    'frequency_unit' => 'day',
  ];

  try{
    $result = civicrm_api3('ContributionRecur', 'create', $params);
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
function contribution_recur_create_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 1,
    'values' => [
      '1' => [
        'id' => '1',
        'contact_id' => '3',
        'amount' => '500',
        'currency' => 'USD',
        'frequency_unit' => 'day',
        'frequency_interval' => '1',
        'installments' => '12',
        'start_date' => '2013-07-29 00:00:00',
        'create_date' => '20120130621222105',
        'modified_date' => '2012-11-14 16:02:35',
        'cancel_date' => '',
        'end_date' => '',
        'processor_id' => '',
        'payment_token_id' => '',
        'trxn_id' => '',
        'invoice_id' => '',
        'contribution_status_id' => '1',
        'is_test' => '',
        'cycle_day' => '',
        'next_sched_contribution_date' => '',
        'failure_count' => '',
        'failure_retry_date' => '',
        'auto_renew' => '',
        'payment_processor_id' => '',
        'financial_type_id' => '',
        'payment_instrument_id' => '',
        'campaign_id' => '',
        'is_email_receipt' => '',
      ],
    ],
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testCreateContributionRecur"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/ContributionRecurTest.php
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
