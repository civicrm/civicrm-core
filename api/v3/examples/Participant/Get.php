<?php
/**
 * Test Generated example demonstrating the Participant.get API.
 *
 * @return array
 *   API result array
 */
function participant_get_example() {
  $params = [
    'id' => 1,
  ];

  try{
    $result = civicrm_api3('Participant', 'get', $params);
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
function participant_get_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 1,
    'values' => [
      '1' => [
        'contact_id' => '2',
        'contact_type' => 'Individual',
        'contact_sub_type' => '',
        'sort_name' => 'Anderson, Anthony',
        'display_name' => 'Mr. Anthony Anderson II',
        'event_id' => '6',
        'event_title' => 'Annual CiviCRM meet',
        'event_start_date' => '2013-07-29 00:00:00',
        'event_end_date' => '2013-08-04 00:00:00',
        'participant_id' => '1',
        'participant_fee_level' => '',
        'participant_fee_amount' => '',
        'participant_fee_currency' => '',
        'event_type' => 'Conference',
        'participant_status_id' => '2',
        'participant_status' => 'Attended',
        'participant_role_id' => '1',
        'participant_role' => 'Attendee',
        'participant_register_date' => '2007-02-19 00:00:00',
        'participant_source' => 'Wimbeldon',
        'participant_note' => '',
        'participant_is_pay_later' => 0,
        'participant_is_test' => 0,
        'participant_registered_by_id' => '',
        'participant_discount_name' => '',
        'participant_campaign_id' => '',
        'id' => '1',
      ],
    ],
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testGetParamsAsIdOnly"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/ParticipantTest.php
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
