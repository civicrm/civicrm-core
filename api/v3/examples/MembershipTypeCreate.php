<?php

/*
 
 */
function membership_type_create_example(){
$params = array( 
  'name' => '40+ Membership',
  'description' => 'people above 40 are given health instructions',
  'member_of_contact_id' => 1,
  'financial_type_id' => 1,
  'domain_id' => '1',
  'minimum_fee' => '200',
  'duration_unit' => 'month',
  'duration_interval' => '10',
  'period_type' => 'rolling',
  'visibility' => 'public',
  'version' => 3,
);

  $result = civicrm_api( 'membership_type','create',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function membership_type_create_expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 2,
  'values' => array( 
      '2' => array( 
          'id' => '2',
          'domain_id' => '1',
          'name' => '40+ Membership',
          'description' => 'people above 40 are given health instructions',
          'member_of_contact_id' => '1',
          'financial_type_id' => '1',
          'minimum_fee' => '200',
          'duration_unit' => 'month',
          'duration_interval' => '10',
          'period_type' => 'rolling',
          'fixed_period_start_day' => '',
          'fixed_period_rollover_day' => '',
          'relationship_type_id' => '',
          'relationship_direction' => '',
          'max_related' => '',
          'visibility' => 'public',
          'weight' => '',
          'receipt_text_signup' => '',
          'receipt_text_renewal' => '',
          'auto_renew' => '',
          'is_active' => '',
        ),
    ),
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testCreate and can be found in
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