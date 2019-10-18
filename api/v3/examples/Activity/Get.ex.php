<?php
/**
 * Test Generated example demonstrating the Activity.get API.
 *
 * @return array
 *   API result array
 */
function activity_get_example() {
  $params = [
    'case_id' => [
      'IS NULL' => 1,
    ],
  ];

  try{
    $result = civicrm_api3('Activity', 'get', $params);
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
function activity_get_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 9,
    'values' => [
      '9' => [
        'id' => '9',
        'activity_type_id' => '2',
        'subject' => 'Ask not what your API can do for you, but what you can do for your API.',
        'activity_date_time' => '2019-08-20 19:10:43',
        'status_id' => '2',
        'priority_id' => '2',
        'is_test' => 0,
        'is_auto' => 0,
        'is_current_revision' => '1',
        'is_deleted' => 0,
        'is_star' => 0,
        'created_date' => '2013-07-28 08:49:19',
        'modified_date' => '2012-11-14 16:02:35',
        'source_contact_id' => '19',
        'source_contact_name' => 'Mr. Anthony Anderson II',
        'source_contact_sort_name' => 'Anderson, Anthony',
      ],
    ],
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testGet"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/ActivityCaseTest.php
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
