<?php

/*
 
 */
function relationship_delete_example(){
$params = array( 
  'contact_id_a' => 51,
  'contact_id_b' => 52,
  'relationship_type_id' => 26,
  'start_date' => '2008-12-20',
  'is_active' => 1,
  'version' => 3,
);

  $result = civicrm_api( 'relationship','delete',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function relationship_delete_expectedresult(){

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
* testRelationshipDelete and can be found in
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