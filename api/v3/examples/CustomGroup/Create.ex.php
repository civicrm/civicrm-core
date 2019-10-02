<?php
/**
 * Test Generated example demonstrating the CustomGroup.create API.
 *
 * @return array
 *   API result array
 */
function custom_group_create_example() {
  $params = [
    'title' => 'Test_Group_1',
    'name' => 'test_group_1',
    'extends' => [
      '0' => 'Individual',
    ],
    'weight' => 4,
    'collapse_display' => 1,
    'style' => 'Inline',
    'help_pre' => 'This is Pre Help For Test Group 1',
    'help_post' => 'This is Post Help For Test Group 1',
    'is_active' => 1,
  ];

  try{
    $result = civicrm_api3('CustomGroup', 'create', $params);
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
function custom_group_create_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 1,
    'values' => [
      '1' => [
        'id' => '1',
        'name' => 'test_group_1',
        'title' => 'Test_Group_1',
        'extends' => 'Individual',
        'extends_entity_column_id' => '',
        'extends_entity_column_value' => '',
        'style' => 'Inline',
        'collapse_display' => '1',
        'help_pre' => 'This is Pre Help For Test Group 1',
        'help_post' => 'This is Post Help For Test Group 1',
        'weight' => '2',
        'is_active' => '1',
        'table_name' => 'civicrm_value_test_group_1_1',
        'is_multiple' => '',
        'min_multiple' => '',
        'max_multiple' => '',
        'collapse_adv_display' => '',
        'created_id' => '',
        'created_date' => '',
        'is_reserved' => '',
        'is_public' => '',
      ],
    ],
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testCustomGroupCreate"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/CustomGroupTest.php
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
