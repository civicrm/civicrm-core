<?php
/**
 * Test Generated example demonstrating the UFField.get API.
 *
 * @return array
 *   API result array
 */
function uf_field_get_example() {
  $params = [];

  try{
    $result = civicrm_api3('UFField', 'get', $params);
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
function uf_field_get_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 1,
    'values' => [
      '1' => [
        'id' => '1',
        'uf_group_id' => '11',
        'field_name' => 'phone',
        'is_active' => '1',
        'is_view' => 0,
        'is_required' => 0,
        'weight' => '1',
        'visibility' => 'Public Pages and Listings',
        'in_selector' => 0,
        'is_searchable' => '1',
        'location_type_id' => '1',
        'phone_type_id' => '1',
        'label' => 'Test Phone',
        'field_type' => 'Contact',
        'is_multi_summary' => 0,
      ],
    ],
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testGetUFFieldSuccess"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/UFFieldTest.php
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
