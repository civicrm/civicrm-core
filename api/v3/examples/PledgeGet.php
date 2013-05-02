<?php

/*
 
 */
function pledge_get_example(){
$params = array( 
  'pledge_id' => 1,
  'version' => 3,
);

  $result = civicrm_api( 'pledge','get',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function pledge_get_expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 1,
  'values' => array( 
      '1' => array( 
          'contact_id' => '5',
          'contact_type' => 'Individual',
          'contact_sub_type' => '',
          'sort_name' => 'Anderson, Anthony',
          'display_name' => 'Mr. Anthony Anderson II',
          'pledge_id' => '1',
          'pledge_amount' => '100.00',
          'pledge_create_date' => '2013-02-04 00:00:00',
          'pledge_status' => 'Pending',
          'pledge_total_paid' => '',
          'pledge_next_pay_date' => '2013-02-06 00:00:00',
          'pledge_next_pay_amount' => '20.00',
          'pledge_outstanding_amount' => '',
          'pledge_financial_type' => 'Donation',
          'pledge_contribution_page_id' => '',
          'pledge_frequency_interval' => '5',
          'pledge_frequency_unit' => 'year',
          'pledge_is_test' => 0,
          'pledge_campaign_id' => '',
          'pledge_currency' => 'USD',
          'id' => '1',
        ),
    ),
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testGetPledge and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/PledgeTest.php
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