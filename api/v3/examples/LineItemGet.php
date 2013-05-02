<?php

/*
 
 */
function line_item_get_example(){
$params = array( 
  'version' => 3,
  'entity_table' => 'civicrm_contribution',
);

  $result = civicrm_api( 'line_item','get',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function line_item_get_expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 1,
  'values' => array( 
      '1' => array( 
          'id' => '1',
          'entity_table' => 'civicrm_contribution',
          'entity_id' => '1',
          'price_field_id' => '1',
          'label' => 'Contribution Amount',
          'qty' => '1',
          'unit_price' => '100.00',
          'line_total' => '100.00',
          'price_field_value_id' => '1',
          'financial_type_id' => '3',
          'deductible_amount' => '0.00',
        ),
    ),
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testGetBasicLineItem and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/LineItemTest.php
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