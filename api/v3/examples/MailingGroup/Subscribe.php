<?php
/**
 * Test Generated example of using mailing_group subscribe API.
 *
 *
 * @return array
 *   API result array
 */
function mailing_group_subscribe_example() {
  $params = [
    'email' => 'test@test.test',
    'group_id' => 2,
    'contact_id' => 3,
    'hash' => 'b15de8b64e2cec34',
    'time_stamp' => '20101212121212',
  ];

  try{
    $result = civicrm_api3('mailing_group', 'subscribe', $params);
  }
  catch (CiviCRM_API3_Exception $e) {
    // Handle error here.
    $errorMessage = $e->getMessage();
    $errorCode = $e->getErrorCode();
    $errorData = $e->getExtraParams();
    return [
      'error' => $errorMessage,
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
function mailing_group_subscribe_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 1,
    'values' => [
      '1' => [
        'contact_id' => '3',
        'subscribe_id' => '1',
        'hash' => '67eac7789eaee00',
      ],
    ],
  ];

  return $expectedResult;
}

/**
* This example has been generated from the API test suite.
* The test that created it is called
* testMailerGroupSubscribeGivenContactId
* and can be found in
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/MailingGroupTest.php
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
