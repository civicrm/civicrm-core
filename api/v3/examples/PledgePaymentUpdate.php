<?php

/*
 
 */
function pledge_payment_update_example(){
$params = array( 
  'pledge_id' => 1,
  'contribution_id' => 1,
  'version' => 3,
  'status_id' => 2,
  'actual_amount' => 20,
);

  $result = civicrm_api( 'pledge_payment','update',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function pledge_payment_update_expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 1,
  'values' => array( 
      '1' => array( 
          'id' => '1',
          'pledge_id' => '1',
          'contribution_id' => '1',
          'scheduled_amount' => '20.00',
          'actual_amount' => '20.00',
          'currency' => 'USD',
          'scheduled_date' => '2013-02-15 00:00:00',
          'reminder_date' => '',
          'reminder_count' => 0,
          'status_id' => '1',
        ),
    ),
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testUpdatePledgePayment and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/PledgePaymentTest.php
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