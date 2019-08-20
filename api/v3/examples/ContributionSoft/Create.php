<?php
/**
 * Test Generated example demonstrating the ContributionSoft.create API.
 *
 * @return array
 *   API result array
 */
function contribution_soft_create_example() {
  $params = [
    'contribution_id' => 6,
    'contact_id' => 19,
    'amount' => '10',
    'currency' => 'USD',
    'soft_credit_type_id' => 5,
  ];

  try{
    $result = civicrm_api3('ContributionSoft', 'create', $params);
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
function contribution_soft_create_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 5,
    'values' => [
      '5' => [
        'id' => '5',
        'contribution_id' => '6',
        'contact_id' => '19',
        'amount' => '10',
        'currency' => 'USD',
        'pcp_id' => '',
        'pcp_display_in_roll' => '',
        'pcp_roll_nickname' => '',
        'pcp_personal_note' => '',
        'soft_credit_type_id' => '5',
      ],
    ],
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testCreateContributionSoft"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/ContributionSoftTest.php
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
