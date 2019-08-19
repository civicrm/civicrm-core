{literal}<?php{/literal}
/**
 * Test Generated example demonstrating the {$entity}.{$action} API.
 *
{if !empty($result.deprecated) && is_string($result.deprecated)}
 * @deprecated
 * {$result.deprecated}
{if !$description}
 *
{/if}
{/if}
{if $description}
{foreach from=$description item='line'}
 * {$line}
{/foreach}
 *
{/if}
 * @return array
 *   API result array
 */
function {$function}_example() {literal}{{/literal}
  $params = {$params|@print_array};
{literal}
  try{{/literal}
    $result = civicrm_api3('{$entity}', '{$action}', $params);
{literal}  }
  catch (CiviCRM_API3_Exception $e) {
    // Handle error here.
    $errorMessage = $e->getMessage();
    $errorCode = $e->getErrorCode();
    $errorData = $e->getExtraParams();
    return array(
      'is_error' => 1,
      'error_message' => $errorMessage,
      'error_code' => $errorCode,
      'error_data' => $errorData,
    );
  }{/literal}

  return $result;
{literal}}{/literal}

/**
 * Function returns array of result expected from previous function.
 *
 * @return array
 *   API result array
 */
function {$function}_expectedresult() {literal}{{/literal}

  $expectedResult = {$result|@print_array};

  return $expectedResult;
{literal}}{/literal}

/*
* This example has been generated from the API test suite.
* The test that created it is called "{$testFunction}"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/{$testFile}
*
* You can see the outcome of the API tests at
* https://test.civicrm.org/job/CiviCRM-Core-Matrix/
*
* To Learn about the API read
* https://docs.civicrm.org/dev/en/latest/api/
*
* Browse the API on your own site with the API Explorer. It is in the main
* CiviCRM menu, under: Support > Development > API Explorer.
*
* Read more about testing here
* https://docs.civicrm.org/dev/en/latest/testing/
*
* API Standards documentation:
* https://docs.civicrm.org/dev/en/latest/framework/api-architecture/
*/
