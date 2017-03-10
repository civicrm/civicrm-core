<?php
/**
 * Test Generated example demonstrating the Pledge.get API.
 *
 * @return array
 *   API result array
 */
function pledge_get_example() {
  $params = array(
    'pledge_id' => 1,
  );

  try{
    $result = civicrm_api3('Pledge', 'get', $params);
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
function pledge_get_expectedresult() {

  $expectedResult = array(
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 1,
    'values' => array(
      '1' => array(
        'contact_id' => '5',
        'contact_type' => 'Individual',
        'contact_sub_type' => '',
        'sort_name' => 'Anderson, Anthony',
        'display_name' => 'Mr. Anthony Anderson II',
        'pledge_id' => '1',
        'pledge_amount' => '100.00',
        'pledge_create_date' => '2017-02-07 00:00:00',
        'pledge_start_date' => '2017-02-07 00:00:00',
        'pledge_status' => 'Pending',
        'pledge_total_paid' => '',
        'pledge_next_pay_date' => '2017-02-09 00:00:00',
        'pledge_next_pay_amount' => '20.00',
        'pledge_outstanding_amount' => '',
        'pledge_financial_type' => 'Donation',
        'pledge_contribution_page_id' => '',
        'pledge_frequency_interval' => '5',
        'pledge_frequency_unit' => 'year',
        'pledge_is_test' => 0,
        'pledge_campaign_id' => '',
        'pledge_currency' => 'USD',
        'id' => '1',
      ),
    ),
  );

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testGetPledge"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/PledgeTest.php
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
