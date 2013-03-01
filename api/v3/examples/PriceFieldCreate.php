<?php

/*
 
 */
function price_field_create_example(){
$params = array( 
  'version' => 3,
  'price_set_id' => 3,
  'name' => 'grassvariety',
  'label' => 'Grass Variety',
  'html_type' => 'Text',
  'is_enter_qty' => 1,
  'is_active' => 1,
);

  $result = civicrm_api( 'price_field','create',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function price_field_create_expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 2,
  'values' => array( 
      '2' => array( 
          'id' => '2',
          'price_set_id' => '3',
          'name' => 'grassvariety',
          'label' => 'Grass Variety',
          'html_type' => 'Text',
          'is_enter_qty' => '1',
          'help_pre' => '',
          'help_post' => '',
          'weight' => '',
          'is_display_amounts' => '',
          'options_per_line' => '',
          'is_active' => '1',
          'is_required' => '',
          'active_on' => '',
          'expire_on' => '',
          'javascript' => '',
          'visibility_id' => '',
        ),
    ),
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testCreatePriceField and can be found in
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