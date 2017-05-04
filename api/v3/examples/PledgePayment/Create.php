<?php
/**
 * Test Generated example demonstrating the PledgePayment.create API.
 *
 * @return array
 *   API result array
 */
function pledge_payment_create_example() {
  $params = array(
    'contact_id' => 1,
    'pledge_id' => 1,
    'contribution_id' => 1,
    'status_id' => 1,
    'actual_amount' => 20,
  );

  try{
    $result = civicrm_api3('PledgePayment', 'create', $params);
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
function pledge_payment_create_expectedresult() {

  $expectedResult = array(
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 1,
    'values' => array(
      '1' => array(
        'id' => '1',
        'pledge_id' => '1',
        'contribution_id' => '1',
        'scheduled_amount' => '',
        'actual_amount' => '20',
        'currency' => 'USD',
        'scheduled_date' => '',
        'reminder_date' => '',
        'reminder_count' => '',
        'status_id' => '1',
      ),
    ),
  );

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testCreatePledgePayment"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/PledgePaymentTest.php
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
