<?php
/**
 * Test Generated example demonstrating the Setting.getvalue API.
 *
 * Demonstrates getvalue action - intended for runtime use as better caching than get.
 *
 * @return array
 *   API result array
 */
function setting_getvalue_example() {
  $params = [
    'name' => 'petition_contacts',
    'group' => 'Campaign Preferences',
  ];

  try{
    $result = civicrm_api3('Setting', 'getvalue', $params);
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
function setting_getvalue_expectedresult() {

  $expectedResult = 'Petition Contacts';

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testGetValue"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/SettingTest.php
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
