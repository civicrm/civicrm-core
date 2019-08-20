<?php
/**
 * Test Generated example demonstrating the Setting.getdefaults API.
 *
 * Gets defaults setting a variable for a given domain - if no domain is set current is assumed.
 *
 * @return array
 *   API result array
 */
function setting_getdefaults_example() {
  $params = [
    'name' => 'address_format',
  ];

  try{
    $result = civicrm_api3('Setting', 'getdefaults', $params);
  }
  catch (CiviCRM_API3_Exception $e) {
    // Handle error here.
    $errorMessage = $e->getMessage();
    $errorCode = $e->getErrorCode();
    $errorData = $e->getExtraParams();
    return [
      'is_error' => 1,
      'error_message' => $errorMessage,
      'error_code' => $errorCode,
      'error_data' => $errorData,
    ];
  }

  return $result;
}

/**
 * Function returns array of result expected from previous function.
 *
 * @return array
 *   API result array
 */
function setting_getdefaults_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 1,
    'values' => [
      '1' => [
        'address_format' => '{contact.address_name}
{contact.street_address}
{contact.supplemental_address_1}
{contact.supplemental_address_2}
{contact.supplemental_address_3}
{contact.city}{, }{contact.state_province}{ }{contact.postal_code}
{contact.country}',
      ],
    ],
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testGetDefaults"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/SettingTest.php
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
