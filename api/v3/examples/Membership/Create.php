<?php
/**
 * Test Generated example demonstrating the Membership.create API.
 *
 * @return array
 *   API result array
 */
function membership_create_example() {
  $params = [
    'contact_id' => 79,
    'membership_type_id' => 47,
    'join_date' => '2006-01-21',
    'start_date' => '2006-01-21',
    'end_date' => '2006-12-21',
    'source' => 'Payment',
    'is_override' => 1,
    'status_id' => 30,
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
        'contact_id' => '79',
        'membership_type_id' => '47',
        'join_date' => '20060121000000',
        'start_date' => '2013-07-29 00:00:00',
        'end_date' => '2013-08-04 00:00:00',
        'source' => 'Payment',
        'status_id' => '30',
        'is_override' => '1',
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
* The test that created it is called "testMembershipCreate"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/MembershipTest.php
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
