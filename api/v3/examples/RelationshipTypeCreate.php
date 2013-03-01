<?php

/*
 
 */
function relationship_type_create_example(){
$params = array( 
  'name_a_b' => 'Relation 1 for relationship type create',
  'name_b_a' => 'Relation 2 for relationship type create',
  'contact_type_a' => 'Individual',
  'contact_type_b' => 'Organization',
  'is_reserved' => 1,
  'is_active' => 1,
  'version' => 3,
  'sequential' => 1,
);

  $result = civicrm_api( 'relationship_type','create',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function relationship_type_create_expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 1,
  'values' => array( 
      '0' => array( 
          'id' => '1',
          'name_a_b' => 'Relation 1 for relationship type create',
          'label_a_b' => 'Relation 1 for relationship type create',
          'name_b_a' => 'Relation 2 for relationship type create',
          'label_b_a' => 'Relation 2 for relationship type create',
          'description' => '',
          'contact_type_a' => 'Individual',
          'contact_type_b' => 'Organization',
          'contact_sub_type_a' => '',
          'contact_sub_type_b' => '',
          'is_reserved' => '1',
          'is_active' => '1',
        ),
    ),
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testRelationshipTypeCreate and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/RelationshipTypeTest.php
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