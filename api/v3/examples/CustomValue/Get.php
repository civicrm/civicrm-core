<?php
/**
 * Test Generated example demonstrating the CustomValue.get API.
 *
 * This demonstrates the use of CustomValue get to fetch single and multi-valued custom data.
 *
 * @return array
 *   API result array
 */
function custom_value_get_example() {
  $params = array(
    'id' => 2,
    'entity_id' => 2,
  );

  try{
    $result = civicrm_api3('CustomValue', 'get', $params);
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
function custom_value_get_expectedresult() {

  $expectedResult = array(
    'is_error' => 0,
    'version' => 3,
    'count' => 7,
    'values' => array(
      '1' => array(
        'entity_id' => '2',
        'latest' => 'value 1',
        'id' => '1',
      ),
      '2' => array(
        'entity_id' => '2',
        'latest' => 'value 3',
        'id' => '2',
        '1' => 'value 2',
        '2' => 'value 3',
      ),
      '3' => array(
        'entity_id' => '2',
        'latest' => '',
        'id' => '3',
        '1' => 'warm beer',
        '2' => '',
      ),
      '4' => array(
        'entity_id' => '2',
        'latest' => '',
        'id' => '4',
        '1' => 'fl* w*',
        '2' => '',
      ),
      '5' => array(
        'entity_id' => '2',
        'latest' => 'coffee',
        'id' => '5',
        '1' => 'defaultValue',
        '2' => 'coffee',
      ),
      '6' => array(
        'entity_id' => '2',
        'latest' => 'value 4',
        'id' => '6',
        '1' => '',
        '2' => 'value 4',
      ),
      '7' => array(
        'entity_id' => '2',
        'latest' => '',
        'id' => '7',
        '1' => 'vegemite',
        '2' => '',
      ),
    ),
  );

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testGetMultipleCustomValues"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/CustomValueTest.php
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
