<?php
/**
 * Test Generated example demonstrating the PriceSet.create API.
 *
 * @return array
 *   API result array
 */
function price_set_create_example() {
  $params = array(
    'entity_table' => 'civicrm_event',
    'entity_id' => 1,
    'name' => 'event price',
    'title' => 'event price',
    'extends' => 1,
  );

  try{
    $result = civicrm_api3('PriceSet', 'create', $params);
  }
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
    'id' => 18,
    'values' => array(
      '18' => array(
        'id' => '18',
        'domain_id' => '',
        'name' => 'event price',
        'title' => 'event price',
        'is_active' => '',
        'help_pre' => '',
        'help_post' => '',
        'javascript' => '',
        'extends' => '1',
        'financial_type_id' => '',
        'is_quick_config' => '',
        'is_reserved' => '',
        'min_amount' => '',
      ),
    ),
  );

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testEventPriceSet"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/PriceSetTest.php
*
* You can see the outcome of the API tests at
* https://test.civicrm.org/job/CiviCRM-master-git/
*
* To Learn about the API read
* http://wiki.civicrm.org/confluence/display/CRMDOC/Using+the+API
*
* Browse the api on your own site with the api explorer
* http://MYSITE.ORG/path/to/civicrm/api
*
* Read more about testing here
* http://wiki.civicrm.org/confluence/display/CRM/Testing
*
* API Standards documentation:
* http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
*/
