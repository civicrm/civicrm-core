<?php
/**
 * Test Generated example demonstrating the Profile.get API.
 *
 * @return array
 *   API result array
 */
function profile_get_example() {
  $params = [
    'profile_id' => [
      '0' => 15,
      '1' => 1,
      '2' => 'Billing',
    ],
    'contact_id' => 5,
  ];

  try{
    $result = civicrm_api3('Profile', 'get', $params);
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
function profile_get_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 3,
    'values' => [
      '15' => [
        'postal_code-1' => '90210',
        'state_province-1' => '1021',
        'country-1' => '1228',
        'phone-1-1' => '021 512 755',
        'email-Primary' => 'abc1.xyz1@yahoo.com',
        'last_name' => 'xyz1',
        'first_name' => 'abc1',
        'email-primary' => 'abc1.xyz1@yahoo.com',
      ],
      '1' => [
        'first_name' => 'abc1',
        'last_name' => 'xyz1',
        'street_address-1' => '5 Saint Helier St',
        'city-1' => 'Gotham City',
        'postal_code-1' => '90210',
        'country-1' => '1228',
        'state_province-1' => '1021',
      ],
      'Billing' => [
        'billing_first_name' => 'abc1',
        'billing_middle_name' => 'J.',
        'billing_last_name' => 'xyz1',
        'billing_street_address-5' => '5 Saint Helier St',
        'billing_city-5' => 'Gotham City',
        'billing_state_province_id-5' => '1021',
        'billing_country_id-5' => '1228',
        'billing_postal_code-5' => '90210',
        'billing-email-5' => 'abc1.xyz1@yahoo.com',
        'email-5' => 'abc1.xyz1@yahoo.com',
      ],
    ],
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testProfileGetMultiple"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/ProfileTest.php
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
