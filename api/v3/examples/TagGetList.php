<?php
/**
 * Test Generated example of using tag getlist API
 * *
 */
function tag_getlist_example(){
$params = array(
  'input' => 'New Tag3',
);

try{
  $result = civicrm_api3('tag', 'getlist', $params);
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
function tag_getlist_expectedresult(){

  $expectedResult = array(
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 0,
  'values' => array(
      '0' => array(
          'id' => '6',
          'label' => 'New Tag3',
          'description' => 'This is description for Our New Tag ',
          'image' => '',
        ),
    ),
  'more_results' => '',
  'page_num' => 1,
);

  return $expectedResult;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testTagGetList and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/TagTest.php
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
