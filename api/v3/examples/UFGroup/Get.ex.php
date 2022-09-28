<?php
/**
 * Test Generated example demonstrating the UFGroup.get API.
 *
 * @return array
 *   API result array
 */
function uf_group_get_example() {
  $params = [
    'id' => 2,
  ];

  try{
    $result = civicrm_api3('UFGroup', 'get', $params);
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
function uf_group_get_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 2,
    'values' => [
      '2' => [
        'id' => '2',
        'is_active' => 0,
        'group_type' => 'Individual,Contact',
        'title' => 'Test Group',
        'help_pre' => 'help pre',
        'help_post' => 'help post',
        'limit_listings_group_id' => '1',
        'post_URL' => 'http://example.org/post',
        'add_to_group_id' => '1',
        'add_captcha' => '1',
        'is_map' => '1',
        'is_edit_link' => '1',
        'is_uf_link' => '1',
        'is_update_dupe' => '1',
        'cancel_URL' => 'http://example.org/cancel',
        'is_cms_user' => '1',
        'notify' => 'admin@example.org',
        'is_reserved' => '1',
        'name' => 'Test_Group',
        'created_id' => '1',
        'created_date' => '2013-07-28 08:49:19',
        'is_proximity_search' => 0,
        'add_cancel_button' => '1',
      ],
    ],
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testUFGroupGet"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/UFGroupTest.php
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
