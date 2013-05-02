<?php

/*
 
 */
function entity_tag_create_example(){
$params = array( 
  'contact_id' => 12,
  'tag_id' => 1,
  'version' => 3,
);

  $result = civicrm_api( 'entity_tag','create',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function entity_tag_create_expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'not_added' => 0,
  'added' => 1,
  'total_count' => 1,
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testContactEntityTagCreate and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/EntityTagTest.php
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