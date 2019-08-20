<?php
/**
 * Test Generated example demonstrating the Email.replace API.
 *
 * @return array
 *   API result array
 */
function email_replace_example() {
  $params = [
    'contact_id' => 17,
    'values' => [
      '0' => [
        'location_type_id' => 34,
        'email' => '1-1@example.com',
        'is_primary' => 1,
      ],
      '1' => [
        'location_type_id' => 34,
        'email' => '1-2@example.com',
        'is_primary' => 0,
      ],
      '2' => [
        'location_type_id' => 34,
        'email' => '1-3@example.com',
        'is_primary' => 0,
      ],
      '3' => [
        'location_type_id' => 35,
        'email' => '2-1@example.com',
        'is_primary' => 0,
      ],
      '4' => [
        'location_type_id' => 35,
        'email' => '2-2@example.com',
        'is_primary' => 0,
      ],
    ],
  ];

  try{
    $result = civicrm_api3('Email', 'replace', $params);
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
function email_replace_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 5,
    'values' => [
      '13' => [
        'id' => '13',
        'contact_id' => '17',
        'location_type_id' => '34',
        'email' => '1-1@example.com',
        'is_primary' => '1',
        'is_billing' => '',
        'on_hold' => 0,
        'is_bulkmail' => '',
        'hold_date' => '',
        'reset_date' => '',
        'signature_text' => '',
        'signature_html' => '',
      ],
      '14' => [
        'id' => '14',
        'contact_id' => '17',
        'location_type_id' => '34',
        'email' => '1-2@example.com',
        'is_primary' => 0,
        'is_billing' => '',
        'on_hold' => 0,
        'is_bulkmail' => '',
        'hold_date' => '',
        'reset_date' => '',
        'signature_text' => '',
        'signature_html' => '',
      ],
      '15' => [
        'id' => '15',
        'contact_id' => '17',
        'location_type_id' => '34',
        'email' => '1-3@example.com',
        'is_primary' => 0,
        'is_billing' => '',
        'on_hold' => 0,
        'is_bulkmail' => '',
        'hold_date' => '',
        'reset_date' => '',
        'signature_text' => '',
        'signature_html' => '',
      ],
      '16' => [
        'id' => '16',
        'contact_id' => '17',
        'location_type_id' => '35',
        'email' => '2-1@example.com',
        'is_primary' => 0,
        'is_billing' => '',
        'on_hold' => 0,
        'is_bulkmail' => '',
        'hold_date' => '',
        'reset_date' => '',
        'signature_text' => '',
        'signature_html' => '',
      ],
      '17' => [
        'id' => '17',
        'contact_id' => '17',
        'location_type_id' => '35',
        'email' => '2-2@example.com',
        'is_primary' => 0,
        'is_billing' => '',
        'on_hold' => 0,
        'is_bulkmail' => '',
        'hold_date' => '',
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
* The test that created it is called "testReplaceEmail"
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
