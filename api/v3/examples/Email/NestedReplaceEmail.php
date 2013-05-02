<?php

/*
 example demonstrates use of Replace in a nested API call
 */
function email_replace_example(){
$params = array( 
  'version' => 3,
  'id' => 10,
  'api.email.replace' => array( 
      'values' => array( 
          '0' => array( 
              'location_type_id' => 20,
              'email' => '1-1@example.com',
              'is_primary' => 1,
            ),
          '1' => array( 
              'location_type_id' => 20,
              'email' => '1-2@example.com',
              'is_primary' => 0,
            ),
          '2' => array( 
              'location_type_id' => 20,
              'email' => '1-3@example.com',
              'is_primary' => 0,
            ),
          '3' => array( 
              'location_type_id' => 21,
              'email' => '2-1@example.com',
              'is_primary' => 0,
            ),
          '4' => array( 
              'location_type_id' => 21,
              'email' => '2-2@example.com',
              'is_primary' => 0,
            ),
        ),
    ),
);

  $result = civicrm_api( 'email','replace',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function email_replace_expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 10,
  'values' => array( 
      '10' => array( 
          'contact_id' => '10',
          'contact_type' => 'Organization',
          'contact_sub_type' => '',
          'sort_name' => 'Unit Test Organization',
          'display_name' => 'Unit Test Organization',
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
          'first_name' => '',
          'middle_name' => '',
          'last_name' => '',
          'job_title' => '',
          'birth_date' => '',
          'is_deceased' => 0,
          'deceased_date' => '',
          'household_name' => '',
          'organization_name' => 'Unit Test Organization',
          'sic_code' => '',
          'contact_is_deleted' => 0,
          'gender_id' => '',
          'gender' => '',
          'prefix_id' => '',
          'prefix' => '',
          'suffix_id' => '',
          'suffix' => '',
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
          'state_province_name' => '',
          'state_province' => '',
          'country_id' => '',
          'country' => '',
          'phone_id' => '',
          'phone_type_id' => '',
          'phone' => '',
          'email_id' => '',
          'email' => '',
          'on_hold' => '',
          'im_id' => '',
          'provider_id' => '',
          'im' => '',
          'worldregion_id' => '',
          'world_region' => '',
          'id' => '10',
          'api.email.replace' => array( 
              'is_error' => 0,
              'version' => 3,
              'count' => 5,
              'values' => array( 
                  '0' => array( 
                      'id' => '18',
                      'contact_id' => '10',
                      'location_type_id' => '20',
                      'email' => '1-1@example.com',
                      'is_primary' => '1',
                      'is_billing' => '',
                      'on_hold' => '',
                      'is_bulkmail' => '',
                      'hold_date' => '',
                      'reset_date' => '',
                      'signature_text' => '',
                      'signature_html' => '',
                    ),
                  '1' => array( 
                      'id' => '19',
                      'contact_id' => '10',
                      'location_type_id' => '20',
                      'email' => '1-2@example.com',
                      'is_primary' => 0,
                      'is_billing' => '',
                      'on_hold' => '',
                      'is_bulkmail' => '',
                      'hold_date' => '',
                      'reset_date' => '',
                      'signature_text' => '',
                      'signature_html' => '',
                    ),
                  '2' => array( 
                      'id' => '20',
                      'contact_id' => '10',
                      'location_type_id' => '20',
                      'email' => '1-3@example.com',
                      'is_primary' => 0,
                      'is_billing' => '',
                      'on_hold' => '',
                      'is_bulkmail' => '',
                      'hold_date' => '',
                      'reset_date' => '',
                      'signature_text' => '',
                      'signature_html' => '',
                    ),
                  '3' => array( 
                      'id' => '21',
                      'contact_id' => '10',
                      'location_type_id' => '21',
                      'email' => '2-1@example.com',
                      'is_primary' => 0,
                      'is_billing' => '',
                      'on_hold' => '',
                      'is_bulkmail' => '',
                      'hold_date' => '',
                      'reset_date' => '',
                      'signature_text' => '',
                      'signature_html' => '',
                    ),
                  '4' => array( 
                      'id' => '22',
                      'contact_id' => '10',
                      'location_type_id' => '21',
                      'email' => '2-2@example.com',
                      'is_primary' => 0,
                      'is_billing' => '',
                      'on_hold' => '',
                      'is_bulkmail' => '',
                      'hold_date' => '',
                      'reset_date' => '',
                      'signature_text' => '',
                      'signature_html' => '',
                    ),
                ),
            ),
        ),
    ),
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testReplaceEmailsInChain and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/EmailTest.php
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