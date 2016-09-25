<?php
/**
 * Test Generated example demonstrating the Profile.get API.
 *
 * @return array
 *   API result array
 */
function profile_get_example() {
  $params = array(
    'profile_id' => array(
      '0' => 15,
      '1' => 1,
      '2' => 'Billing',
    ),
    'contact_id' => 3,
  );

  try{
    $result = civicrm_api3('Profile', 'get', $params);
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
function profile_get_expectedresult() {

  $expectedResult = array(
    'is_error' => 0,
    'version' => 3,
    'count' => 3,
    'values' => array(
      '15' => array(
        'postal_code-1' => '90210',
        'state_province-1' => '1021',
        'country-1' => '1228',
        'phone-1-1' => '021 512 755',
        'email-Primary' => 'abc1.xyz1@yahoo.com',
        'last_name' => 'xyz1',
        'first_name' => 'abc1',
        'email-primary' => 'abc1.xyz1@yahoo.com',
      ),
      '1' => array(
        'first_name' => 'abc1',
        'last_name' => 'xyz1',
        'street_address-1' => '5 Saint Helier St',
        'city-1' => 'Gotham City',
        'postal_code-1' => '90210',
        'country-1' => '1228',
        'state_province-1' => '1021',
      ),
      'Billing' => array(
        'billing_first_name' => 'abc1',
        'billing_middle_name' => 'J.',
        'billing_last_name' => 'xyz1',
        'billing_street_address-5' => '5 Saint Helier St',
        'billing_city-5' => 'Gotham City',
        'billing_state_province_id-5' => '1021',
        'billing_country_id-5' => '1228',
        'billing_postal_code-5' => '90210',
        'billing-email-5' => 'abc1.xyz1@yahoo.com',
        'email-5' => 'abc1.xyz1@yahoo.com',
      ),
    ),
  );

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testProfileGetMultiple"
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
