<?php
/**
 * Test Generated example demonstrating the Setting.revert API.
 *
 * Demonstrates reverting a parameter to default value.
 *
 * @return array
 *   API result array
 */
function setting_revert_example() {
  $params = array(
    'name' => 'address_format',
  );

  try{
    $result = civicrm_api3('Setting', 'revert', $params);
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
function setting_revert_expectedresult() {

  $expectedResult = array(
    'is_error' => 0,
    'version' => 3,
    'count' => 5,
    'id' => 1,
    'values' => array(
      'is_error' => 0,
      'version' => 3,
      'count' => 1,
      'id' => 1,
      'values' => array(
        '1' => array(
          'address_format' => '{contact.address_name}
{contact.street_address}
{contact.supplemental_address_1}
{contact.supplemental_address_2}
{contact.supplemental_address_3}
{contact.city}{, }{contact.state_province}{ }{contact.postal_code}
{contact.country}',
        ),
      ),
    ),
  );

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testRevert"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/SettingTest.php
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
