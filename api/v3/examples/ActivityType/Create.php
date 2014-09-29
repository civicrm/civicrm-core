<?php
/**
 * Test Generated example of using activity_type create API
 * *
 */
function activity_type_create_example(){
$params = array(
  'weight' => '2',
  'label' => 'send out letters',
  'filter' => 0,
  'is_active' => 1,
  'is_optgroup' => 1,
  'is_default' => 0,
);

try{
  $result = civicrm_api3('activity_type', 'create', $params);
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
function activity_type_create_expectedresult(){

  $expectedResult = array(
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 764,
  'values' => array(
      '764' => array(
          'id' => '764',
          'option_group_id' => '2',
          'label' => 'send out letters',
          'value' => '49',
          'name' => 'send out letters',
          'grouping' => '',
          'filter' => 0,
          'is_default' => 0,
          'weight' => '2',
          'description' => '',
          'is_optgroup' => '1',
          'is_reserved' => '',
          'is_active' => '1',
          'component_id' => '',
          'domain_id' => '',
          'visibility_id' => '',
        ),
    ),
);

  return $expectedResult;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testActivityTypeCreate and can be found in
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/ActivityTypeTest.php
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
