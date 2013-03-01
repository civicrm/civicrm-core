<?php

/*
 
 */
function survey_create_example(){
$params = array( 
  'version' => 3,
  'title' => 'survey title',
  'activity_type_id' => '30',
  'max_number_of_contacts' => 12,
  'instructions' => 'Call people, ask for money',
);

  $result = civicrm_api( 'survey','create',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function survey_create_expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 1,
  'values' => array( 
      '1' => array( 
          'id' => '1',
          'title' => 'survey title',
          'campaign_id' => '',
          'activity_type_id' => '30',
          'recontact_interval' => '',
          'instructions' => 'Call people, ask for money',
          'release_frequency' => '',
          'max_number_of_contacts' => '12',
          'default_number_of_contacts' => '',
          'is_active' => '',
          'is_default' => '',
          'created_id' => '',
          'created_date' => '20130204224326',
          'last_modified_id' => '',
          'last_modified_date' => '',
          'result_id' => '',
          'bypass_confirm' => '',
          'thankyou_title' => '',
          'thankyou_text' => '',
        ),
    ),
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testCreateSurvey and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/SurveyTest.php
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