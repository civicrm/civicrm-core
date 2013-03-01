<?php

/*
 
 */
function price_field_value_create_example(){
$params = array( 
  'version' => 3,
  'price_field_id' => 13,
  'membership_type_id' => 5,
  'name' => 'memType1',
  'label' => 'memType1',
  'amount' => 90,
  'membership_num_terms' => 2,
  'is_active' => 1,
  'financial_type_id' => 2,
);

  $result = civicrm_api( 'price_field_value','create',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function price_field_value_create_expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 10,
  'values' => array( 
      '10' => array( 
          'id' => '10',
          'price_field_id' => '13',
          'name' => 'memType1',
          'label' => 'memType1',
          'description' => '',
          'amount' => '90',
          'count' => '',
          'max_value' => '',
          'weight' => '1',
          'membership_type_id' => '5',
          'membership_num_terms' => '2',
          'is_default' => '',
          'is_active' => '1',
          'financial_type_id' => '2',
          'deductible_amount' => '',
        ),
    ),
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testCreatePriceFieldValuewithMultipleTerms and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/PriceFieldValueTest.php
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