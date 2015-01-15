<?php
/**
 * @file
 * Test Generated API Example.
 * See bottom of this file for more detail.
 */

/**
 * Test Generated example of using mailing submit API.
 *
 *
 * @return array
 *   API result array
 */
function mailing_submit_example() {
  $params = array(
    'scheduled_date' => '2014-12-13 10:00:00',
    'id' => 9,
  );

  try{
    $result = civicrm_api3('mailing', 'submit', $params);
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
function mailing_submit_expectedresult() {

  $expectedResult = array(
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 9,
    'values' => array(
      '9' => array(
        'id' => '9',
        'domain_id' => '1',
        'header_id' => '',
        'footer_id' => '',
        'reply_id' => '',
        'unsubscribe_id' => '',
        'resubscribe_id' => '',
        'optout_id' => '',
        'name' => 'mailing name',
        'from_name' => 'FIXME',
        'from_email' => 'info@EXAMPLE.ORG',
        'replyto_email' => 'info@EXAMPLE.ORG',
        'subject' => 'Hello {contact.display_name}',
        'body_text' => 'This is {contact.display_name}',
        'body_html' => '<p>This is {contact.display_name}</p>',
        'url_tracking' => '1',
        'forward_replies' => 0,
        'auto_responder' => 0,
        'open_tracking' => '1',
        'is_completed' => '',
        'msg_template_id' => '',
        'override_verp' => '1',
        'created_id' => '22',
        'created_date' => '2013-07-28 08:49:19',
        'scheduled_id' => '22',
        'scheduled_date' => '20130728085413',
        'approver_id' => '',
        'approval_date' => '',
        'approval_status_id' => '',
        'approval_note' => '',
        'is_archived' => 0,
        'visibility' => 'Public Pages',
        'campaign_id' => '',
        'dedupe_email' => 0,
        'sms_provider_id' => '',
        'hash' => '67eac7789eaee00',
        'location_type_id' => '',
        'email_selection_method' => '',
      ),
    ),
  );

  return $expectedResult;
}

/**
* This example has been generated from the API test suite.
* The test that created it is called
* testMailerSubmit
* and can be found in
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/MailingTest.php
*
* You can see the outcome of the API tests at
* https://test.civicrm.org/job/CiviCRM-master-git/
*
* To Learn about the API read
* http://wiki.civicrm.org/confluence/display/CRMDOC/Using+the+API
*
* Browse the api on your own site with the api explorer
* http://MYSITE.ORG/path/to/civicrm/api/explorer
*
* Read more about testing here
* http://wiki.civicrm.org/confluence/display/CRM/Testing
*
* API Standards documentation:
* http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
*/
