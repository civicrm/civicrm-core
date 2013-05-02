<?php

/*
 demonstrates use of BETWEEN filter
 */
function relationship_get_example(){
$params = array( 
  'version' => 3,
  'relationship_type_id' => array( 
      'BETWEEN' => array( 
          '0' => 34,
          '1' => 36,
        ),
    ),
);

  $result = civicrm_api( 'relationship','get',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function relationship_get_expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'version' => 3,
  'count' => 3,
  'values' => array( 
      '2' => array( 
          'id' => '2',
          'contact_id_a' => '72',
          'contact_id_b' => '73',
          'relationship_type_id' => '34',
          'start_date' => '2008-12-20',
          'is_active' => '1',
          'description' => '',
          'is_permission_a_b' => 0,
          'is_permission_b_a' => 0,
          'custom_1' => 'xyz',
          'custom_1_-1' => 'xyz',
          'custom_3' => '07/11/2009',
          'custom_3_-1' => '07/11/2009',
          'custom_4' => 'http://civicrm.org',
          'custom_4_-1' => 'http://civicrm.org',
        ),
      '3' => array( 
          'id' => '3',
          'contact_id_a' => '72',
          'contact_id_b' => '73',
          'relationship_type_id' => '35',
          'start_date' => '2008-12-20',
          'is_active' => '1',
          'description' => '',
          'is_permission_a_b' => 0,
          'is_permission_b_a' => 0,
          'custom_1' => 'xyz',
          'custom_1_-1' => 'xyz',
          'custom_3' => '07/11/2009',
          'custom_3_-1' => '07/11/2009',
          'custom_4' => 'http://civicrm.org',
          'custom_4_-1' => 'http://civicrm.org',
        ),
      '4' => array( 
          'id' => '4',
          'contact_id_a' => '72',
          'contact_id_b' => '73',
          'relationship_type_id' => '36',
          'start_date' => '2008-12-20',
          'is_active' => '1',
          'description' => '',
          'is_permission_a_b' => 0,
          'is_permission_b_a' => 0,
          'custom_1' => 'xyz',
          'custom_1_-1' => 'xyz',
          'custom_3' => '07/11/2009',
          'custom_3_-1' => '07/11/2009',
          'custom_4' => 'http://civicrm.org',
          'custom_4_-1' => 'http://civicrm.org',
        ),
    ),
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testGetTypeOperators and can be found in
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