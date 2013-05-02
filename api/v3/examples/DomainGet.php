<?php

/*
 
 */
function domain_get_example(){
$params = array( 
  'version' => 3,
  'sequential' => 1,
);

  $result = civicrm_api( 'domain','get',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function domain_get_expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'version' => 3,
  'count' => 2,
  'values' => array( 
      '0' => array( 
          'id' => '1',
          'name' => 'Default Domain Name',
          'version' => '3',
          'contact_id' => '3',
          'domain_email' => 'my@email.com',
          'domain_phone' => array( 
              'phone_type' => 'Phone',
              'phone' => '456-456',
            ),
          'domain_address' => array( 
              'street_address' => '45 Penny Lane',
              'supplemental_address_1' => '',
              'supplemental_address_2' => '',
              'city' => '',
              'state_province_id' => '',
              'postal_code' => '',
              'country_id' => '',
              'geo_code_1' => '',
              'geo_code_2' => '',
            ),
          'from_email' => 'info@EXAMPLE.ORG',
          'from_name' => 'FIXME',
        ),
      '1' => array( 
          'id' => '2',
          'name' => 'Second Domain',
          'version' => '4.3.alpha1',
          'contact_id' => '2',
          'domain_email' => '"Domain Email" <domainemail2@example.org>',
          'domain_phone' => array( 
              'phone_type' => 'Phone',
              'phone' => '204 555-1001',
            ),
          'domain_address' => array( 
              'street_address' => '15 Main St',
              'supplemental_address_1' => '',
              'supplemental_address_2' => '',
              'city' => 'Collinsville',
              'state_province_id' => '1006',
              'postal_code' => '6022',
              'country_id' => '1228',
              'geo_code_1' => '41.8328',
              'geo_code_2' => '-72.9253',
            ),
          'from_email' => 'info@EXAMPLE.ORG',
          'from_name' => 'FIXME',
        ),
    ),
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testGet and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/DomainTest.php
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