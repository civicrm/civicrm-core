<?php

/*
 Demonstrates retrieving options for a custom field
 */
function contact_getoptions_example(){
$params = array( 
  'field' => 'custom_1',
  'version' => 3,
);

  $result = civicrm_api( 'contact','getoptions',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function contact_getoptions_expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'version' => 3,
  'count' => 2,
  'values' => array( 
      '1' => 'Label1',
      '2' => 'Label2',
    ),
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testCustomFieldCreateWithOptionValues and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/ContactTest.php
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