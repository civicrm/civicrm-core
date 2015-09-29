<?php
/**
 * Test Generated example demonstrating the Contact.get API.
 *
 * This demonstrates the usage of chained api functions with multiple custom fields.
 *
 * @return array
 *   API result array
 */
function contact_get_example() {
  $params = array(
    'id' => 1,
    'api.website.getValue' => array(
      'return' => 'url',
    ),
    'api.Contribution.getCount' => array(),
    'api.CustomValue.get' => 1,
  );

  try{
    $result = civicrm_api3('Contact', 'get', $params);
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
function contact_get_expectedresult() {

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
        'preferred_communication_method' => '',
        'preferred_language' => 'en_US',
        'preferred_mail_format' => 'Both',
        'first_name' => 'abc3',
        'middle_name' => '',
        'last_name' => 'xyz3',
        'prefix_id' => '',
        'suffix_id' => '',
        'formal_title' => '',
        'communication_style_id' => '',
        'job_title' => '',
        'gender_id' => '',
        'birth_date' => '',
        'is_deceased' => 0,
        'deceased_date' => '',
        'household_name' => '',
        'organization_name' => '',
        'sic_code' => '',
        'contact_is_deleted' => 0,
        'current_employer' => '',
        'address_id' => '2',
        'street_address' => '1 my road',
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
        'email' => 'man3@yahoo.com',
        'on_hold' => 0,
        'im_id' => '',
        'provider_id' => '',
        'im' => '',
        'worldregion_id' => '',
        'world_region' => '',
        'individual_prefix' => '',
        'individual_suffix' => '',
        'communication_style' => '',
        'gender' => '',
        'state_province_name' => '',
        'state_province' => '',
        'country' => '',
        'id' => '1',
        'api.website.getValue' => 'http://civicrm.org',
        'api.Contribution.getCount' => 2,
        'api.CustomValue.get' => array(
          'is_error' => 0,
          'version' => 3,
          'count' => 8,
          'values' => array(
            '0' => array(
              'entity_id' => '1',
              'latest' => 'value 4',
              'id' => '1',
              'entity_table' => 'Contact',
            ),
            '1' => array(
              'entity_table' => 'Contact',
            ),
            '2' => array(
              'entity_id' => '1',
              'latest' => 'value 3',
              'id' => '2',
              '1' => 'value 2',
              'entity_table' => 'Contact',
              '2' => 'value 3',
            ),
            '3' => array(
              'entity_id' => '1',
              'latest' => '',
              'id' => '3',
              '1' => 'warm beer',
              '2' => '',
            ),
            '4' => array(
              'entity_id' => '1',
              'latest' => '',
              'id' => '4',
              '1' => '',
              '2' => '',
            ),
            '5' => array(
              'entity_id' => '1',
              'latest' => 'defaultValue',
              'id' => '5',
              '1' => 'defaultValue',
            ),
            '6' => array(
              'entity_id' => '1',
              'latest' => 'vegemite',
              'id' => '6',
              '1' => 'vegemite',
            ),
            '7' => array(
              'entity_id' => '1',
              'latest' => '',
              'id' => '7',
              '1' => '',
            ),
          ),
        ),
      ),
    ),
  );

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testGetIndividualWithChainedArraysAndMultipleCustom"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/ContactTest.php
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
