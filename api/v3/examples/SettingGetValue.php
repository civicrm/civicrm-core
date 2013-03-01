<?php

/*
 Demonstrates getvalue action - intended for runtime use as better caching than get
 */
function setting_getvalue_example(){
$params = array( 
  'version' => 3,
  'name' => 'petition_contacts',
  'group' => 'Campaign Preferences',
);

  $result = civicrm_api( 'setting','getvalue',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function setting_getvalue_expectedresult(){

  $expectedResult = 'Petition Contacts';

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testGetValue and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/SettingTest.php
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