<?php

/**
 * @file
 */

/**
 * Test Generated example demonstrating the Activity.get API.
 *
 * Demonstrates getting assignee_contact_id & using it to get the contact.
 *
 * @return array
 *   API result array
 */
function activity_get_example() {
  $params = [
    'activity_id' => 1,
    'sequential' => 1,
    'return.assignee_contact_id' => 1,
    'api.contact.get' => [
      'id' => '$value.source_contact_id',
    ],
    'return' => [
      '0' => 'activity_type_id',
      '1' => 'subject',
    ],
  ];

  try {
    $result = civicrm_api3('Activity', 'get', $params);
  }
  catch (CRM_Core_Exception $e) {
    // Handle error here.
    $errorMessage = $e->getMessage();
    $errorCode = $e->getErrorCode();
    $errorData = $e->getExtraParams();
    return [
      'is_error' => 1,
      'error_message' => $errorMessage,
      'error_code' => $errorCode,
      'error_data' => $errorData,
    ];
  }

  return $result;
}

/**
 * Function returns array of result expected from previous function.
 *
 * @return array
 *   API result array
 */
function activity_get_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 1,
    'values' => [
      '0' => [
        'id' => '1',
        'activity_type_id' => '9999',
        'subject' => 'test activity type id',
        'source_contact_id' => '1',
        'source_contact_name' => 'Mr. Anthony Anderson II',
        'source_contact_sort_name' => 'Anderson, Anthony',
        'assignee_contact_id' => [
          '0' => '3',
        ],
        'assignee_contact_name' => [
          '3' => 'The Rock roccky',
        ],
        'assignee_contact_sort_name' => [
          '3' => 'roccky, The Rock',
        ],
        'api.contact.get' => [
          'is_error' => 0,
          'version' => 3,
          'count' => 1,
          'id' => 1,
          'values' => [
            '0' => [
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
              'preferred_communication_method' => '',
              'preferred_language' => 'en_US',
              'preferred_mail_format' => 'Both',
              'first_name' => 'Anthony',
              'middle_name' => 'J.',
              'last_name' => 'Anderson',
              'prefix_id' => '3',
              'suffix_id' => '3',
              'formal_title' => '',
              'communication_style_id' => '1',
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
              'address_id' => '',
              'street_address' => '',
              'supplemental_address_1' => '',
              'supplemental_address_2' => '',
              'supplemental_address_3' => '',
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
              'languages' => 'English (United States)',
              'individual_prefix' => 'Mr.',
              'individual_suffix' => 'II',
              'communication_style' => 'Formal',
              'gender' => '',
              'state_province_name' => '',
              'state_province' => '',
              'country' => '',
              'id' => '1',
            ],
          ],
        ],
      ],
    ],
  ];

  return $expectedResult;
}

/*
 * This example has been generated from the API test suite.
 * The test that created it is called "testActivityGetGoodID1"
 * and can be found at:
 * https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/ActivityTest.php
 *
 * You can see the outcome of the API tests at
 * https://test.civicrm.org/job/CiviCRM-Core-Matrix/
 *
 * To Learn about the API read
 * https://docs.civicrm.org/dev/en/latest/api/
 *
 * Browse the API on your own site with the API Explorer. It is in the main
 * CiviCRM menu, under: Support > Development > API Explorer.
 *
 * Read more about testing here
 * https://docs.civicrm.org/dev/en/latest/testing/
 *
 * API Standards documentation:
 * https://docs.civicrm.org/dev/en/latest/framework/api-architecture/
 */
