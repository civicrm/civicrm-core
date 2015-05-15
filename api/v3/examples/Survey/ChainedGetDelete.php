<?php
/**
 * Test Generated example demonstrating the Survey.get API.
 *
 * Demonstrates get + delete in the same call.
 *
 * @return array
 *   API result array
 */
function survey_get_example() {
  $params = array(
    'title' => 'survey title',
    'api.survey.delete' => 1,
  );

  try{
    $result = civicrm_api3('Survey', 'get', $params);
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
function survey_get_expectedresult() {

  $expectedResult = array(
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 4,
    'values' => array(
      '4' => array(
        'id' => '4',
        'title' => 'survey title',
        'activity_type_id' => '30',
        'instructions' => 'Call people, ask for money',
        'max_number_of_contacts' => '12',
        'is_active' => '1',
        'is_default' => 0,
        'created_date' => '2013-07-28 08:49:19',
        'bypass_confirm' => 0,
        'is_share' => '1',
        'api.survey.delete' => array(
          'is_error' => 0,
          'version' => 3,
          'count' => 1,
          'values' => TRUE,
        ),
      ),
    ),
  );

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testGetSurveyChainDelete"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/SurveyTest.php
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
