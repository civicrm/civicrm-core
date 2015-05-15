<?php
/**
 * Test Generated example demonstrating the Tag.get API.
 *
 * @return array
 *   API result array
 */
function tag_get_example() {
  $params = array(
    'id' => '7',
    'name' => 'New Tag3',
  );

  try{
    $result = civicrm_api3('Tag', 'get', $params);
  }
  catch (CiviCRM_API3_Exception $e) {
    // Handle error here.
    $errorMessage = $e->getMessage();
    $errorCode = $e->getErrorCode();
    $errorData = $e->getExtraParams();
    return array(
      'error' => $errorMessage,
      'error_code' => $errorCode,
      'error_data' => $errorData,
    );
  }

  return $result;
}

/**
 * Function returns array of result expected from previous function.
 *
 * @return array
 *   API result array
 */
function tag_get_expectedresult() {

  $expectedResult = array(
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 7,
    'values' => array(
      '7' => array(
        'id' => '7',
        'name' => 'New Tag3',
        'description' => 'This is description for Our New Tag ',
        'is_selectable' => '1',
        'is_reserved' => 0,
        'is_tagset' => 0,
        'used_for' => 'civicrm_contact',
        'created_date' => '2013-07-28 08:49:19',
      ),
    ),
  );

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testGet"
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
