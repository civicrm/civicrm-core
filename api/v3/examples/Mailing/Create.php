<?php
/**
 * Test Generated example of using mailing create API.
 *
 * @return array
 *   API result array
 */
function mailing_create_example() {
  $params = array(
    'subject' => 'Hello {contact.display_name}',
    'body_text' => 'This is {contact.display_name}.
{domain.address}{action.optOutUrl}',
    'body_html' => '<p>This is {contact.display_name}.</p><p>{domain.address}{action.optOutUrl}</p>',
    'name' => 'mailing name',
    'created_id' => 3,
    'header_id' => '',
    'footer_id' => '',
    'scheduled_date' => 'now',
  );

  try{
    $result = civicrm_api3('mailing', 'create', $params);
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
function mailing_create_expectedresult() {

  $expectedResult = array(
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 1,
    'values' => array(
      '1' => array(
        'id' => '1',
        'domain_id' => '1',
        'header_id' => '',
        'footer_id' => '',
        'reply_id' => '8',
        'unsubscribe_id' => '5',
        'resubscribe_id' => '6',
        'optout_id' => '7',
        'name' => 'mailing name',
        'from_name' => 'FIXME',
        'from_email' => 'info@EXAMPLE.ORG',
        'replyto_email' => 'info@EXAMPLE.ORG',
        'subject' => 'Hello {contact.display_name}',
        'body_text' => 'This is {contact.display_name}.
{domain.address}{action.optOutUrl}',
        'body_html' => '<p>This is {contact.display_name}.</p><p>{domain.address}{action.optOutUrl}</p>',
        'url_tracking' => '1',
        'forward_replies' => '',
        'auto_responder' => '',
        'open_tracking' => '1',
        'is_completed' => '',
        'msg_template_id' => '',
        'override_verp' => '1',
        'created_id' => '3',
        'created_date' => '2013-07-28 08:49:19',
        'scheduled_id' => '',
        'scheduled_date' => '20130728085413',
        'approver_id' => '',
        'approval_date' => '',
        'approval_status_id' => '',
        'approval_note' => '',
        'is_archived' => '',
        'visibility' => 'Public Pages',
        'campaign_id' => '',
        'dedupe_email' => '1',
        'sms_provider_id' => '',
        'hash' => '',
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
* testMailerCreateSuccess
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
* http://MYSITE.ORG/path/to/civicrm/api
*
* Read more about testing here
* http://wiki.civicrm.org/confluence/display/CRM/Testing
*
* API Standards documentation:
* http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
*/
