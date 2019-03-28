<?php
/**
 * Test Generated example demonstrating the Tag.getlist API.
 *
 * Demonstrates use of api.getlist for autocomplete and quicksearch applications.
 *
 * @return array
 *   API result array
 */
function tag_getlist_example() {
  $params = [
    'input' => 'New Tag3',
    'extra' => [
      '0' => 'used_for',
    ],
  ];

  try{
    $result = civicrm_api3('Tag', 'getlist', $params);
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
function tag_getlist_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 0,
    'values' => [
      '0' => [
        'id' => '19',
        'label' => 'New Tag3',
        'description' => [
          '0' => 'This is description for Our New Tag ',
        ],
        'extra' => [
          'used_for' => 'civicrm_contact',
        ],
      ],
    ],
    'page_num' => 1,
    'more_results' => '',
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testTagGetList"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/TagTest.php
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
