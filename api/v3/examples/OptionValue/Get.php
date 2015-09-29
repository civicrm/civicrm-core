<?php
/**
 * Test Generated example demonstrating the OptionValue.get API.
 *
 * @return array
 *   API result array
 */
function option_value_get_example() {
  $params = array(
    'option_group_id' => 1,
  );

  try{
    $result = civicrm_api3('OptionValue', 'get', $params);
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
function option_value_get_expectedresult() {

  $expectedResult = array(
    'is_error' => 0,
    'version' => 3,
    'count' => 5,
    'values' => array(
      '1' => array(
        'id' => '1',
        'option_group_id' => '1',
        'label' => 'Phone',
        'value' => '1',
        'filter' => 0,
        'weight' => '1',
        'is_optgroup' => 0,
        'is_reserved' => 0,
        'is_active' => '1',
      ),
      '2' => array(
        'id' => '2',
        'option_group_id' => '1',
        'label' => 'Email',
        'value' => '2',
        'filter' => 0,
        'weight' => '2',
        'is_optgroup' => 0,
        'is_reserved' => 0,
        'is_active' => '1',
      ),
      '3' => array(
        'id' => '3',
        'option_group_id' => '1',
        'label' => 'Postal Mail',
        'value' => '3',
        'filter' => 0,
        'weight' => '3',
        'is_optgroup' => 0,
        'is_reserved' => 0,
        'is_active' => '1',
      ),
      '4' => array(
        'id' => '4',
        'option_group_id' => '1',
        'label' => 'SMS',
        'value' => '4',
        'filter' => 0,
        'weight' => '4',
        'is_optgroup' => 0,
        'is_reserved' => 0,
        'is_active' => '1',
      ),
      '5' => array(
        'id' => '5',
        'option_group_id' => '1',
        'label' => 'Fax',
        'value' => '5',
        'filter' => 0,
        'weight' => '5',
        'is_optgroup' => 0,
        'is_reserved' => 0,
        'is_active' => '1',
      ),
    ),
  );

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testGetOptionGroup"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/OptionValueTest.php
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
