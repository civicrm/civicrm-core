<?php

/*
 
 */
function batch_update_example(){
$params = array( 
  'name' => 'New_Batch_04',
  'title' => 'New Batch 04',
  'description' => 'This is description for New Batch 04',
  'total' => '400.44',
  'item_count' => 4,
  'version' => 3,
  'id' => 3,
);

  $result = civicrm_api( 'batch','update',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function batch_update_expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 3,
  'values' => array( 
      '3' => array( 
          'id' => '3',
          'name' => 'New_Batch_04',
          'title' => 'New Batch 04',
          'description' => 'This is description for New Batch 04',
          'created_id' => '',
          'created_date' => '',
          'modified_id' => '',
          'modified_date' => '2012-11-14 16:02:35',
          'saved_search_id' => '',
          'status_id' => '',
          'type_id' => '',
          'mode_id' => '',
          'total' => '400.44',
          'item_count' => '4',
          'payment_instrument_id' => '',
          'exported_date' => '',
        ),
    ),
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testUpdate and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/BatchTest.php
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