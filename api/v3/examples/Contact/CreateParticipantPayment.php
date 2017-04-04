<?php
/**
 * Test Generated example demonstrating the Contact.create API.
 *
 * Single function to create contact with partipation & contribution.
 * Note that in the case of 'contribution' the 'create' is implied (api.contribution.create)
 *
 * @return array
 *   API result array
 */
function contact_create_example() {
  $params = array(
    'contact_type' => 'Individual',
    'display_name' => 'dlobo',
    'api.participant' => array(
      'event_id' => 42,
      'status_id' => 1,
      'role_id' => 1,
      'format.only_id' => 1,
    ),
    'api.contribution.create' => array(
      'financial_type_id' => 1,
      'total_amount' => 100,
      'format.only_id' => 1,
    ),
    'api.participant_payment.create' => array(
      'contribution_id' => '$value.api.contribution.create',
      'participant_id' => '$value.api.participant',
    ),
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
function contact_create_expectedresult() {

  $expectedResult = array(
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 5,
    'values' => array(
      '5' => array(
        'id' => '5',
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
        'sort_name' => 'dlobo',
        'display_name' => 'dlobo',
        'nick_name' => '',
        'legal_name' => '',
        'image_URL' => '',
        'preferred_communication_method' => '',
        'preferred_language' => 'en_US',
        'preferred_mail_format' => 'Both',
        'hash' => '67eac7789eaee00',
        'api_key' => '',
        'first_name' => '',
        'middle_name' => '',
        'last_name' => '',
        'prefix_id' => '',
        'suffix_id' => '',
        'formal_title' => '',
        'communication_style_id' => '',
        'email_greeting_id' => '1',
        'email_greeting_custom' => '',
        'email_greeting_display' => '',
        'postal_greeting_id' => '1',
        'postal_greeting_custom' => '',
        'postal_greeting_display' => '',
        'addressee_id' => '1',
        'addressee_custom' => '',
        'addressee_display' => '',
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
        'api.participant' => 4,
        'api.contribution.create' => 1,
        'api.participant_payment.create' => array(
          'is_error' => 0,
          'version' => 3,
          'count' => 1,
          'id' => 1,
          'values' => array(
            '0' => array(
              'id' => '1',
              'participant_id' => '4',
              'contribution_id' => '1',
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
* The test that created it is called "testCreateParticipantWithPayment"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/ParticipantTest.php
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
