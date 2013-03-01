<?php

/*
 
 */
function price_set_set_example(){
$params = array( 
  'version' => 3,
  'entity_table' => 'civicrm_event',
  'entity_id' => 1,
  'name' => 'event price',
  'title' => 'event price',
  'extends' => 1,
);

  $result = civicrm_api( 'price_set','set',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function price_set_set_expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 16,
  'values' => array( 
      '16' => array( 
          'id' => '16',
          'domain_id' => '',
          'name' => 'event price',
          'title' => 'event price',
          'is_active' => '',
          'help_pre' => '',
          'help_post' => '',
          'javascript' => '',
          'extends' => '1',
          'financial_type_id' => '',
          'is_quick_config' => '',
          'is_reserved' => '',
        ),
    ),
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testEventPriceSet and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/PriceSetTest.php
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