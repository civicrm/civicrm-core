<?php
/**
 * Test Generated example of using grant get API
 * *
 */
function grant_get_example(){
$params = array(
  'rationale' => 'Just Because',
);

try{
  $result = civicrm_api3('grant', 'get', $params);
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
function grant_get_expectedresult(){

  $expectedResult = array(
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 2,
  'values' => array(
      '2' => array(
          'id' => '2',
          'contact_id' => '4',
          'application_received_date' => '20130728084957',
          'decision_date' => '20130805000000',
          'grant_type_id' => '1',
          'amount_total' => '500.00',
          'currency' => 'USD',
          'rationale' => 'Just Because',
          'status_id' => '1',
        ),
    ),
);

  return $expectedResult;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testGetGrant and can be found in
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/GrantTest.php
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
