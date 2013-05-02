<?php

/*
 /*this demonstrates the usage of chained api functions. A variety of techniques are used
 */
function contact_get_example(){
$params = array( 
  'id' => 1,
  'version' => 3,
  'api.website.getValue' => array( 
      'return' => 'url',
    ),
  'api.Contribution.getCount' => array(),
  'api.CustomValue.get' => 1,
);

  $result = civicrm_api( 'contact','get',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function contact_get_expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 1,
  'values' => array( 
      '1' => array( 
          'contact_id' => '1',
          'contact_type' => 'Individual',
          'contact_sub_type' => '',
          'sort_name' => 'xyz3, abc3',
          'display_name' => 'abc3 xyz3',
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
          'first_name' => 'abc3',
          'middle_name' => '',
          'last_name' => 'xyz3',
          'job_title' => '',
          'birth_date' => '',
          'is_deceased' => 0,
          'deceased_date' => '',
          'household_name' => '',
          'organization_name' => '',
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
          'email_id' => '1',
          'email' => 'man3@yahoo.com',
          'on_hold' => 0,
          'im_id' => '',
          'provider_id' => '',
          'im' => '',
          'worldregion_id' => '',
          'world_region' => '',
          'id' => '1',
          'api.website.getValue' => 'http://civicrm.org',
          'api.Contribution.getCount' => 2,
          'api.CustomValue.get' => array( 
              'is_error' => 0,
              'version' => 3,
              'count' => 10,
              'values' => array( 
                  '0' => array( 
                      'entity_id' => '1',
                      'latest' => 'value 4',
                      'id' => '12',
                      '0' => 'value 4',
                    ),
                  '1' => array( 
                      'entity_table' => 'Contact',
                    ),
                  '2' => array( 
                      'entity_id' => '1',
                      'latest' => 'value 3',
                      'id' => '13',
                      '1' => 'value 2',
                      '2' => 'value 3',
                    ),
                  '3' => array( 
                      'entity_table' => 'Contact',
                    ),
                  '4' => array( 
                      'entity_id' => '1',
                      'latest' => '',
                      'id' => '14',
                      '1' => 'warm beer',
                      '2' => '',
                    ),
                  '5' => array( 
                      'entity_id' => '1',
                      'latest' => '',
                      'id' => '15',
                      '1' => '',
                      '2' => '',
                    ),
                  '6' => array( 
                      'entity_table' => 'Contact',
                    ),
                  '7' => array( 
                      'entity_id' => '1',
                      'latest' => '',
                      'id' => '16',
                      '1' => '',
                    ),
                  '8' => array( 
                      'entity_id' => '1',
                      'latest' => 'vegemite',
                      'id' => '17',
                      '1' => 'vegemite',
                    ),
                  '9' => array( 
                      'entity_id' => '1',
                      'latest' => '',
                      'id' => '18',
                      '1' => '',
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
* testGetIndividualWithChainedArraysAndMultipleCustom and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/ContactTest.php
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