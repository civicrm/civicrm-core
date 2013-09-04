<?php
/**
 * Test Generated example of using participant_status_type get API
 * *
 */
function participant_status_type_get_example(){
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
  $result = civicrm_api3('participant_status_type', 'get', $params);
}
catch (CiviCRM_API3_Exception $e) {
  // handle error here
  $errorMessage = $e->getMessage();
  $errorCode = $e->getErrorCode();
  $errorData = $e->getExtraParams();
  return array('error' => $errorMessage, 'error_code' => $errorCode, 'error_data' => $errorData);
}

return $result;
}

/**
 * Function returns array of result expected from previous function
 */
function participant_status_type_get_expectedresult(){

  $expectedResult = array(
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 14,
  'values' => array(
      '14' => array(
          'id' => '14',
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
* This example has been generated from the API test suite. The test that created it is called
*
* testGetParticipantStatusType and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/ParticipantStatusTypeTest.php
*
* You can see the outcome of the API tests at
* http://tests.dev.civicrm.org/trunk/results-api_v3
*
* To Learn about the API read
* http://book.civicrm.org/developer/current/techniques/api/
*
* and review the wiki at
* http://wiki.civicrm.org/confluence/display/CRMDOC/CiviCRM+Public+APIs
*
* Read more about testing here
* http://wiki.civicrm.org/confluence/display/CRM/Testing
*
* API Standards documentation:
* http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
*/