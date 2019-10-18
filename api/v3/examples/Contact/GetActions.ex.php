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
    'count' => 29,
    'values' => [
      '0' => 'create',
      '1' => 'delete',
      '2' => 'duplicatecheck',
      '3' => 'example_action1',
      '4' => 'example_action2',
      '5' => 'get',
      '6' => 'get_merge_conflicts',
      '7' => 'getactions',
      '8' => 'getcount',
      '9' => 'getfield',
      '10' => 'getfields',
      '11' => 'getlist',
      '12' => 'getmergedfrom',
      '13' => 'getmergedto',
      '14' => 'getoptions',
      '15' => 'getquick',
      '16' => 'getrefcount',
      '17' => 'getsingle',
      '18' => 'getunique',
      '19' => 'getvalue',
      '20' => 'merge',
      '21' => 'proximity',
      '22' => 'replace',
      '23' => 'setvalue',
      '24' => 'type_create',
      '25' => 'type_delete',
      '26' => 'type_get',
      '27' => 'update',
      '28' => 'validate',
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
* https://test.civicrm.org/job/CiviCRM-Core-Matrix/
*
* To Learn about the API read
* https://docs.civicrm.org/dev/en/latest/api/
*
* Browse the API on your own site with the API Explorer. It is in the main
* CiviCRM menu, under: Support > Development > API Explorer.
*
* Read more about testing here
* https://docs.civicrm.org/dev/en/latest/testing/
*
* API Standards documentation:
* https://docs.civicrm.org/dev/en/latest/framework/api-architecture/
*/
