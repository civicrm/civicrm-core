<?php
/**
 * Test Generated example demonstrating the Participant.create API.
 *
 * @return array
 *   API result array
 */
function participant_create_example() {
  $params = [
    'contact_id' => 2,
    'event_id' => 3,
    'status_id' => 1,
    'role_id' => 1,
    'register_date' => '2007-07-21 00:00:00',
    'source' => 'Online Event Registration: API Testing',
    'custom_1' => 'custom string',
  ];

  try{
    $result = civicrm_api3('Participant', 'create', $params);
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
function participant_create_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 4,
    'values' => [
      '4' => [
        'id' => '4',
        'contact_id' => '2',
        'event_id' => '3',
        'status_id' => '1',
        'role_id' => '1',
        'register_date' => '20070721000000',
        'source' => 'Online Event Registration: API Testing',
        'fee_level' => '',
        'is_test' => '',
        'is_pay_later' => '',
        'fee_amount' => '',
        'registered_by_id' => '',
        'discount_id' => '',
        'fee_currency' => '',
        'campaign_id' => '',
        'discount_amount' => '',
        'cart_id' => '',
        'must_wait' => '',
        'transferred_to_contact_id' => '',
      ],
    ],
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testCreateWithCustom"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/ParticipantTest.php
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
