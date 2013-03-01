<?php

/*
 demonstrates use of Sort param (available in many api functions). Also, getsingle
 */
function option_value_getsingle_example(){
$params = array( 
  'option_group_id' => 1,
  'version' => 3,
  'options' => array( 
      'sort' => 'label DESC',
      'limit' => 1,
    ),
);

  $result = civicrm_api( 'option_value','getsingle',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function option_value_getsingle_expectedresult(){

  $expectedResult = array( 
  'id' => '4',
  'option_group_id' => '1',
  'label' => 'SMS',
  'value' => '4',
  'filter' => 0,
  'weight' => '4',
  'is_optgroup' => 0,
  'is_reserved' => 0,
  'is_active' => '1',
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testGetSingleValueOptionValueSort and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/OptionValueTest.php
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