<?php

/**
 * @file
 */

/**
 * Test Generated example demonstrating the SavedSearch.create API.
 *
 * @return array
 *   API result array
 */
function saved_search_create_example() {
  $params = [
    'expires_date' => '2021-08-08',
    'form_values' => [
      'relation_type_id' => '6_a_b',
      'relation_target_name' => 'Default Organization',
    ],
    'api.Group.create' => [
      'name' => 'my_smartgroup',
      'title' => 'my smartgroup',
      'description' => 'Volunteers for the default organization',
      'saved_search_id' => '$value.id',
      'is_active' => 1,
      'visibility' => 'User and User Admin Only',
      'is_hidden' => 0,
      'is_reserved' => 0,
    ],
  ];

  try {
    $result = civicrm_api3('SavedSearch', 'create', $params);
  }
  catch (CRM_Core_Exception $e) {
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
function saved_search_create_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 3,
    'values' => [
      '3' => [
        'id' => '3',
        'name' => '',
        'label' => '',
        'form_values' => [
          'relation_type_id' => '6_a_b',
          'relation_target_name' => 'Default Organization',
        ],
        'mapping_id' => '',
        'search_custom_id' => '',
        'api_entity' => '',
        'api_params' => '',
        'created_id' => '',
        'modified_id' => '',
        'expires_date' => '20210808000000',
        'created_date' => '',
        'modified_date' => '',
        'description' => '',
        'api.Group.create' => [
          'is_error' => 0,
          'version' => 3,
          'count' => 1,
          'id' => 1,
          'values' => [
            '0' => [
              'id' => '1',
              'name' => 'my_smartgroup',
              'title' => 'my smartgroup',
              'description' => 'Volunteers for the default organization',
              'source' => '',
              'saved_search_id' => '3',
              'is_active' => '1',
              'visibility' => 'User and User Admin Only',
              'where_clause' => '',
              'select_tables' => '',
              'where_tables' => '',
              'group_type' => '',
              'cache_date' => '',
              'refresh_date' => '',
              'parents' => '',
              'children' => '',
              'is_hidden' => 0,
              'is_reserved' => 0,
              'created_id' => '',
              'modified_id' => '',
              'frontend_title' => '',
              'frontend_description' => '',
            ],
          ],
        ],
      ],
    ],
  ];

  return $expectedResult;
}

/*
 * This example has been generated from the API test suite.
 * The test that created it is called "testCreateSavedSearchWithSmartGroup"
 * and can be found at:
 * https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/SavedSearchTest.php
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
