<?php
/**
 * Test Generated example demonstrating the Contact.getvalue API.
 *
 * This demonstrates use of the 'format.single_value' param.
 * This param causes only a single value of the only entity to be returned as an string.
 * It will be ignored if there is not exactly 1 result
 *
 * @return array
 *   API result array
 */
function contact_getvalue_example() {
  $params = [
    'id' => 17,
    'return' => 'display_name',
  ];

  try{
    $result = civicrm_api3('Contact', 'getvalue', $params);
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
function contact_getvalue_expectedresult() {

  $expectedResult = 'Test Contact';

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testContactGetFormatSingleValue"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/ContactTest.php
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
