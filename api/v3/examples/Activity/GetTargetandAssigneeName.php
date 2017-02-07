<?php
/**
 * Test Generated example demonstrating the Activity.getsingle API.
 *
 * Demonstrates retrieving activity target & source contact names.
 *
 * @return array
 *   API result array
 */
function activity_getsingle_example() {
  $params = array(
    'id' => 1,
    'return' => array(
      '0' => 'source_contact_name',
      '1' => 'target_contact_name',
      '2' => 'assignee_contact_name',
      '3' => 'subject',
    ),
  );

  try{
    $result = civicrm_api3('Activity', 'getsingle', $params);
  }
  catch (CiviCRM_API3_Exception $e) {
    // Handle error here.
    $errorMessage = $e->getMessage();
    $errorCode = $e->getErrorCode();
    $errorData = $e->getExtraParams();
    return array(
      'is_error' => 1,
      'error_message' => $errorMessage,
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
function activity_getsingle_expectedresult() {

  $expectedResult = array(
    'id' => '1',
    'subject' => 'Make-it-Happen Meeting',
    'source_contact_id' => '6',
    'source_contact_name' => 'D Bug',
    'target_contact_id' => array(
      '1' => '4',
    ),
    'target_contact_name' => array(
      '3' => 'A Cat',
      '4' => 'B Good',
    ),
    'assignee_contact_id' => array(),
    'assignee_contact_name' => array(
      '5' => 'C Shore',
    ),
  );

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testActivityReturnTargetAssigneeName"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/ActivityTest.php
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
