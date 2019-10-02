<?php
/**
 * Test Generated example demonstrating the UFField.replace API.
 *
 * @return array
 *   API result array
 */
function uf_field_replace_example() {
  $params = [
    'uf_group_id' => 11,
    'option.autoweight' => '',
    'values' => [
      '0' => [
        'field_name' => 'first_name',
        'field_type' => 'Contact',
        'visibility' => 'Public Pages and Listings',
        'weight' => 3,
        'label' => 'Test First Name',
        'is_searchable' => 1,
        'is_active' => 1,
      ],
      '1' => [
        'field_name' => 'country',
        'field_type' => 'Contact',
        'visibility' => 'Public Pages and Listings',
        'weight' => 2,
        'label' => 'Test Country',
        'is_searchable' => 1,
        'is_active' => 1,
        'location_type_id' => 1,
      ],
      '2' => [
        'field_name' => 'phone',
        'field_type' => 'Contact',
        'visibility' => 'Public Pages and Listings',
        'weight' => 1,
        'label' => 'Test Phone',
        'is_searchable' => 1,
        'is_active' => 1,
        'location_type_id' => 1,
        'phone_type_id' => 1,
      ],
    ],
    'check_permissions' => TRUE,
  ];

  try{
    $result = civicrm_api3('UFField', 'replace', $params);
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
function uf_field_replace_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 3,
    'values' => [
      '1' => [
        'id' => '1',
        'uf_group_id' => '11',
        'field_name' => 'first_name',
        'is_active' => '1',
        'is_view' => '',
        'is_required' => '',
        'weight' => '3',
        'help_post' => '',
        'help_pre' => '',
        'visibility' => 'Public Pages and Listings',
        'in_selector' => '',
        'is_searchable' => '1',
        'location_type_id' => '',
        'phone_type_id' => '',
        'website_type_id' => '',
        'label' => 'Test First Name',
        'field_type' => 'Contact',
        'is_reserved' => '',
        'is_multi_summary' => '',
      ],
      '2' => [
        'id' => '2',
        'uf_group_id' => '11',
        'field_name' => 'country',
        'is_active' => '1',
        'is_view' => '',
        'is_required' => '',
        'weight' => '2',
        'help_post' => '',
        'help_pre' => '',
        'visibility' => 'Public Pages and Listings',
        'in_selector' => '',
        'is_searchable' => '1',
        'location_type_id' => '1',
        'phone_type_id' => '',
        'website_type_id' => '',
        'label' => 'Test Country',
        'field_type' => 'Contact',
        'is_reserved' => '',
        'is_multi_summary' => '',
      ],
      '3' => [
        'id' => '3',
        'uf_group_id' => '11',
        'field_name' => 'phone',
        'is_active' => '1',
        'is_view' => '',
        'is_required' => '',
        'weight' => '1',
        'help_post' => '',
        'help_pre' => '',
        'visibility' => 'Public Pages and Listings',
        'in_selector' => '',
        'is_searchable' => '1',
        'location_type_id' => '1',
        'phone_type_id' => '1',
        'website_type_id' => '',
        'label' => 'Test Phone',
        'field_type' => 'Contact',
        'is_reserved' => '',
        'is_multi_summary' => '',
      ],
    ],
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testReplaceUFFields"
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
