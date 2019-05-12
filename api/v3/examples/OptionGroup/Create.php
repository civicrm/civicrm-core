<?php
/**
 * Test Generated example demonstrating the OptionGroup.create API.
 *
 * @return array
 *   API result array
 */
function option_group_create_example() {
  $params = [
    'sequential' => 1,
    'name' => 'civicrm_event.amount.560',
    'is_reserved' => 1,
    'is_active' => 1,
    'api.OptionValue.create' => [
      'label' => 'workshop',
      'value' => 35,
      'is_default' => 1,
      'is_active' => 1,
      'format.only_id' => 1,
    ],
  ];

  try{
    $result = civicrm_api3('OptionGroup', 'create', $params);
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
function option_group_create_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 92,
    'values' => [
      '0' => [
        'id' => '92',
        'name' => 'civicrm_event.amount.560',
        'title' => '',
        'description' => '',
        'data_type' => '',
        'is_reserved' => '1',
        'is_active' => '1',
        'is_locked' => '',
        'api.OptionValue.create' => 849,
      ],
    ],
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testGetOptionCreateSuccess"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/OptionGroupTest.php
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
