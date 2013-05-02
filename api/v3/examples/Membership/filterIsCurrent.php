<?php

/*
 Demonstrates use of 'filter' active_only' param
 */
function membership_get_example(){
$params = array( 
  'contact_id' => 14,
  'filters' => array( 
      'is_current' => 1,
    ),
  'version' => 3,
);

  $result = civicrm_api( 'membership','get',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function membership_get_expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 1,
  'values' => array( 
      '1' => array( 
          'id' => '1',
          'membership_id' => '1',
          'contact_id' => '14',
          'membership_contact_id' => '14',
          'membership_type_id' => '12',
          'join_date' => '2009-01-21',
          'start_date' => '2009-01-21',
          'membership_start_date' => '2009-01-21',
          'end_date' => '2009-12-21',
          'membership_end_date' => '2009-12-21',
          'source' => 'Payment',
          'membership_source' => 'Payment',
          'status_id' => '19',
          'is_override' => '1',
          'is_test' => 0,
          'member_is_test' => 0,
          'is_pay_later' => 0,
          'member_is_pay_later' => 0,
          'membership_name' => 'General',
          'relationship_name' => 'Child of',
        ),
    ),
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testGetOnlyActive and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/MembershipTest.php
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