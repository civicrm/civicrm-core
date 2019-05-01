<?php
/**
 * Test Generated example demonstrating the MailSettings.create API.
 *
 * @return array
 *   API result array
 */
function mail_settings_create_example() {
  $params = [
    'domain_id' => 1,
    'name' => 'my mail setting',
    'domain' => 'setting.com',
    'local_part' => 'civicrm+',
    'server' => 'localhost',
    'username' => 'sue',
    'password' => 'pass',
    'is_default' => 1,
  ];

  try{
    $result = civicrm_api3('MailSettings', 'create', $params);
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
function mail_settings_create_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 5,
    'values' => [
      '5' => [
        'id' => '5',
        'domain_id' => '1',
        'name' => 'my mail setting',
        'is_default' => '1',
        'domain' => 'setting.com',
        'localpart' => '',
        'return_path' => '',
        'protocol' => '',
        'server' => 'localhost',
        'port' => '',
        'username' => 'sue',
        'password' => 'pass',
        'is_ssl' => '',
        'source' => '',
      ],
    ],
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testDeleteMailSettings"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/MailSettingsTest.php
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
