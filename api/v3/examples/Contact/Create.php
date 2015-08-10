<?php
/**
 * Test Generated example demonstrating the Contact.create API.
 *
 * @return array
 *   API result array
 */
function contact_create_example() {
  $params = array(
    'id' => 3,
    'api.CustomField.create' => array(
      'id' => 1,
      'html_type' => 'Select Date',
      'data_type' => 'Date',
      'time_format' => '',
    ),
    'api.CustomValue.create' => array(
      'id' => '1',
      'entity_id' => 3,
      'custom_1' => '20150810',
    ),
    'api.CustomValue.get' => 1,
  );

  try{
    $result = civicrm_api3('Contact', 'create', $params);
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
function contact_create_expectedresult() {

  $expectedResult = array(
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 3,
    'values' => array(
      '3' => array(
        'id' => '3',
        'contact_type' => 'Individual',
        'contact_sub_type' => '',
        'do_not_email' => 0,
        'do_not_phone' => 0,
        'do_not_mail' => 0,
        'do_not_sms' => 0,
        'do_not_trade' => 0,
        'is_opt_out' => 0,
        'legal_identifier' => '',
        'external_identifier' => '',
        'sort_name' => 'xyz3, abc3',
        'display_name' => 'abc3 xyz3',
        'nick_name' => '',
        'legal_name' => '',
        'image_URL' => '',
        'preferred_communication_method' => '',
        'preferred_language' => 'en_US',
        'preferred_mail_format' => 'Both',
        'hash' => '67eac7789eaee00',
        'api_key' => '',
        'first_name' => 'abc3',
        'middle_name' => '',
        'last_name' => 'xyz3',
        'prefix_id' => '',
        'suffix_id' => '',
        'formal_title' => '',
        'communication_style_id' => '',
        'email_greeting_id' => '1',
        'email_greeting_custom' => '',
        'email_greeting_display' => 'Dear abc3',
        'postal_greeting_id' => '1',
        'postal_greeting_custom' => '',
        'postal_greeting_display' => 'Dear abc3',
        'addressee_id' => '1',
        'addressee_custom' => '',
        'addressee_display' => 'abc3 xyz3',
        'job_title' => '',
        'gender_id' => '',
        'birth_date' => '',
        'is_deceased' => 0,
        'deceased_date' => '',
        'household_name' => '',
        'primary_contact_id' => '',
        'organization_name' => '',
        'sic_code' => '',
        'user_unique_id' => '',
        'created_date' => '2013-07-28 08:49:19',
        'modified_date' => '2012-11-14 16:02:35',
        'api.CustomField.create' => array(
          'is_error' => 0,
          'version' => 3,
          'count' => 1,
          'id' => 1,
          'values' => array(
            '0' => array(
              'id' => '1',
              'custom_group_id' => '1',
              'name' => 'test_datetime',
              'label' => 'Demo Date',
              'data_type' => 'Date',
              'html_type' => 'Select Date',
              'default_value' => '',
              'is_required' => 0,
              'is_searchable' => 0,
              'is_search_range' => 0,
              'weight' => '4',
              'help_pre' => '',
              'help_post' => '',
              'mask' => '',
              'attributes' => '',
              'javascript' => '',
              'is_active' => '1',
              'is_view' => 0,
              'options_per_line' => '',
              'text_length' => '',
              'start_date_years' => '',
              'end_date_years' => '',
              'date_format' => 'mm/dd/yy',
              'time_format' => '',
              'note_columns' => '',
              'note_rows' => '',
              'column_name' => 'demo_date_1',
              'option_group_id' => '',
              'filter' => '',
              'in_selector' => 0,
            ),
          ),
        ),
        'api.CustomValue.create' => array(
          'is_error' => 0,
          'version' => 3,
          'count' => 1,
          'values' => TRUE,
        ),
        'api.CustomValue.get' => array(
          'is_error' => 0,
          'version' => 3,
          'count' => 2,
          'values' => array(
            '0' => array(
              'entity_id' => '3',
              'latest' => '2015-08-10 00:00:00',
              'id' => '1',
            ),
            '1' => array(
              'entity_table' => 'Contact',
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
* The test that created it is called "testCreateContactCustomFldDateTime"
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
