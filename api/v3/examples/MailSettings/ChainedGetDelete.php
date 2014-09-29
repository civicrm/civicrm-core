<?php
/**
 * Test Generated example of using mail_settings get API
 * demonstrates get + delete in the same call *
 */
function mail_settings_get_example(){
$params = array(
  'title' => 'MailSettings title',
  'api.MailSettings.delete' => 1,
);

try{
  $result = civicrm_api3('mail_settings', 'get', $params);
}
catch (CiviCRM_API3_Exception $e) {
  // handle error here
  $errorMessage = $e->getMessage();
  $errorCode = $e->getErrorCode();
  $errorData = $e->getExtraParams();
  return array('error' => $errorMessage, 'error_code' => $errorCode, 'error_data' => $errorData);
}

return $result;
}

/**
 * Function returns array of result expected from previous function
 */
function mail_settings_get_expectedresult(){

  $expectedResult = array(
  'is_error' => 0,
  'version' => 3,
  'count' => 2,
  'values' => array(
      '1' => array(
          'id' => '1',
          'domain_id' => '1',
          'name' => 'default',
          'is_default' => 0,
          'domain' => 'EXAMPLE.ORG',
          'api.MailSettings.delete' => array(
              'is_error' => 0,
              'version' => 3,
              'count' => 1,
              'values' => 1,
            ),
        ),
      '6' => array(
          'id' => '6',
          'domain_id' => '1',
          'name' => 'my mail setting',
          'is_default' => '1',
          'domain' => 'setting.com',
          'server' => 'localhost',
          'username' => 'sue',
          'password' => 'pass',
          'is_ssl' => 0,
          'api.MailSettings.delete' => array(
              'is_error' => 0,
              'version' => 3,
              'count' => 1,
              'values' => 1,
            ),
        ),
    ),
);

  return $expectedResult;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testGetMailSettingsChainDelete and can be found in
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/MailSettingsTest.php
*
* You can see the outcome of the API tests at
* https://test.civicrm.org/job/CiviCRM-master-git/
*
* To Learn about the API read
* http://wiki.civicrm.org/confluence/display/CRMDOC/Using+the+API
*
* Browse the api on your own site with the api explorer
* http://MYSITE.ORG/path/to/civicrm/api/explorer
*
* Read more about testing here
* http://wiki.civicrm.org/confluence/display/CRM/Testing
*
* API Standards documentation:
* http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
*/
