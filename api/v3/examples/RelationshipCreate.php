<?php

/*
 
 */
function relationship_create_example(){
$params = array( 
  'contact_id_a' => 27,
  'contact_id_b' => 28,
  'relationship_type_id' => 18,
  'start_date' => '2010-10-30',
  'end_date' => '2010-12-30',
  'is_active' => 1,
  'note' => 'note',
  'version' => 3,
);

  $result = civicrm_api( 'relationship','create',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function relationship_create_expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 1,
  'values' => array( 
      '1' => array( 
          'id' => '1',
          'moreIDs' => '1',
        ),
    ),
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testRelationshipCreate and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/RelationshipTest.php
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