<?php
/**
 * Test Generated example demonstrating the OptionValue.getsingle API.
 *
 * Demonstrates use of Sort param (available in many api functions). Also, getsingle.
 *
 * @return array
 *   API result array
 */
function option_value_getsingle_example() {
  $params = [
    'option_group_id' => 1,
    'options' => [
      'sort' => 'label DESC',
      'limit' => 1,
    ],
  ];

  try{
    $result = civicrm_api3('OptionValue', 'getsingle', $params);
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
function option_value_getsingle_expectedresult() {

  $expectedResult = [
    'id' => '4',
    'option_group_id' => '1',
    'label' => 'SMS',
    'value' => '4',
    'name' => 'SMS',
    'filter' => 0,
    'weight' => '4',
    'is_optgroup' => 0,
    'is_reserved' => 0,
    'is_active' => '1',
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testGetSingleValueOptionValueSort"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/OptionValueTest.php
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
