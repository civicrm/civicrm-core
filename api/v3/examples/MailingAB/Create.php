<?php
/**
 * Test Generated example demonstrating the MailingAB.create API.
 *
 * @return array
 *   API result array
 */
function mailing_a_b_create_example() {
  $params = [
    'mailing_id_a' => 1,
    'mailing_id_b' => 2,
    'mailing_id_c' => 3,
    'testing_criteria' => 'subject',
    'winner_criteria' => 'open',
    'declare_winning_time' => '+2 days',
    'group_percentage' => 10,
  ];

  try{
    $result = civicrm_api3('MailingAB', 'create', $params);
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
function mailing_a_b_create_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 1,
    'values' => [
      '1' => [
        'id' => '1',
        'name' => '',
        'status' => '',
        'mailing_id_a' => '1',
        'mailing_id_b' => '2',
        'mailing_id_c' => '3',
        'domain_id' => '1',
        'testing_criteria' => 'subject',
        'winner_criteria' => 'open',
        'specific_url' => '',
        'declare_winning_time' => '20170209023708',
        'group_percentage' => '10',
        'created_id' => '3',
        'created_date' => '2013-07-28 08:49:19',
      ],
    ],
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testMailingABCreateSuccess"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/MailingABTest.php
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
