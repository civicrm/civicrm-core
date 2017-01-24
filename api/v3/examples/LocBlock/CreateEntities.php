<?php
/**
 * Test Generated example demonstrating the LocBlock.create API.
 *
 * Create entities and locBlock in 1 api call.
 *
 * @return array
 *   API result array
 */
function loc_block_create_example() {
  $params = array(
    'email' => array(
      'location_type_id' => 1,
      'email' => 'test2@loc.block',
    ),
    'phone' => array(
      'location_type_id' => 1,
      'phone' => '987654321',
    ),
    'phone_2' => array(
      'location_type_id' => 1,
      'phone' => '456-7890',
    ),
    'address' => array(
      'location_type_id' => 1,
      'street_address' => '987654321',
    ),
  );

  try{
    $result = civicrm_api3('LocBlock', 'create', $params);
  }
  catch (CiviCRM_API3_Exception $e) {
    // Handle error here.
    $errorMessage = $e->getMessage();
    $errorCode = $e->getErrorCode();
    $errorData = $e->getExtraParams();
    return array(
      'is_error' => 1,
      'error_message' => $errorMessage,
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
function loc_block_create_expectedresult() {

  $expectedResult = array(
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 3,
    'values' => array(
      '3' => array(
        'address' => array(
          'id' => '3',
          'location_type_id' => '1',
          'is_primary' => 0,
          'is_billing' => 0,
          'street_address' => '987654321',
          'manual_geo_code' => 0,
        ),
        'email' => array(
          'id' => '4',
          'contact_id' => '',
          'location_type_id' => '1',
          'email' => 'test2@loc.block',
          'is_primary' => 0,
          'is_billing' => '',
          'on_hold' => '',
          'is_bulkmail' => '',
          'hold_date' => '',
          'reset_date' => '',
          'signature_text' => '',
          'signature_html' => '',
        ),
        'phone' => array(
          'id' => '3',
          'contact_id' => '',
          'location_type_id' => '1',
          'is_primary' => 0,
          'is_billing' => '',
          'mobile_provider_id' => '',
          'phone' => '987654321',
          'phone_ext' => '',
          'phone_numeric' => '',
          'phone_type_id' => '',
        ),
        'phone_2' => array(
          'id' => '4',
          'contact_id' => '',
          'location_type_id' => '1',
          'is_primary' => 0,
          'is_billing' => '',
          'mobile_provider_id' => '',
          'phone' => '456-7890',
          'phone_ext' => '',
          'phone_numeric' => '',
          'phone_type_id' => '',
        ),
        'id' => '3',
        'address_id' => '3',
        'email_id' => '4',
        'phone_id' => '3',
        'im_id' => '',
        'address_2_id' => '',
        'email_2_id' => '',
        'phone_2_id' => '4',
        'im_2_id' => '',
      ),
    ),
  );

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testCreateLocBlockEntities"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/LocBlockTest.php
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
