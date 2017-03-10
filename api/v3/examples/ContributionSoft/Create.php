<?php
/**
 * Test Generated example demonstrating the ContributionSoft.create API.
 *
 * @return array
 *   API result array
 */
function contribution_soft_create_example() {
  $params = array(
    'contribution_id' => 6,
    'contact_id' => 19,
    'amount' => '10',
    'currency' => 'USD',
    'soft_credit_type_id' => 5,
  );

  try{
    $result = civicrm_api3('ContributionSoft', 'create', $params);
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
function contribution_soft_create_expectedresult() {

  $expectedResult = array(
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 5,
    'values' => array(
      '5' => array(
        'id' => '5',
        'contribution_id' => '6',
        'contact_id' => '19',
        'amount' => '10',
        'currency' => 'USD',
        'pcp_id' => '',
        'pcp_display_in_roll' => '',
        'pcp_roll_nickname' => '',
        'pcp_personal_note' => '',
        'soft_credit_type_id' => '5',
      ),
    ),
  );

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testCreateContributionSoft"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/ContributionSoftTest.php
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
