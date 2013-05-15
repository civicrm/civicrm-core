<?php

/*
 Demonstrates creating contribution with SoftCredit
 */
function contribution_create_example(){
$params = array( 
  'contact_id' => 3,
  'receive_date' => '20120511',
  'total_amount' => '100',
  'financial_type_id' => 1,
  'non_deductible_amount' => '10',
  'fee_amount' => '5',
  'net_amount' => '95',
  'source' => 'SSF',
  'contribution_status_id' => 1,
  'version' => 3,
  'soft_credit_to' => 2,
);

  $result = civicrm_api( 'contribution','create',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function contribution_create_expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 1,
  'values' => array( 
      '1' => array( 
          'id' => '1',
          'contact_id' => '1',
          'financial_type_id' => '1',
          'contribution_page_id' => '',
          'payment_instrument_id' => '',
          'receive_date' => '20120511000000',
          'non_deductible_amount' => '10',
          'total_amount' => '100',
          'fee_amount' => '5',
          'net_amount' => '95',
          'trxn_id' => '',
          'invoice_id' => '',
          'currency' => 'USD',
          'cancel_date' => '',
          'cancel_reason' => '',
          'receipt_date' => '',
          'thankyou_date' => '',
          'source' => 'SSF',
          'amount_level' => '',
          'contribution_recur_id' => '',
          'honor_contact_id' => '',
          'is_test' => '',
          'is_pay_later' => '',
          'contribution_status_id' => '1',
          'honor_type_id' => '',
          'address_id' => '',
          'check_number' => '',
          'campaign_id' => '',
          'contribution_type_id' => '1',
        ),
    ),
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testCreateContributionWithSoftCredt and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/ContributionTest.php
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