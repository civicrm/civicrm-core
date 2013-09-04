<?php
/**
 * Test Generated example of using uf_join create API
 * *
 */
function uf_join_create_example(){
$params = array(
  'module' => 'CiviContribute',
  'entity_table' => 'civicrm_contribution_page',
  'entity_id' => 1,
  'weight' => 1,
  'uf_group_id' => 11,
  'is_active' => 1,
  'sequential' => 1,
);

try{
  $result = civicrm_api3('uf_join', 'create', $params);
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
function uf_join_create_expectedresult(){

  $expectedResult = array(
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 0,
  'values' => array(
      '0' => array(
          'id' => '1',
          'is_active' => '1',
          'module' => 'CiviContribute',
          'entity_table' => 'civicrm_contribution_page',
          'entity_id' => '1',
          'weight' => '1',
          'uf_group_id' => '11',
        ),
    ),
);

  return $expectedResult;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testCreateUFJoin and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/UFJoinTest.php
*
* You can see the outcome of the API tests at
* http://tests.dev.civicrm.org/trunk/results-api_v3
*
* To Learn about the API read
* http://book.civicrm.org/developer/current/techniques/api/
*
* and review the wiki at
* http://wiki.civicrm.org/confluence/display/CRMDOC/CiviCRM+Public+APIs
*
* Read more about testing here
* http://wiki.civicrm.org/confluence/display/CRM/Testing
*
* API Standards documentation:
* http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
*/