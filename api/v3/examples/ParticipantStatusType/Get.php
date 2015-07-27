<?php
/**
 * Test Generated example demonstrating the ParticipantStatusType.get API.
 *
 * @return array
 *   API result array
 */
function participant_status_type_get_example() {
  $params = array(
    'name' => 'test status',
    'label' => 'I am a test',
    'class' => 'Positive',
    'is_reserved' => 0,
    'is_active' => 1,
    'is_counted' => 1,
    'visibility_id' => 1,
    'weight' => 10,
  );

  try{
    $result = civicrm_api3('ParticipantStatusType', 'get', $params);
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
function participant_status_type_get_expectedresult() {

  $expectedResult = array(
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 17,
    'values' => array(
      '17' => array(
        'id' => '17',
        'name' => 'test status',
        'label' => 'I am a test',
        'class' => 'Positive',
        'is_reserved' => 0,
        'is_active' => '1',
        'is_counted' => '1',
        'weight' => '10',
        'visibility_id' => '1',
      ),
    ),
  );

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testGetParticipantStatusType"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/ParticipantStatusTypeTest.php
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
