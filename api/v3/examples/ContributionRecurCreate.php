<?php

/*
 
 */
function contribution_recur_create_example(){
$params = array( 
  'version' => 3,
  'contact_id' => 3,
  'installments' => '12',
  'frequency_interval' => '1',
  'amount' => '500',
  'contribution_status_id' => 1,
  'start_date' => '2012-01-01 00:00:00',
  'currency' => 'USD',
  'frequency_unit' => 'day',
);

  $result = civicrm_api( 'contribution_recur','create',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function contribution_recur_create_expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 1,
  'values' => array( 
      '1' => array( 
          'id' => '1',
          'contact_id' => '3',
          'amount' => '500',
          'currency' => 'USD',
          'frequency_unit' => 'day',
          'frequency_interval' => '1',
          'installments' => '12',
          'start_date' => '20120101000000',
          'create_date' => '20130204222627',
          'modified_date' => '2012-11-14 16:02:35',
          'cancel_date' => '',
          'end_date' => '',
          'processor_id' => '',
          'trxn_id' => '',
          'invoice_id' => '',
          'contribution_status_id' => '1',
          'is_test' => '',
          'cycle_day' => '',
          'next_sched_contribution' => '',
          'failure_count' => '',
          'failure_retry_date' => '',
          'auto_renew' => '',
          'payment_processor_id' => '',
          'financial_type_id' => '',
          'payment_instrument_id' => '',
          'campaign_id' => '',
          'is_email_receipt' => '',
        ),
    ),
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testCreateContributionRecur and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/ContributionRecurTest.php
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