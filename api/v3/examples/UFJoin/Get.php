<?php
/**
 * Test Generated example of using uf_join get API
 * *
 */
function uf_join_get_example(){
$params = array(
  'entity_table' => 'civicrm_contribution_page',
  'entity_id' => 1,
  'sequential' => 1,
);

try{
  $result = civicrm_api3('uf_join', 'get', $params);
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
function uf_join_get_expectedresult(){

  $expectedResult = array(
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 1,
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
* testGetUFJoinId and can be found in
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/UFJoinTest.php
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
