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
  $params = array(
    'isCurrent' => 1,
  );

  try{
    $result = civicrm_api3('Event', 'get', $params);
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
function event_get_expectedresult() {

  $expectedResult = array(
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 3,
    'values' => array(
      '3' => array(
        'id' => '3',
        'title' => 'Annual CiviCRM meet 2',
        'event_title' => 'Annual CiviCRM meet 2',
        'summary' => 'If you have any CiviCRM realted issues or want to track where CiviCRM is heading, Sign up now',
        'description' => 'This event is intended to give brief idea about progess of CiviCRM and giving solutions to common user issues',
        'event_description' => 'This event is intended to give brief idea about progess of CiviCRM and giving solutions to common user issues',
        'event_type_id' => '1',
        'participant_listing_id' => 0,
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
        'allow_same_participant_emails' => 0,
        'is_template' => 0,
        'created_date' => '2013-07-28 08:49:19',
        'is_share' => '1',
        'is_confirm_enabled' => '1',
        'is_billing_required' => 0,
      ),
    ),
  );

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testGetIsCurrent"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/EventTest.php
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
