<?php
/**
 * Test Generated example demonstrating the MembershipStatus.create API.
 *
 * @return array
 *   API result array
 */
function membership_status_create_example() {
  $params = [
    'name' => 'test membership status',
  ];

  try{
    $result = civicrm_api3('MembershipStatus', 'create', $params);
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
function membership_status_create_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 15,
    'values' => [
      '15' => [
        'id' => '15',
        'name' => 'test membership status',
        'label' => 'test membership status',
        'start_event' => '',
        'start_event_adjust_unit' => '',
        'start_event_adjust_interval' => '',
        'end_event' => '',
        'end_event_adjust_unit' => '',
        'end_event_adjust_interval' => '',
        'is_current_member' => '',
        'is_admin' => '',
        'weight' => '',
        'is_default' => '',
        'is_active' => '',
        'is_reserved' => '',
      ],
    ],
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testCreate"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/MembershipStatusTest.php
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
