<?php

/*
 
 */
function membership_update_example(){
$params = array( 
  'contact_id' => 33,
  'membership_type_id' => 30,
  'join_date' => '2009-01-21',
  'start_date' => '2009-01-21',
  'end_date' => '2009-12-21',
  'source' => 'Payment',
  'is_override' => 1,
  'status_id' => 36,
  'version' => 3,
  'custom_3' => 'custom string',
);

  $result = civicrm_api( 'membership','update',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function membership_update_expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 1,
  'values' => array( 
      '1' => array( 
          'id' => '1',
          'contact_id' => '33',
          'membership_type_id' => '30',
          'join_date' => '20090121000000',
          'start_date' => '20090121000000',
          'end_date' => '20091221000000',
          'source' => 'Payment',
          'status_id' => '36',
          'is_override' => '1',
          'owner_membership_id' => '',
          'max_related' => '',
          'is_test' => '',
          'is_pay_later' => '',
          'contribution_recur_id' => '',
          'campaign_id' => '',
        ),
    ),
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testUpdateWithCustom and can be found in
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