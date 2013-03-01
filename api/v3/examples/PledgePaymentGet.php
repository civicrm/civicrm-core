<?php

/*
 
 */
function pledge_payment_get_example(){
$params = array( 
  'version' => 3,
);

  $result = civicrm_api( 'pledge_payment','get',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function pledge_payment_get_expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'version' => 3,
  'count' => 5,
  'values' => array( 
      '1' => array( 
          'id' => '1',
          'pledge_id' => '1',
          'scheduled_amount' => '20.00',
          'currency' => 'USD',
          'scheduled_date' => '2013-02-15 00:00:00',
          'reminder_count' => 0,
          'status_id' => '2',
        ),
      '2' => array( 
          'id' => '2',
          'pledge_id' => '1',
          'scheduled_amount' => '20.00',
          'currency' => 'USD',
          'scheduled_date' => '2018-02-15 00:00:00',
          'reminder_count' => 0,
          'status_id' => '2',
        ),
      '3' => array( 
          'id' => '3',
          'pledge_id' => '1',
          'scheduled_amount' => '20.00',
          'currency' => 'USD',
          'scheduled_date' => '2023-02-15 00:00:00',
          'reminder_count' => 0,
          'status_id' => '2',
        ),
      '4' => array( 
          'id' => '4',
          'pledge_id' => '1',
          'scheduled_amount' => '20.00',
          'currency' => 'USD',
          'scheduled_date' => '2028-02-15 00:00:00',
          'reminder_count' => 0,
          'status_id' => '2',
        ),
      '5' => array( 
          'id' => '5',
          'pledge_id' => '1',
          'scheduled_amount' => '20.00',
          'currency' => 'USD',
          'scheduled_date' => '2033-02-15 00:00:00',
          'reminder_count' => 0,
          'status_id' => '2',
        ),
    ),
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testGetPledgePayment and can be found in
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