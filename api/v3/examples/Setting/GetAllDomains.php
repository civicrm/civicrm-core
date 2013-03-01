<?php

/*
 shows getting a variable for all domains
 */
function setting_get_example(){
$params = array( 
  'version' => 3,
  'domain_id' => 'all',
  'return' => 'uniq_email_per_site',
);

  $result = civicrm_api( 'setting','Get',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function setting_get_expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'version' => 3,
  'count' => 3,
  'values' => array( 
      '1' => array( 
          'uniq_email_per_site' => '1',
        ),
      '2' => array( 
          'uniq_email_per_site' => '1',
        ),
      '3' => array( 
          'uniq_email_per_site' => '1',
        ),
    ),
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testCreateSettingMultipleDomains and can be found in
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