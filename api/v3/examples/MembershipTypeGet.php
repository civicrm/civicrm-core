<?php

/*
 
 */
function membership_type_get_example(){
$params = array( 
  'id' => 1,
  'version' => 3,
);

  $result = civicrm_api( 'membership_type','get',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function membership_type_get_expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 1,
  'values' => array( 
      '1' => array( 
          'id' => '1',
          'domain_id' => '1',
          'name' => 'General',
          'member_of_contact_id' => '1',
          'financial_type_id' => '1',
          'minimum_fee' => '0.00',
          'duration_unit' => 'year',
          'duration_interval' => '1',
          'period_type' => 'rolling',
          'visibility' => '1',
          'auto_renew' => 0,
          'is_active' => '1',
        ),
    ),
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testGet and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/MembershipTypeTest.php
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