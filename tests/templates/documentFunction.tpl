{literal}<?php{/literal}

/*
 {$description}
 */
function {$function}_example(){literal}{{/literal}
$params = {$params|@print_array};

  $result = civicrm_api( '{$fnPrefix}','{$action}',$params );

  return $result;
{literal}}{/literal}

/*
 * Function returns array of result expected from previous function
 */
function {$function}_expectedresult(){literal}{{/literal}

  $expectedResult = {$result|@print_array};

  return $expectedResult  ;
{literal}}{/literal}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* {$testfunction} and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/{$filename}
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