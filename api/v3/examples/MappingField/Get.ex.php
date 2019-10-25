<?php
/**
 * Test Generated example demonstrating the MappingField.get API.
 *
 * @return array
 *   API result array
 */
function mapping_field_get_example() {
  $params = [
    'mapping_id' => 2,
    'name' => 'last_name',
    'contact_type' => 'Individual',
    'column_number' => 2,
    'grouping' => 1,
  ];

  try{
    $result = civicrm_api3('MappingField', 'get', $params);
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
function mapping_field_get_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 2,
    'values' => [
      '2' => [
        'id' => '2',
        'mapping_id' => '2',
        'name' => 'last_name',
        'contact_type' => 'Individual',
        'column_number' => '2',
        'grouping' => '1',
      ],
    ],
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testGetMappingField"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/MappingFieldTest.php
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
