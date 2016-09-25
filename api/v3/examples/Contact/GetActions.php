<?php
/**
 * Test Generated example demonstrating the Contact.getactions API.
 *
 * Getting the available actions for an entity.
 *
 * @return array
 *   API result array
 */
function contact_getactions_example() {
  $params = array();

  try{
    $result = civicrm_api3('Contact', 'getactions', $params);
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
function contact_getactions_expectedresult() {

  $expectedResult = array(
    'is_error' => 0,
    'version' => 3,
    'count' => 23,
    'values' => array(
      '0' => 'create',
      '1' => 'delete',
      '2' => 'example_action1',
      '3' => 'example_action2',
      '4' => 'get',
      '5' => 'getactions',
      '6' => 'getcount',
      '7' => 'getfields',
      '8' => 'getlist',
      '9' => 'getoptions',
      '10' => 'getquick',
      '11' => 'getrefcount',
      '12' => 'getsingle',
      '13' => 'getstat',
      '14' => 'getvalue',
      '15' => 'merge',
      '16' => 'proximity',
      '17' => 'replace',
      '18' => 'setvalue',
      '19' => 'type_create',
      '20' => 'type_delete',
      '21' => 'type_get',
      '22' => 'update',
    ),
    'deprecated' => array(
      'getquick' => 'The "getquick" action is deprecated in favor of "getlist".',
      'setvalue' => 'The "setvalue" action is deprecated. Use "create" with an id instead.',
      'update' => 'The "update" action is deprecated. Use "create" with an id instead.',
    ),
  );

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testGetActions"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/ContactTest.php
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
