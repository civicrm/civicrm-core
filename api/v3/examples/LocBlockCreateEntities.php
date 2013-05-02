<?php

/*
 Create entities and location block in 1 api call
 */
function loc_block_createentities_example(){
$params = array( 
  'version' => 3,
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

  $result = civicrm_api( 'loc_block','createEntities',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function loc_block_createentities_expectedresult(){

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
              'contact_id' => 'null',
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
              'contact_id' => 'null',
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
              'contact_id' => 'null',
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

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testCreateLocBlockEntities and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/LocBlockTest.php
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