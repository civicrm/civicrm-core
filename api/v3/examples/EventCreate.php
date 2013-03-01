<?php

/*
 
 */
function event_create_example(){
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
  'version' => 3,
);

  $result = civicrm_api( 'event','create',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function event_create_expectedresult(){

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
          'start_date' => '20081021000000',
          'end_date' => '20081023000000',
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
          'created_date' => '20130204223127',
          'currency' => '',
          'campaign_id' => '',
          'is_share' => '',
          'parent_event_id' => '',
          'slot_label_id' => '',
        ),
    ),
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testCreateEventSuccess and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/EventTest.php
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