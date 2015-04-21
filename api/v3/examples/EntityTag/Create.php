<?php
/**
 * Test Generated example demonstrating the EntityTag.create API.
 *
 * @return array
 *   API result array
 */
function entity_tag_create_example() {
  $params = array(
    'contact_id' => 3,
    'tag_id' => '6',
  );

  try{
    $result = civicrm_api3('EntityTag', 'create', $params);
  }
  catch (CiviCRM_API3_Exception $e) {
    // Handle error here.
    $errorMessage = $e->getMessage();
    $errorCode = $e->getErrorCode();
    $errorData = $e->getExtraParams();
    return array(
      'error' => $errorMessage,
      'error_code' => $errorCode,
      'error_data' => $errorData,
    );
  }

  return $result;
}

/**
 * Function returns array of result expected from previous function.
 *
 * @return array
 *   API result array
 */
function entity_tag_create_expectedresult() {

  $expectedResult = array(
    'is_error' => 0,
    'not_added' => 0,
    'added' => 1,
    'total_count' => 1,
  );

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testIndividualEntityTagGet"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/EntityTagTest.php
*
* You can see the outcome of the API tests at
* https://test.civicrm.org/job/CiviCRM-master-git/
*
* To Learn about the API read
* http://wiki.civicrm.org/confluence/display/CRMDOC/Using+the+API
*
* Browse the api on your own site with the api explorer
* http://MYSITE.ORG/path/to/civicrm/api
*
* Read more about testing here
* http://wiki.civicrm.org/confluence/display/CRM/Testing
*
* API Standards documentation:
* http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
*/
