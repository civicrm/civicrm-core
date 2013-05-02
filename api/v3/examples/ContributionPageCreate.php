<?php

/*
 
 */
function contribution_page_create_example(){
$params = array( 
  'version' => 3,
  'title' => 'Test Contribution Page',
  'financial_type_id' => 1,
  'currency' => 'NZD',
  'goal_amount' => 34567,
);

  $result = civicrm_api( 'contribution_page','create',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function contribution_page_create_expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 1,
  'values' => array( 
      '1' => array( 
          'id' => '1',
          'title' => 'Test Contribution Page',
          'intro_text' => '',
          'financial_type_id' => '1',
          'payment_processor' => '',
          'is_credit_card_only' => '',
          'is_monetary' => '',
          'is_recur' => '',
          'is_confirm_enabled' => '',
          'recur_frequency_unit' => '',
          'is_recur_interval' => '',
          'is_recur_installments' => '',
          'is_pay_later' => '',
          'pay_later_text' => '',
          'pay_later_receipt' => '',
          'is_partial_payment' => '',
          'initial_amount_label' => '',
          'initial_amount_help_text' => '',
          'min_initial_amount' => '',
          'is_allow_other_amount' => '',
          'default_amount_id' => '',
          'min_amount' => '',
          'max_amount' => '',
          'goal_amount' => '34567',
          'thankyou_title' => '',
          'thankyou_text' => '',
          'thankyou_footer' => '',
          'is_for_organization' => '',
          'for_organization' => '',
          'is_email_receipt' => '',
          'receipt_from_name' => '',
          'receipt_from_email' => '',
          'cc_receipt' => '',
          'bcc_receipt' => '',
          'receipt_text' => '',
          'is_active' => '',
          'footer_text' => '',
          'amount_block_is_active' => '',
          'honor_block_is_active' => '',
          'honor_block_title' => '',
          'honor_block_text' => '',
          'start_date' => '',
          'end_date' => '',
          'created_id' => '',
          'created_date' => '',
          'currency' => 'NZD',
          'campaign_id' => '',
          'is_share' => '',
        ),
    ),
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testCreateContributionPage and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/ContributionPageTest.php
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