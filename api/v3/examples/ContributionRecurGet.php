<?php

/*
 
 */
function contribution_recur_get_example(){
$params = array( 
  'version' => 3,
  'amount' => '500',
);

  $result = civicrm_api( 'contribution_recur','get',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function contribution_recur_get_expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 1,
  'values' => array( 
      '1' => array( 
          'id' => '1',
          'contact_id' => '4',
          'amount' => '500.00',
          'currency' => 'USD',
          'frequency_unit' => 'day',
          'frequency_interval' => '1',
          'installments' => '12',
          'start_date' => '2012-01-01 00:00:00',
          'create_date' => '2013-02-04 22:26:27',
          'contribution_status_id' => '1',
          'is_test' => 0,
          'cycle_day' => '1',
          'failure_count' => 0,
          'auto_renew' => 0,
          'is_email_receipt' => '1',
        ),
    ),
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testGetContributionRecur and can be found in
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