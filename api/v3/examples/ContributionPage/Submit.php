<?php
/**
 * Test Generated example demonstrating the ContributionPage.submit API.
 *
 * submit contribution page
 *
 * @return array
 *   API result array
 */
function contribution_page_submit_example() {
  $params = [
    'id' => 1,
    'pledge_amount' => [
      '2' => 1,
    ],
    'billing_first_name' => 'Billy',
    'billing_middle_name' => 'Goat',
    'billing_last_name' => 'Gruff',
    'email' => 'billy@goat.gruff',
    'payment_processor_id' => 1,
    'credit_card_number' => '4111111111111111',
    'credit_card_type' => 'Visa',
    'credit_card_exp_date' => [
      'M' => 9,
      'Y' => 2040,
    ],
    'cvv2' => 123,
    'pledge_id' => '1',
    'cid' => '83',
    'contact_id' => '83',
    'amount' => '100',
    'is_pledge' => TRUE,
    'pledge_block_id' => 2,
  ];

  try{
    $result = civicrm_api3('ContributionPage', 'submit', $params);
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
function contribution_page_submit_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 0,
    'values' => '',
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testSubmitPledgePayment"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/ContributionPageTest.php
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
