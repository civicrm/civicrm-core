<?php

/*
 
 */
function note_get_example(){
$params = array( 
  'entity_table' => 'civicrm_contact',
  'entity_id' => 1,
  'note' => 'Hello!!! m testing Note',
  'contact_id' => 1,
  'modified_date' => '2011-01-31',
  'subject' => 'Test Note',
  'version' => 3,
);

  $result = civicrm_api( 'note','get',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function note_get_expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 1,
  'values' => array( 
      '1' => array( 
          'id' => '1',
          'entity_table' => 'civicrm_contact',
          'entity_id' => '1',
          'note' => 'hello I am testing Note',
          'contact_id' => '1',
          'modified_date' => '2012-11-14 16:02:35',
          'subject' => 'Test Note',
          'privacy' => 0,
        ),
    ),
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testGet and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/NoteTest.php
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