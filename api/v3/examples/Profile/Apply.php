<?php
/**
 * Test Generated example demonstrating the Profile.apply API.
 *
 * @return array
 *   API result array
 */
function profile_apply_example() {
  $params = array(
    'profile_id' => 31,
    'contact_id' => 3,
    'first_name' => 'abc2',
    'last_name' => 'xyz2',
    'email-Primary' => 'abc2.xyz2@gmail.com',
    'phone-1-1' => '022 321 826',
    'country-1' => '1013',
    'state_province-1' => '1000',
  );

  try{
    $result = civicrm_api3('Profile', 'apply', $params);
  }
  catch (CiviCRM_API3_Exception $e) {
    // Handle error here.
    $errorMessage = $e->getMessage();
    $errorCode = $e->getErrorCode();
    $errorData = $e->getExtraParams();
    return array(
      'error' => $errorMessage,
      'error_code' => $errorCode,
      'error_data' => $errorData,
    );
  }

  return $result;
}

/**
 * Function returns array of result expected from previous function.
 *
 * @return array
 *   API result array
 */
function profile_apply_expectedresult() {

  $expectedResult = array(
    'is_error' => 0,
    'version' => 3,
    'count' => 11,
    'values' => array(
      'contact_type' => 'Individual',
      'contact_sub_type' => '',
      'contact_id' => 3,
      'version' => 3,
      'debug' => 1,
      'profile_id' => 31,
      'first_name' => 'abc2',
      'last_name' => 'xyz2',
      'email' => array(
        '1' => array(
          'location_type_id' => '1',
          'is_primary' => 1,
          'email' => 'abc2.xyz2@gmail.com',
        ),
      ),
      'phone' => array(
        '2' => array(
          'location_type_id' => '1',
          'is_primary' => 1,
          'phone_type_id' => '1',
          'phone' => '022 321 826',
        ),
      ),
      'address' => array(
        '1' => array(
          'location_type_id' => '1',
          'is_primary' => 1,
          'country_id' => '1013',
          'state_province_id' => '1000',
        ),
      ),
    ),
  );

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testProfileApply"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/ProfileTest.php
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
