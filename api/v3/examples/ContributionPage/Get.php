<?php
/**
 * Test Generated example of using contribution_page get API
 * *
 */
function contribution_page_get_example(){
$params = array(
  'amount' => '34567',
  'currency' => 'NZD',
  'financial_type_id' => 1,
);

try{
  $result = civicrm_api3('contribution_page', 'get', $params);
}
catch (CiviCRM_API3_Exception $e) {
  // handle error here
  $errorMessage = $e->getMessage();
  $errorCode = $e->getErrorCode();
  $errorData = $e->getExtraParams();
  return array('error' => $errorMessage, 'error_code' => $errorCode, 'error_data' => $errorData);
}

return $result;
}

/**
 * Function returns array of result expected from previous function
 */
function contribution_page_get_expectedresult(){

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
          'is_pay_later' => '1',
          'is_partial_payment' => 0,
          'is_allow_other_amount' => 0,
          'goal_amount' => '34567.00',
          'is_for_organization' => 0,
          'is_email_receipt' => 0,
          'is_active' => '1',
          'amount_block_is_active' => '1',
          'currency' => 'NZD',
          'is_share' => '1',
        ),
    ),
);

  return $expectedResult;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testGetContributionPageByAmount and can be found in
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/ContributionPageTest.php
*
* You can see the outcome of the API tests at
* https://test.civicrm.org/job/CiviCRM-master-git/
*
* To Learn about the API read
* http://wiki.civicrm.org/confluence/display/CRMDOC/Using+the+API
*
* Browse the api on your own site with the api explorer
* http://MYSITE.ORG/path/to/civicrm/api/explorer
*
* Read more about testing here
* http://wiki.civicrm.org/confluence/display/CRM/Testing
*
* API Standards documentation:
* http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
*/
