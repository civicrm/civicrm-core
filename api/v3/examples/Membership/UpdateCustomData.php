<?php
/**
 * Test Generated example demonstrating the Membership.create API.
 *
 * @return array
 *   API result array
 */
function membership_create_example() {
  $params = [
    'contact_id' => 3,
    'membership_type_id' => 1,
    'join_date' => '2009-01-21',
    'start_date' => '2009-01-21',
    'end_date' => '2009-12-21',
    'source' => 'Payment',
    'is_override' => 1,
    'status_id' => 42,
    'custom_1' => 'custom string',
  ];

  try{
    $result = civicrm_api3('Membership', 'create', $params);
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
function membership_create_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 1,
    'values' => [
      '1' => [
        'id' => '1',
        'contact_id' => '3',
        'membership_type_id' => '1',
        'join_date' => '20090121000000',
        'start_date' => '2013-07-29 00:00:00',
        'end_date' => '2013-08-04 00:00:00',
        'source' => 'Payment',
        'status_id' => '42',
        'is_override' => '1',
        'status_override_end_date' => '',
        'owner_membership_id' => '',
        'max_related' => '',
        'is_test' => 0,
        'is_pay_later' => '',
        'contribution_recur_id' => '',
        'campaign_id' => '',
      ],
    ],
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testUpdateWithCustom"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/MembershipTest.php
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
