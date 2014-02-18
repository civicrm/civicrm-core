<?php
/**
 * Test Generated example of using membership_status get API
 * *
 */
function membership_status_get_example(){
$params = array(
  'name' => 'test status',
);

try{
  $result = civicrm_api3('membership_status', 'get', $params);
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
function membership_status_get_expectedresult(){

  $expectedResult = array(
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 9,
  'values' => array(
      '9' => array(
          'id' => '9',
          'name' => 'test status',
          'label' => 'test status',
          'start_event' => 'start_date',
          'end_event' => 'end_date',
          'is_current_member' => '1',
          'is_admin' => 0,
          'is_default' => 0,
          'is_active' => '1',
          'is_reserved' => 0,
        ),
    ),
);

  return $expectedResult;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testGet and can be found in
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/MembershipStatusTest.php
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
