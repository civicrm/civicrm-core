<?php
/**
 * Test Generated example demonstrating the PriceField.create API.
 *
 * @return array
 *   API result array
 */
function price_field_create_example() {
  $params = [
    'price_set_id' => 3,
    'name' => 'grassvariety',
    'label' => 'Grass Variety',
    'html_type' => 'Text',
    'is_enter_qty' => 1,
    'is_active' => 1,
  ];

  try{
    $result = civicrm_api3('PriceField', 'create', $params);
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
function price_field_create_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 2,
    'values' => [
      '2' => [
        'id' => '2',
        'price_set_id' => '3',
        'name' => 'grassvariety',
        'label' => 'Grass Variety',
        'html_type' => 'Text',
        'is_enter_qty' => '1',
        'help_pre' => '',
        'help_post' => '',
        'weight' => '',
        'is_display_amounts' => '',
        'options_per_line' => '',
        'is_active' => '1',
        'is_required' => '',
        'active_on' => '',
        'expire_on' => '',
        'javascript' => '',
        'visibility_id' => '',
      ],
    ],
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testCreatePriceField"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/PriceFieldTest.php
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
