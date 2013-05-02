<?php

/*
 utilises field names
 */
function custom_value_get_example(){
$params = array( 
  'id' => 2,
  'version' => 3,
  'entity_id' => 2,
  'format.field_names' => 1,
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
  'count' => 4,
  'values' => array( 
      'mySingleField' => array( 
          'entity_id' => '2',
          'latest' => 'value 1',
          'id' => 'mySingleField',
          '0' => 'value 1',
        ),
      'Cust_Field' => array( 
          'entity_id' => '2',
          'latest' => 'coffee',
          'id' => 'Cust_Field',
          '1' => '',
          '2' => 'coffee',
        ),
      'field_2' => array( 
          'entity_id' => '2',
          'latest' => 'value 4',
          'id' => 'field_2',
          '1' => '',
          '2' => 'value 4',
        ),
      'field_3' => array( 
          'entity_id' => '2',
          'latest' => '',
          'id' => 'field_3',
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