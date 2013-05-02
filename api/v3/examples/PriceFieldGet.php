<?php

/*
 
 */
function price_field_get_example(){
$params = array( 
  'version' => 3,
  'name' => 'contribution_amount',
);

  $result = civicrm_api( 'price_field','get',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function price_field_get_expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 1,
  'values' => array( 
      '1' => array( 
          'id' => '1',
          'price_set_id' => '1',
          'name' => 'contribution_amount',
          'label' => 'Contribution Amount',
          'html_type' => 'Text',
          'is_enter_qty' => 0,
          'weight' => '1',
          'is_display_amounts' => '1',
          'options_per_line' => '1',
          'is_active' => '1',
          'is_required' => '1',
          'visibility_id' => '1',
        ),
    ),
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testGetBasicPriceField and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/PriceFieldTest.php
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