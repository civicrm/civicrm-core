{literal}<?php{/literal}
/**
 * Test Generated example of using {$fnPrefix} {$action} API
 *{if $description} {$description}{/if}
 *
 */
function {$function}_example(){literal}{{/literal}
$params = {$params|@print_array};
{literal}
try{{/literal}
  $result = civicrm_api3('{$fnPrefix}', '{$action}', $params);
{literal}}
catch (CiviCRM_API3_Exception $e) {
  // handle error here
  $errorMessage = $e->getMessage();
  $errorCode = $e->getErrorCode();
  $errorData = $e->getExtraParams();
  return array('error' => $errorMessage, 'error_code' => $errorCode, 'error_data' => $errorData);
}{/literal}

return $result;
{literal}}{/literal}

/**
 * Function returns array of result expected from previous function
 */
function {$function}_expectedresult(){literal}{{/literal}

  $expectedResult = {$result|@print_array};

  return $expectedResult;
{literal}}{/literal}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* {$testfunction} and can be found in
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/{$filename}
*
* You can see the outcome of the API tests at
* https://test.civicrm.org/job/CiviCRM-master-git/
*
* To Learn about the API read
* http://wiki.civicrm.org/confluence/display/CRMDOC/Using+the+API
*
* Browse the api on your own site with the api explorer
* http://MYSITE.ORG/path/to/civicrm/api/explorer
*
* Read more about testing here
* http://wiki.civicrm.org/confluence/display/CRM/Testing
*
* API Standards documentation:
* http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
*/
