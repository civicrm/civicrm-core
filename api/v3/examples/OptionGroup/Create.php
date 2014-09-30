<?php
/**
 * Test Generated example of using option_group create API
 * *
 */
function option_group_create_example(){
$params = array(
  'sequential' => 1,
  'name' => 'civicrm_event.amount.560',
  'is_reserved' => 1,
  'is_active' => 1,
  'api.OptionValue.create' => array(
      'label' => 'workshop',
      'value' => 35,
      'is_default' => 1,
      'is_active' => 1,
      'format.only_id' => 1,
    ),
);

try{
  $result = civicrm_api3('option_group', 'create', $params);
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
function option_group_create_expectedresult(){

  $expectedResult = array(
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 89,
  'values' => array(
      '0' => array(
          'id' => '89',
          'name' => 'civicrm_event.amount.560',
          'title' => '',
          'description' => '',
          'is_reserved' => '1',
          'is_active' => '1',
          'is_locked' => '',
          'api.OptionValue.create' => 764,
        ),
    ),
);

  return $expectedResult;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testGetOptionCreateSuccess and can be found in
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/OptionGroupTest.php
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
