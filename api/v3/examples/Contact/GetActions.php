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
  $params = [];

  try{
    $result = civicrm_api3('Contact', 'getactions', $params);
  }
  catch (CiviCRM_API3_Exception $e) {
    // Handle error here.
    $errorMessage = $e->getMessage();
    $errorCode = $e->getErrorCode();
    $errorData = $e->getExtraParams();
    return [
      'is_error' => 1,
      'error_message' => $errorMessage,
      'error_code' => $errorCode,
      'error_data' => $errorData,
    ];
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

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 20,
    'values' => [
      '0' => 'create',
      '1' => 'delete',
      '2' => 'duplicatecheck',
      '3' => 'get',
      '4' => 'getactions',
      '5' => 'getcount',
      '6' => 'getfield',
      '7' => 'getfields',
      '8' => 'getlist',
      '9' => 'getoptions',
      '10' => 'getquick',
      '11' => 'getrefcount',
      '12' => 'getsingle',
      '13' => 'getvalue',
      '14' => 'merge',
      '15' => 'proximity',
      '16' => 'replace',
      '17' => 'setvalue',
      '18' => 'update',
      '19' => 'validate',
    ],
    'deprecated' => [
      'getquick' => 'The "getquick" action is deprecated in favor of "getlist".',
      'setvalue' => 'The "setvalue" action is deprecated. Use "create" with an id instead.',
      'update' => 'The "update" action is deprecated. Use "create" with an id instead.',
    ],
  ];

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
