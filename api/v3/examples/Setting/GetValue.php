<?php
/**
 * Test Generated example of using setting getvalue API
 * Demonstrates getvalue action - intended for runtime use as better caching than get *
 */
function setting_getvalue_example(){
$params = array(
  'name' => 'petition_contacts',
  'group' => 'Campaign Preferences',
);

try{
  $result = civicrm_api3('setting', 'getvalue', $params);
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
function setting_getvalue_expectedresult(){

  $expectedResult = 'Petition Contacts';

  return $expectedResult;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testGetValue and can be found in
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/SettingTest.php
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
