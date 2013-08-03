<?php
/**
 * Test Generated example of using activity get API
 * Function demonstrates getting asignee_contact_id & using it to get the contact *
 */
function activity_get_example(){
$params = array(
  'activity_id' => 1,
  'sequential' => 1,
  'return.assignee_contact_id' => 1,
  'api.contact.get' => array(
      'id' => '$value.source_contact_id',
    ),
);

try{
  $result = civicrm_api3('activity', 'get', $params);
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
function activity_get_expectedresult(){

  $expectedResult = array(
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 1,
  'values' => array(
      '0' => array(
          'id' => '1',
          'activity_type_id' => '46',
          'subject' => 'test activity type id',
          'activity_date_time' => '2011-06-02 14:36:13',
          'duration' => '120',
          'location' => 'Pensulvania',
          'details' => 'a test activity',
          'status_id' => '2',
          'priority_id' => '1',
          'is_test' => 0,
          'is_auto' => 0,
          'is_current_revision' => '1',
          'is_deleted' => 0,
          'assignee_contact_id' => array(
              '0' => '3',
            ),
          'source_contact_id' => '1',
          'api.contact.get' => array(
              'is_error' => 0,
              'version' => 3,
              'count' => 1,
              'id' => 1,
              'values' => array(
                  '0' => array(
                      'contact_id' => '1',
                      'contact_type' => 'Individual',
                      'contact_sub_type' => '',
                      'sort_name' => 'Anderson, Anthony',
                      'display_name' => 'Mr. Anthony Anderson II',
                      'do_not_email' => 0,
                      'do_not_phone' => 0,
                      'do_not_mail' => 0,
                      'do_not_sms' => 0,
                      'do_not_trade' => 0,
                      'is_opt_out' => 0,
                      'legal_identifier' => '',
                      'external_identifier' => '',
                      'nick_name' => '',
                      'legal_name' => '',
                      'image_URL' => '',
                      'preferred_mail_format' => 'Both',
                      'first_name' => 'Anthony',
                      'middle_name' => 'J.',
                      'last_name' => 'Anderson',
                      'job_title' => '',
                      'birth_date' => '',
                      'is_deceased' => 0,
                      'deceased_date' => '',
                      'household_name' => '',
                      'organization_name' => '',
                      'sic_code' => '',
                      'contact_is_deleted' => 0,
                      'current_employer' => '',
                      'address_id' => '',
                      'street_address' => '',
                      'supplemental_address_1' => '',
                      'supplemental_address_2' => '',
                      'city' => '',
                      'postal_code_suffix' => '',
                      'postal_code' => '',
                      'geo_code_1' => '',
                      'geo_code_2' => '',
                      'state_province_id' => '',
                      'country_id' => '',
                      'phone_id' => '',
                      'phone_type_id' => '',
                      'phone' => '',
                      'email_id' => '1',
                      'email' => 'anthony_anderson@civicrm.org',
                      'on_hold' => 0,
                      'im_id' => '',
                      'provider_id' => '',
                      'im' => '',
                      'worldregion_id' => '',
                      'world_region' => '',
                      'state_province_name' => '',
                      'state_province' => '',
                      'country' => '',
                      'id' => '1',
                    ),
                ),
            ),
        ),
    ),
);

  return $expectedResult;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testActivityGetGoodID1 and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/ActivityTest.php
*
* You can see the outcome of the API tests at
* http://tests.dev.civicrm.org/trunk/results-api_v3
*
* To Learn about the API read
* http://book.civicrm.org/developer/current/techniques/api/
*
* and review the wiki at
* http://wiki.civicrm.org/confluence/display/CRMDOC/CiviCRM+Public+APIs
*
* Read more about testing here
* http://wiki.civicrm.org/confluence/display/CRM/Testing
*
* API Standards documentation:
* http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
*/