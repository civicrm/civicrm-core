<?php
/**
 * Test Generated example demonstrating the SavedSearch.create API.
 *
 * @return array
 *   API result array
 */
function saved_search_create_example() {
  $params = [
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

  try{
    $result = civicrm_api3('SavedSearch', 'create', $params);
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
function saved_search_create_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 3,
    'values' => [
      '3' => [
        'id' => '3',
        'form_values' => [
          'relation_type_id' => '6_a_b',
          'relation_target_name' => 'Default Organization',
        ],
        'mapping_id' => '',
        'search_custom_id' => '',
        'where_clause' => '',
        'select_tables' => '',
        'where_tables' => '',
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
              'where_clause' => ' (  ( `civicrm_group_contact_cache_1`.group_id IN (\"1\") )  ) ',
              'select_tables' => 'a:8:{s:15:\"civicrm_contact\";i:1;s:15:\"civicrm_address\";i:1;s:15:\"civicrm_country\";i:1;s:13:\"civicrm_email\";i:1;s:13:\"civicrm_phone\";i:1;s:10:\"civicrm_im\";i:1;s:19:\"civicrm_worldregion\";i:1;s:31:\"`civicrm_group_contact_cache_1`\";s:132:\" LEFT JOIN civicrm_group_contact_cache `civicrm_group_contact_cache_1` ON contact_a.id = `civicrm_group_contact_cache_1`.contact_id \";}',
              'where_tables' => 'a:2:{s:15:\"civicrm_contact\";i:1;s:31:\"`civicrm_group_contact_cache_1`\";s:132:\" LEFT JOIN civicrm_group_contact_cache `civicrm_group_contact_cache_1` ON contact_a.id = `civicrm_group_contact_cache_1`.contact_id \";}',
              'group_type' => '',
              'cache_date' => '',
              'refresh_date' => '',
              'parents' => '',
              'children' => '',
              'is_hidden' => 0,
              'is_reserved' => 0,
              'created_id' => '',
              'modified_id' => '',
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
