<?php

/*
 demonstrates get + delete in the same call
 */
function survey_get_example(){
$params = array( 
  'version' => 3,
  'title' => 'survey title',
  'api.survey.delete' => 1,
);

  $result = civicrm_api( 'survey','get',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function survey_get_expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 2,
  'values' => array( 
      '2' => array( 
          'id' => '2',
          'title' => 'survey title',
          'activity_type_id' => '30',
          'instructions' => 'Call people, ask for money',
          'max_number_of_contacts' => '12',
          'is_active' => '1',
          'is_default' => 0,
          'created_date' => '2013-02-04 22:43:26',
          'bypass_confirm' => 0,
          'api.survey.delete' => array( 
              'is_error' => 0,
              'version' => 3,
              'count' => 1,
              'values' => true,
            ),
        ),
    ),
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testGetSurveyChainDelete and can be found in
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