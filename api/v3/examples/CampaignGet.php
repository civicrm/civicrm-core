<?php

/*
 
 */
function campaign_get_example(){
$params = array( 
  'version' => 3,
  'title' => 'campaign title',
  'description' => 'Call people, ask for money',
  'created_date' => 'first sat of July 2008',
);

  $result = civicrm_api( 'campaign','get',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function campaign_get_expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 2,
  'values' => array( 
      '2' => array( 
          'id' => '2',
          'name' => 'campaign_title',
          'title' => 'campaign title',
          'description' => 'Call people, ask for money',
          'is_active' => '1',
          'created_date' => '2008-07-05 00:00:00',
        ),
    ),
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testGetCampaign and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/CampaignTest.php
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