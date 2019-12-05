<?php
/**
 * Test Generated example demonstrating the Activity.create API.
 *
 * Demonstrates setting & retrieving activity target & source.
 *
 * @return array
 *   API result array
 */
function activity_create_example() {
  $params = [
    'source_contact_id' => 1,
    'subject' => 'Make-it-Happen Meeting',
    'activity_date_time' => '20110316',
    'duration' => 120,
    'location' => 'Pennsylvania',
    'details' => 'a test activity',
    'status_id' => 1,
    'activity_type_id' => 1,
    'priority_id' => 1,
    'target_contact_id' => 1,
    'assignee_contact_id' => 1,
  ];

  try{
    $result = civicrm_api3('Activity', 'create', $params);
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
function activity_create_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 1,
    'values' => [
      '1' => [
        'id' => '1',
        'source_record_id' => '',
        'activity_type_id' => '1',
        'subject' => 'Make-it-Happen Meeting',
        'activity_date_time' => '20110316000000',
        'duration' => '120',
        'location' => 'Pennsylvania',
        'phone_id' => '',
        'phone_number' => '',
        'details' => 'a test activity',
        'status_id' => '1',
        'priority_id' => '1',
        'parent_id' => '',
        'is_test' => '',
        'medium_id' => '',
        'is_auto' => '',
        'relationship_id' => '',
        'is_current_revision' => '',
        'original_id' => '',
        'result' => '',
        'is_deleted' => '',
        'campaign_id' => '',
        'engagement_level' => '',
        'weight' => '',
        'is_star' => '',
      ],
    ],
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testActivityReturnTargetAssignee"
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
