<?php

/*
 
 */
function uf_field_get_example(){
$params = array( 
  'version' => 3,
);

  $result = civicrm_api( 'uf_field','get',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function uf_field_get_expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 1,
  'values' => array( 
      '1' => array( 
          'id' => '1',
          'uf_group_id' => '11',
          'field_name' => 'phone',
          'is_active' => '1',
          'is_view' => 0,
          'is_required' => 0,
          'weight' => '1',
          'visibility' => 'Public Pages and Listings',
          'in_selector' => 0,
          'is_searchable' => '1',
          'location_type_id' => '1',
          'phone_type_id' => '1',
          'label' => 'Test Phone',
          'field_type' => 'Contact',
          'is_multi_summary' => 0,
        ),
    ),
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testGetUFFieldSuccess and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/UFFieldTest.php
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