<?php
/**
 * Test Generated example demonstrating the CustomValue.get API.
 *
 * utilises field names
 *
 * @return array
 *   API result array
 */
function custom_value_get_example() {
  $params = [
    'id' => 2,
    'entity_id' => 2,
    'format.field_names' => 1,
  ];

  try{
    $result = civicrm_api3('CustomValue', 'get', $params);
  }
  catch (CiviCRM_API3_Exception $e) {
    // Handle error here.
    $errorMessage = $e->getMessage();
    $errorCode = $e->getErrorCode();
    $errorData = $e->getExtraParams();
    return [
      'error' => $errorMessage,
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
function custom_value_get_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 7,
    'values' => [
      'mySingleField' => [
        'entity_id' => '2',
        'latest' => 'value 1',
        'id' => 'mySingleField',
      ],
      'field_12' => [
        'entity_id' => '2',
        'latest' => 'value 3',
        'id' => 'field_12',
        '1' => 'value 2',
        '2' => 'value 3',
      ],
      'field_22' => [
        'entity_id' => '2',
        'latest' => '',
        'id' => 'field_22',
        '1' => 'warm beer',
        '2' => '',
      ],
      'field_32' => [
        'entity_id' => '2',
        'latest' => '',
        'id' => 'field_32',
        '1' => 'fl* w*',
        '2' => '',
      ],
      'field_13' => [
        'entity_id' => '2',
        'latest' => 'coffee',
        'id' => 'field_13',
        '1' => 'defaultValue',
        '2' => 'coffee',
      ],
      'field_23' => [
        'entity_id' => '2',
        'latest' => 'value 4',
        'id' => 'field_23',
        '1' => '',
        '2' => 'value 4',
      ],
      'field_33' => [
        'entity_id' => '2',
        'latest' => '',
        'id' => 'field_33',
        '1' => 'vegemite',
        '2' => '',
      ],
    ],
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testGetMultipleCustomValues"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/CustomValueTest.php
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
