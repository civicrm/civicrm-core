<?php
/**
 * Test Generated example of using country create API
 * *
 */
function country_create_example(){
$params = array(
  'name' => 'Made Up Land',
  'iso_code' => 'ML',
  'region_id' => 1,
);

try{
  $result = civicrm_api3('country', 'create', $params);
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
function country_create_expectedresult(){

  $expectedResult = array(
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 1,
  'values' => array(
      '1' => array(
          'id' => '1',
          'name' => 'Made Up Land',
          'iso_code' => 'ML',
          'country_code' => '',
          'address_format_id' => '',
          'idd_prefix' => '',
          'ndd_prefix' => '',
          'region_id' => '1',
          'is_province_abbreviated' => '',
        ),
    ),
);

  return $expectedResult;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testCreateCountry and can be found in
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/CountryTest.php
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
