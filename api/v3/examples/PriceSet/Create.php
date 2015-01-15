<?php
/**
 * @file
 * Test Generated API Example.
 * See bottom of this file for more detail.
 */

/**
 * Test Generated example of using price_set create API.
 *
 *
 * @return array
 *   API result array
 */
function price_set_create_example() {
  $params = array(
    'name' => 'some_price_set',
    'title' => 'Some Price Set',
    'is_active' => 1,
    'financial_type_id' => 1,
    'extends' => array(
      '0' => 1,
      '1' => 2,
    ),
  );

  try{
    $result = civicrm_api3('price_set', 'create', $params);
  }
  catch (CiviCRM_API3_Exception $e) {
    // Handle error here.
    $errorMessage = $e->getMessage();
    $errorCode = $e->getErrorCode();
    $errorData = $e->getExtraParams();
    return array(
      'error' => $errorMessage,
      'error_code' => $errorCode,
      'error_data' => $errorData,
    );
  }

  return $result;
}

/**
 * Function returns array of result expected from previous function.
 *
 * @return array
 *   API result array
 */
function price_set_create_expectedresult() {

  $expectedResult = array(
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 16,
    'values' => array(
      '16' => array(
        'id' => '16',
        'domain_id' => '',
        'name' => 'some_price_set',
        'title' => 'Some Price Set',
        'is_active' => '1',
        'help_pre' => '',
        'help_post' => '',
        'javascript' => '',
        'extends' => array(
          '0' => '1',
          '1' => '2',
        ),
        'financial_type_id' => '1',
        'is_quick_config' => '',
        'is_reserved' => '',
      ),
    ),
  );

  return $expectedResult;
}

/**
* This example has been generated from the API test suite.
* The test that created it is called
* testCreatePriceSetForEventAndContribution
* and can be found in
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/PriceSetTest.php
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
