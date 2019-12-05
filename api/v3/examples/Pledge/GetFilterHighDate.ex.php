<?php
/**
 * Test Generated example demonstrating the Pledge.get API.
 *
 * demonstrates high date filter
 *
 * @return array
 *   API result array
 */
function pledge_get_example() {
  $params = [
    'pledge_start_date_high' => '20190818192828',
  ];

  try{
    $result = civicrm_api3('Pledge', 'get', $params);
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
function pledge_get_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 2,
    'values' => [
      '2' => [
        'contact_id' => '8',
        'contact_type' => 'Individual',
        'contact_sub_type' => '',
        'sort_name' => 'Anderson, Anthony',
        'display_name' => 'Mr. Anthony Anderson II',
        'pledge_id' => '2',
        'pledge_amount' => '100.00',
        'pledge_create_date' => '2019-08-20 00:00:00',
        'pledge_start_date' => '2018-03-02 00:00:00',
        'pledge_status' => 'Overdue',
        'pledge_total_paid' => '',
        'pledge_next_pay_date' => '2018-03-02 00:00:00',
        'pledge_next_pay_amount' => '20.00',
        'pledge_outstanding_amount' => '20.00',
        'pledge_financial_type' => 'Donation',
        'pledge_contribution_page_id' => '',
        'pledge_frequency_interval' => '5',
        'pledge_frequency_unit' => 'year',
        'pledge_is_test' => 0,
        'pledge_campaign_id' => '',
        'pledge_currency' => 'USD',
        'id' => '2',
      ],
    ],
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testPledgeGetReturnFilters"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/PledgeTest.php
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
