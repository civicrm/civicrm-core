<?php
/**
 * Test Generated example demonstrating the Event.create API.
 *
 * @return array
 *   API result array
 */
function event_create_example() {
  $params = array(
    'title' => 'Annual CiviCRM meet',
    'summary' => 'If you have any CiviCRM realted issues or want to track where CiviCRM is heading, Sign up now',
    'description' => 'This event is intended to give brief idea about progess of CiviCRM and giving solutions to common user issues',
    'event_type_id' => 1,
    'is_public' => 1,
    'start_date' => 20081021,
    'end_date' => 20081023,
    'is_online_registration' => 1,
    'registration_start_date' => 20080601,
    'registration_end_date' => '2008-10-15',
    'max_participants' => 100,
    'event_full_text' => 'Sorry! We are already full',
    'is_monetary' => 0,
    'is_active' => 1,
    'is_show_location' => 0,
  );

  try{
    $result = civicrm_api3('Event', 'create', $params);
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
function event_create_expectedresult() {

  $expectedResult = array(
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 3,
    'values' => array(
      '3' => array(
        'id' => '3',
        'title' => 'Annual CiviCRM meet',
        'summary' => 'If you have any CiviCRM realted issues or want to track where CiviCRM is heading, Sign up now',
        'description' => 'This event is intended to give brief idea about progess of CiviCRM and giving solutions to common user issues',
        'event_type_id' => '1',
        'participant_listing_id' => '',
        'is_public' => '1',
        'start_date' => '2013-07-29 00:00:00',
        'end_date' => '2013-08-04 00:00:00',
        'is_online_registration' => '1',
        'registration_link_text' => '',
        'registration_start_date' => '20080601000000',
        'registration_end_date' => '20081015000000',
        'max_participants' => '100',
        'event_full_text' => 'Sorry! We are already full',
        'is_monetary' => 0,
        'financial_type_id' => '',
        'payment_processor' => '',
        'is_map' => '',
        'is_active' => '1',
        'fee_label' => '',
        'is_show_location' => 0,
        'loc_block_id' => '',
        'default_role_id' => '',
        'intro_text' => '',
        'footer_text' => '',
        'confirm_title' => '',
        'confirm_text' => '',
        'confirm_footer_text' => '',
        'is_email_confirm' => '',
        'confirm_email_text' => '',
        'confirm_from_name' => '',
        'confirm_from_email' => '',
        'cc_confirm' => '',
        'bcc_confirm' => '',
        'default_fee_id' => '',
        'default_discount_fee_id' => '',
        'thankyou_title' => '',
        'thankyou_text' => '',
        'thankyou_footer_text' => '',
        'is_pay_later' => '',
        'pay_later_text' => '',
        'pay_later_receipt' => '',
        'is_partial_payment' => '',
        'initial_amount_label' => '',
        'initial_amount_help_text' => '',
        'min_initial_amount' => '',
        'is_multiple_registrations' => '',
        'allow_same_participant_emails' => '',
        'has_waitlist' => '',
        'requires_approval' => '',
        'expiration_time' => '',
        'waitlist_text' => '',
        'approval_req_text' => '',
        'is_template' => 0,
        'template_title' => '',
        'created_id' => '',
        'created_date' => '2013-07-28 08:49:19',
        'currency' => '',
        'campaign_id' => '',
        'is_share' => '',
        'is_confirm_enabled' => '',
        'parent_event_id' => '',
        'slot_label_id' => '',
        'dedupe_rule_group_id' => '',
        'is_billing_required' => '',
      ),
    ),
  );

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testCreateEventSuccess"
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
