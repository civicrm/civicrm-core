<?php

/*
 /*this demonstrates the use of CustomValue get
 */
function custom_value_get_example(){
$params = array( 
  'id' => 2,
  'version' => 3,
  'entity_id' => 2,
);

  $result = civicrm_api( 'custom_value','get',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function custom_value_get_expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'version' => 3,
  'count' => 7,
  'values' => array( 
      '1' => array( 
          'entity_id' => '2',
          'latest' => 'value 1',
          'id' => '1',
          '0' => 'value 1',
        ),
      '2' => array( 
          'entity_id' => '2',
          'latest' => 'value 3',
          'id' => '2',
          '1' => 'value 2',
          '2' => 'value 3',
        ),
      '3' => array( 
          'entity_id' => '2',
          'latest' => '',
          'id' => '3',
          '1' => 'warm beer',
          '2' => '',
        ),
      '4' => array( 
          'entity_id' => '2',
          'latest' => '',
          'id' => '4',
          '1' => 'fl* w*',
          '2' => '',
        ),
      '5' => array( 
          'entity_id' => '2',
          'latest' => 'coffee',
          'id' => '5',
          '1' => '',
          '2' => 'coffee',
        ),
      '6' => array( 
          'entity_id' => '2',
          'latest' => 'value 4',
          'id' => '6',
          '1' => '',
          '2' => 'value 4',
        ),
      '7' => array( 
          'entity_id' => '2',
          'latest' => '',
          'id' => '7',
          '1' => 'vegemite',
          '2' => '',
        ),
    ),
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testGetMultipleCustomValues and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/CustomValueTest.php
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