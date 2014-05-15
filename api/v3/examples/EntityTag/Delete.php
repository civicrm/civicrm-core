<?php
/**
 * Test Generated example of using entity_tag delete API
 * *
 */
function entity_tag_delete_example(){
$params = array(
  'contact_id_h' => 37,
  'tag_id' => '1',
);

try{
  $result = civicrm_api3('entity_tag', 'delete', $params);
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
function entity_tag_delete_expectedresult(){

  $expectedResult = array(
  'is_error' => 0,
  'not_removed' => 0,
  'removed' => 1,
  'total_count' => 1,
);

  return $expectedResult;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testEntityTagDeleteHH and can be found in
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/EntityTagTest.php
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
