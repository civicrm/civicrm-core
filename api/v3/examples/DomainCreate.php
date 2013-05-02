<?php

/*
 
 */
function domain_create_example(){
$params = array( 
  'name' => 'A-team domain',
  'description' => 'domain of chaos',
  'version' => 3,
  'domain_version' => '4.2',
  'contact_id' => 6,
);

  $result = civicrm_api( 'domain','create',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function domain_create_expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 3,
  'values' => array( 
      '3' => array( 
          'id' => '3',
          'name' => 'A-team domain',
          'description' => 'domain of chaos',
          'config_backend' => '',
          'version' => '4.2',
          'contact_id' => '6',
          'locales' => '',
          'locale_custom_strings' => '',
        ),
    ),
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testCreate and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/DomainTest.php
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