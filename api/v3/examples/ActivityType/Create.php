<?php
/**
 * Test Generated example demonstrating the ActivityType.create API.
 *
 * @deprecated
 * The ActivityType api is deprecated. Please use the OptionValue api instead.
 *
 * @return array
 *   API result array
 */
function activity_type_create_example() {
  $params = array(
    'weight' => '2',
    'label' => 'send out letters',
    'filter' => 0,
    'is_active' => 1,
    'is_optgroup' => 1,
    'is_default' => 0,
  );

  try{
    $result = civicrm_api3('ActivityType', 'create', $params);
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
function activity_type_create_expectedresult() {

  $expectedResult = array(
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 784,
    'values' => array(
      '784' => array(
        'id' => '784',
        'option_group_id' => '2',
        'label' => 'send out letters',
        'value' => '51',
        'name' => 'send out letters',
        'grouping' => '',
        'filter' => 0,
        'is_default' => 0,
        'weight' => '2',
        'description' => '',
        'is_optgroup' => '1',
        'is_reserved' => '',
        'is_active' => '1',
        'component_id' => '',
        'domain_id' => '',
        'visibility_id' => '',
      ),
    ),
    'deprecated' => 'The ActivityType api is deprecated. Please use the OptionValue api instead.',
  );

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testActivityTypeCreate"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/ActivityTypeTest.php
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
