<?php
/**
 * Test Generated example demonstrating the OptionValue.get API.
 *
 * @return array
 *   API result array
 */
function option_value_get_example() {
  $params = [
    'option_group_id' => 1,
  ];

  try{
    $result = civicrm_api3('OptionValue', 'get', $params);
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
function option_value_get_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 5,
    'values' => [
      '1' => [
        'id' => '1',
        'option_group_id' => '1',
        'label' => 'Phone',
        'value' => '1',
        'name' => 'Phone',
        'filter' => 0,
        'weight' => '1',
        'is_optgroup' => 0,
        'is_reserved' => 0,
        'is_active' => '1',
      ],
      '2' => [
        'id' => '2',
        'option_group_id' => '1',
        'label' => 'Email',
        'value' => '2',
        'name' => 'Email',
        'filter' => 0,
        'weight' => '2',
        'is_optgroup' => 0,
        'is_reserved' => 0,
        'is_active' => '1',
      ],
      '3' => [
        'id' => '3',
        'option_group_id' => '1',
        'label' => 'Postal Mail',
        'value' => '3',
        'name' => 'Postal Mail',
        'filter' => 0,
        'weight' => '3',
        'is_optgroup' => 0,
        'is_reserved' => 0,
        'is_active' => '1',
      ],
      '4' => [
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
      ],
      '5' => [
        'id' => '5',
        'option_group_id' => '1',
        'label' => 'Fax',
        'value' => '5',
        'name' => 'Fax',
        'filter' => 0,
        'weight' => '5',
        'is_optgroup' => 0,
        'is_reserved' => 0,
        'is_active' => '1',
      ],
    ],
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testGetOptionGroup"
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
