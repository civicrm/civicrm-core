<?php
/**
 * Test Generated example demonstrating the Event.get API.
 *
 * Demonstrates use of is.Current option.
 *
 * @return array
 *   API result array
 */
function event_get_example() {
  $params = [
    'isCurrent' => 1,
  ];

  try{
    $result = civicrm_api3('Event', 'get', $params);
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
function event_get_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 3,
    'values' => [
      '3' => [
        'id' => '3',
        'title' => 'Annual CiviCRM meet 2',
        'event_title' => 'Annual CiviCRM meet 2',
        'summary' => 'If you have any CiviCRM related issues or want to track where CiviCRM is heading, Sign up now',
        'description' => 'This event is intended to give brief idea about progess of CiviCRM and giving solutions to common user issues',
        'event_description' => 'This event is intended to give brief idea about progess of CiviCRM and giving solutions to common user issues',
        'event_type_id' => '1',
        'is_public' => '1',
        'start_date' => '2013-07-29 00:00:00',
        'event_start_date' => '2013-07-29 00:00:00',
        'end_date' => '2013-08-04 00:00:00',
        'event_end_date' => '2013-08-04 00:00:00',
        'is_online_registration' => '1',
        'registration_start_date' => '2010-06-01 00:00:00',
        'registration_end_date' => '2010-10-15 00:00:00',
        'max_participants' => '100',
        'event_full_text' => 'Sorry! We are already full',
        'is_monetary' => 0,
        'is_map' => 0,
        'is_active' => '1',
        'is_show_location' => 0,
        'default_role_id' => '1',
        'is_email_confirm' => 0,
        'is_pay_later' => 0,
        'is_partial_payment' => 0,
        'is_multiple_registrations' => 0,
        'max_additional_participants' => 0,
        'allow_same_participant_emails' => 0,
        'allow_selfcancelxfer' => 0,
        'selfcancelxfer_time' => 0,
        'is_template' => 0,
        'created_date' => '2013-07-28 08:49:19',
        'is_share' => '1',
        'is_confirm_enabled' => '1',
        'is_billing_required' => 0,
      ],
    ],
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testGetIsCurrent"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/EventTest.php
*
* You can see the outcome of the API tests at
* https://test.civicrm.org/job/CiviCRM-Core-Matrix/
*
* To Learn about the API read
* https://docs.civicrm.org/dev/en/latest/api/
*
* Browse the API on your own site with the API Explorer. It is in the main
* CiviCRM menu, under: Support > Development > API Explorer.
*
* Read more about testing here
* https://docs.civicrm.org/dev/en/latest/testing/
*
* API Standards documentation:
* https://docs.civicrm.org/dev/en/latest/framework/api-architecture/
*/
