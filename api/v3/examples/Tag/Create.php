<?php
/**
 * Test Generated example demonstrating the Tag.create API.
 *
 * @return array
 *   API result array
 */
function tag_create_example() {
  $params = [
    'name' => 'Super Heros',
    'description' => 'Outside undie-wearers',
  ];

  try{
    $result = civicrm_api3('Tag', 'create', $params);
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
function tag_create_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 17,
    'values' => [
      '17' => [
        'id' => '17',
        'name' => 'Super Heros',
        'description' => 'Outside undie-wearers',
        'parent_id' => '',
        'is_selectable' => '1',
        'is_reserved' => 0,
        'is_tagset' => 0,
        'used_for' => 'civicrm_contact',
        'created_id' => '',
        'color' => '',
        'created_date' => '2013-07-28 08:49:19',
      ],
    ],
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testCreate"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/TagTest.php
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
