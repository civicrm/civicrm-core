<?php
/**
 * Test Generated example demonstrating the Domain.get API.
 *
 * @return array
 *   API result array
 */
function domain_get_example() {
  $params = [
    'sequential' => 1,
  ];

  try{
    $result = civicrm_api3('Domain', 'get', $params);
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
function domain_get_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 2,
    'values' => [
      '0' => [
        'id' => '1',
        'name' => 'Default Domain Name',
        'version' => '4.6.alpha1',
        'contact_id' => '3',
        'locale_custom_strings' => 'a:1:{s:5:\"en_US\";a:0:{}}',
        'domain_email' => 'my@email.com',
        'domain_phone' => [
          'phone_type' => 'Phone',
          'phone' => '456-456',
        ],
        'domain_address' => [
          'street_address' => '45 Penny Lane',
          'supplemental_address_1' => '',
          'supplemental_address_2' => '',
          'supplemental_address_3' => '',
          'city' => '',
          'state_province_id' => '',
          'postal_code' => '',
          'country_id' => '',
          'geo_code_1' => '',
          'geo_code_2' => '',
        ],
        'from_name' => 'FIXME',
        'from_email' => 'info@EXAMPLE.ORG',
        'domain_version' => '4.6.alpha1',
      ],
      '1' => [
        'id' => '2',
        'name' => 'Second Domain',
        'version' => '4.6.alpha1',
        'contact_id' => '2',
        'domain_email' => '\"Domain Email\" <domainemail2@example.org>',
        'domain_phone' => [
          'phone_type' => 'Phone',
          'phone' => '204 555-1001',
        ],
        'domain_address' => [
          'street_address' => '15 Main St',
          'supplemental_address_1' => '',
          'supplemental_address_2' => '',
          'supplemental_address_3' => '',
          'city' => 'Collinsville',
          'state_province_id' => '1006',
          'postal_code' => '6022',
          'country_id' => '1228',
          'geo_code_1' => '41.8328',
          'geo_code_2' => '-72.9253',
        ],
        'from_name' => 'FIXME',
        'from_email' => 'info@EXAMPLE.ORG',
        'domain_version' => '4.6.alpha1',
      ],
    ],
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testGet"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/DomainTest.php
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
