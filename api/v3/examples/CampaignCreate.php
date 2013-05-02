<?php

/*
 Create a campaign - Note use of relative dates here http://www.php.net/manual/en/datetime.formats.relative.php
 */
function campaign_create_example(){
$params = array( 
  'version' => 3,
  'title' => 'campaign title',
  'description' => 'Call people, ask for money',
  'created_date' => 'first sat of July 2008',
);

  $result = civicrm_api( 'campaign','create',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function campaign_create_expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 1,
  'values' => array( 
      '1' => array( 
          'id' => '1',
          'name' => 'campaign_title',
          'title' => 'campaign title',
          'description' => 'Call people, ask for money',
          'start_date' => '',
          'end_date' => '',
          'campaign_type_id' => '',
          'status_id' => '',
          'external_identifier' => '',
          'parent_id' => '',
          'is_active' => '',
          'created_id' => '',
          'created_date' => '20080705000000',
          'last_modified_id' => '',
          'last_modified_date' => '',
          'goal_general' => '',
          'goal_revenue' => '',
        ),
    ),
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testCreateCampaign and can be found in
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