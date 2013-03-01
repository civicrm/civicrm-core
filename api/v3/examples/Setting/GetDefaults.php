<?php

/*
 gets defaults setting a variable for a given domain - if no domain is set current is assumed
 */
function setting_getdefaults_example(){
$params = array( 
  'version' => 3,
  'name' => 'address_format',
);

  $result = civicrm_api( 'setting','getdefaults',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function setting_getdefaults_expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 1,
  'values' => array( 
      '1' => array( 
          'address_format' => '{contact.address_name}
{contact.street_address}
{contact.supplemental_address_1}
{contact.supplemental_address_2}
{contact.city}{, }{contact.state_province}{ }{contact.postal_code}
{contact.country}',
        ),
    ),
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testGetDefaults and can be found in
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