<?php
/**
 * Test Generated example demonstrating the Email.create API.
 *
 * @return array
 *   API result array
 */
function email_create_example() {
  $params = [
    'contact_id' => 23,
    'email' => 'api@a-team.com',
    'on_hold' => '2',
  ];

  try{
    $result = civicrm_api3('Email', 'create', $params);
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
function email_create_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 26,
    'values' => [
      '26' => [
        'id' => '26',
        'contact_id' => '23',
        'location_type_id' => '1',
        'email' => 'api@a-team.com',
        'is_primary' => '1',
        'is_billing' => '',
        'on_hold' => '2',
        'is_bulkmail' => '',
        'hold_date' => '20190820191652',
        'reset_date' => '',
        'signature_text' => '',
        'signature_html' => '',
      ],
    ],
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testEmailOnHold"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/EmailTest.php
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
