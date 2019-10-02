<?php
/**
 * Test Generated example demonstrating the ContributionRecur.get API.
 *
 * @return array
 *   API result array
 */
function contribution_recur_get_example() {
  $params = [
    'amount' => '500',
  ];

  try{
    $result = civicrm_api3('ContributionRecur', 'get', $params);
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
function contribution_recur_get_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 2,
    'values' => [
      '2' => [
        'id' => '2',
        'contact_id' => '4',
        'amount' => '500.00',
        'currency' => 'USD',
        'frequency_unit' => 'day',
        'frequency_interval' => '1',
        'installments' => '12',
        'start_date' => '2013-07-29 00:00:00',
        'create_date' => '20120130621222105',
        'modified_date' => '2012-11-14 16:02:35',
        'contribution_status_id' => '1',
        'is_test' => 0,
        'cycle_day' => '1',
        'failure_count' => 0,
        'auto_renew' => 0,
        'is_email_receipt' => '1',
      ],
    ],
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testGetContributionRecur"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/ContributionRecurTest.php
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
