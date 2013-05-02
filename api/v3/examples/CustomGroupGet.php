<?php

/*
 
 */
function custom_group_get_example(){
$params = array( 
  'version' => 3,
);

  $result = civicrm_api( 'custom_group','get',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function custom_group_get_expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 1,
  'values' => array( 
      '1' => array( 
          'id' => '1',
          'name' => 'test_group_1',
          'title' => 'Test_Group_1',
          'extends' => 'Individual',
          'style' => 'Inline',
          'collapse_display' => '1',
          'help_pre' => 'This is Pre Help For Test Group 1',
          'help_post' => 'This is Post Help For Test Group 1',
          'weight' => '2',
          'is_active' => '1',
          'table_name' => 'civicrm_value_test_group_1_1',
          'is_multiple' => 0,
          'collapse_adv_display' => 0,
        ),
    ),
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testGetCustomGroupSuccess and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/CustomGroupTest.php
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