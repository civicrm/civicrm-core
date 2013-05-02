<?php

/*
 
 */
function uf_join_create_example(){
$params = array( 
  'module' => 'CiviContribute',
  'entity_table' => 'civicrm_contribution_page',
  'entity_id' => 1,
  'weight' => 1,
  'uf_group_id' => 11,
  'is_active' => 1,
  'version' => 3,
  'sequential' => 1,
);

  $result = civicrm_api( 'uf_join','create',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function uf_join_create_expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 0,
  'values' => array( 
      '0' => array( 
          'id' => '1',
          'is_active' => '1',
          'module' => 'CiviContribute',
          'entity_table' => 'civicrm_contribution_page',
          'entity_id' => '1',
          'weight' => '1',
          'uf_group_id' => '11',
        ),
    ),
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testCreateUFJoin and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/UFJoinTest.php
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