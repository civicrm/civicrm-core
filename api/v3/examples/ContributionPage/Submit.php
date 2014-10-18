<?php
/**
 * Test Generated example of using contribution_page Submit API
 * submit contribution page *
 */
function contribution_page_submit_example(){
$params = array(
  'price_3' => '',
  'id' => 1,
  'amount' => 10,
  'billing_first_name' => 'Billy',
  'billing_middle_name' => 'Goat',
  'billing_last_name' => 'Gruff',
  'email' => 'billy@goat.gruff',
  'selectMembership' => array(
      '0' => 1,
    ),
  'payment_processor' => 1,
  'credit_card_number' => '4111111111111111',
  'credit_card_type' => 'Visa',
  'credit_card_exp_date' => array(
      'M' => 9,
      'Y' => 2040,
    ),
  'cvv2' => 123,
  'is_recur' => 1,
  'frequency_interval' => 1,
  'frequency_unit' => 'month',
);

try{
  $result = civicrm_api3('contribution_page', 'Submit', $params);
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
function contribution_page_submit_expectedresult(){

  $expectedResult = array(
  'is_error' => 0,
  'version' => 3,
  'count' => 0,
  'values' => '',
);

  return $expectedResult;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testSubmitMembershipPriceSetPaymentPaymentProcessorRecurDelayed and can be found in
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
