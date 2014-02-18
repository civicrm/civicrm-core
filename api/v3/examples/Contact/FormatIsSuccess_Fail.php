<?php
/**
 * Test Generated example of using contact create API
 * This demonstrates use of the 'format.is_success' param.
    This param causes only the success or otherwise of the function to be returned as BOOLEAN *
 */
function contact_create_example(){
$params = array(
  'id' => 500,
  'format.is_success' => 1,
);

try{
  $result = civicrm_api3('contact', 'create', $params);
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
function contact_create_expectedresult(){

  $expectedResult = 0;

  return $expectedResult;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testContactCreateFormatIsSuccessFalse and can be found in
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/ContactTest.php
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
