<?php
/**
 * Test Generated example demonstrating the Phone.create API.
 *
 * @return array
 *   API result array
 */
function phone_create_example() {
  $params = array(
    'contact_id' => 3,
    'location_type_id' => 6,
    'phone' => '(123) 456-7890',
    'is_primary' => 1,
    'phone_type_id' => 1,
  );

  try{
    $result = civicrm_api3('Phone', 'create', $params);
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
function phone_create_expectedresult() {

  $expectedResult = array(
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 2,
    'values' => array(
      '2' => array(
        'id' => '2',
        'contact_id' => '3',
        'location_type_id' => '6',
        'is_primary' => '1',
        'is_billing' => '',
        'mobile_provider_id' => '',
        'phone' => '(123) 456-7890',
        'phone_ext' => '',
        'phone_numeric' => '',
        'phone_type_id' => '1',
      ),
    ),
  );

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testCreatePhone"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/PhoneTest.php
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
