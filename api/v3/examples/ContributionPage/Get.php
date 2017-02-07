<?php
/**
 * Test Generated example demonstrating the ContributionPage.get API.
 *
 * @return array
 *   API result array
 */
function contribution_page_get_example() {
  $params = array(
    'currency' => 'NZD',
    'financial_type_id' => 1,
  );

  try{
    $result = civicrm_api3('ContributionPage', 'get', $params);
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
function contribution_page_get_expectedresult() {

  $expectedResult = array(
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 1,
    'values' => array(
      '1' => array(
        'id' => '1',
        'title' => 'Test Contribution Page',
        'financial_type_id' => '1',
        'is_credit_card_only' => 0,
        'is_monetary' => '1',
        'is_recur' => 0,
        'is_confirm_enabled' => '1',
        'is_recur_interval' => 0,
        'is_recur_installments' => 0,
        'adjust_recur_start_date' => 0,
        'is_pay_later' => '1',
        'is_partial_payment' => 0,
        'is_allow_other_amount' => 0,
        'goal_amount' => '34567.00',
        'is_email_receipt' => '1',
        'receipt_from_name' => 'Ego Freud',
        'receipt_from_email' => 'yourconscience@donate.com',
        'is_active' => '1',
        'amount_block_is_active' => '1',
        'currency' => 'NZD',
        'is_share' => '1',
        'is_billing_required' => 0,
        'contribution_type_id' => '1',
      ),
    ),
  );

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testGetBasicContributionPage"
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
