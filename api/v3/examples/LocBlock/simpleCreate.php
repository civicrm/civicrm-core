<?php
/**
 * Test Generated example of using loc_block create API
 * Create locBlock with existing entities *
 */
function loc_block_create_example(){
$params = array(
  'address_id' => 2,
  'phone_id' => 2,
  'email_id' => 32,
);

try{
  $result = civicrm_api3('loc_block', 'create', $params);
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
function loc_block_create_expectedresult(){

  $expectedResult = array(
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 2,
  'values' => array(
      '2' => array(
          'id' => '2',
          'address_id' => '2',
          'email_id' => '32',
          'phone_id' => '2',
          'im_id' => '',
          'address_2_id' => '',
          'email_2_id' => '',
          'phone_2_id' => '',
          'im_2_id' => '',
        ),
    ),
);

  return $expectedResult;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testCreateLocBlock and can be found in
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/LocBlockTest.php
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
