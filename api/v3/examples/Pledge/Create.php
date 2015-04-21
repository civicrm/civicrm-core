<?php
/**
 * Test Generated example demonstrating the Pledge.create API.
 *
 * @return array
 *   API result array
 */
function pledge_create_example() {
  $params = array(
    'contact_id' => 11,
    'pledge_create_date' => '20150222',
    'start_date' => '20150222',
    'scheduled_date' => '20150224',
    'amount' => '100',
    'pledge_status_id' => '2',
    'pledge_financial_type_id' => '1',
    'pledge_original_installment_amount' => 20,
    'frequency_interval' => 5,
    'frequency_unit' => 'year',
    'frequency_day' => 15,
    'installments' => 5,
    'sequential' => 1,
  );

  try{
    $result = civicrm_api3('Pledge', 'create', $params);
  }
  catch (CiviCRM_API3_Exception $e) {
    // Handle error here.
    $errorMessage = $e->getMessage();
    $errorCode = $e->getErrorCode();
    $errorData = $e->getExtraParams();
    return array(
      'error' => $errorMessage,
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
function pledge_create_expectedresult() {

  $expectedResult = array(
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 1,
    'values' => array(
      '0' => array(
        'id' => '1',
        'contact_id' => '11',
        'financial_type_id' => '1',
        'contribution_page_id' => '',
        'amount' => '100',
        'original_installment_amount' => '20',
        'currency' => 'USD',
        'frequency_unit' => 'year',
        'frequency_interval' => '5',
        'frequency_day' => '15',
        'installments' => '5',
        'start_date' => '2013-07-29 00:00:00',
        'create_date' => '20120130621222105',
        'acknowledge_date' => '',
        'modified_date' => '',
        'cancel_date' => '',
        'end_date' => '',
        'max_reminders' => '',
        'initial_reminder_day' => '',
        'additional_reminder_day' => '',
        'status_id' => '2',
        'is_test' => '',
        'campaign_id' => '',
      ),
    ),
  );

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testCreatePledge"
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
