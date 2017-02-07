<?php
/**
 * Test Generated example demonstrating the Logging.revert API.
 *
 * @return array
 *   API result array
 */
function logging_revert_example() {
  $params = array(
    'log_conn_id' => 'woot',
    'log_date' => '2017-02-07 02:35:06',
  );

  try{
    $result = civicrm_api3('Logging', 'revert', $params);
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
function logging_revert_expectedresult() {

  $expectedResult = array(
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'values' => 1,
  );

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "/Users/emcnaughton/buildkit/build/dmaster/sites/all/modules/civicrm/tests/phpunit/api/v3/LoggingTest.php"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/Revert
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
