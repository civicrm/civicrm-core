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
      'is_error' => 1,
      'error_message' => $errorMessage,
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
    'count' => 25,
    'values' => array(
      '0' => 'create',
      '1' => 'delete',
      '2' => 'duplicatecheck',
      '3' => 'example_action1',
      '4' => 'example_action2',
      '5' => 'get',
      '6' => 'getactions',
      '7' => 'getcount',
      '8' => 'getfield',
      '9' => 'getfields',
      '10' => 'getlist',
      '11' => 'getoptions',
      '12' => 'getquick',
      '13' => 'getrefcount',
      '14' => 'getsingle',
      '15' => 'getvalue',
      '16' => 'merge',
      '17' => 'proximity',
      '18' => 'replace',
      '19' => 'setvalue',
      '20' => 'type_create',
      '21' => 'type_delete',
      '22' => 'type_get',
      '23' => 'update',
      '24' => 'validate',
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
