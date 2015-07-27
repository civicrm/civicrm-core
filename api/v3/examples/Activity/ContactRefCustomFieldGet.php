<?php
/**
 * Test Generated example demonstrating the Activity.get API.
 *
 * Get with Contact Ref Custom Field
 *
 * @return array
 *   API result array
 */
function activity_get_example() {
  $params = array(
    'return.custom_2' => 1,
    'id' => 1,
  );

  try{
    $result = civicrm_api3('Activity', 'get', $params);
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
function activity_get_expectedresult() {

  $expectedResult = array(
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 1,
    'values' => array(
      '1' => array(
        'id' => '1',
        'activity_type_id' => '51',
        'subject' => 'test activity type id',
        'activity_date_time' => '2011-06-02 14:36:13',
        'duration' => '120',
        'location' => 'Pennsylvania',
        'details' => 'a test activity',
        'status_id' => '2',
        'priority_id' => '1',
        'is_test' => 0,
        'is_auto' => 0,
        'is_current_revision' => '1',
        'is_deleted' => 0,
        'source_contact_id' => '1',
        'custom_1' => 'defaultValue',
        'custom_1_1' => 'defaultValue',
        'custom_2' => 'Anderson, Anthony',
        'custom_2_1' => 'Anderson, Anthony',
        'custom_2_1_id' => '1',
        'custom_2_id' => '1',
      ),
    ),
  );

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testActivityCreateCustomContactRefField"
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
