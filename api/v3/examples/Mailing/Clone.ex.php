<?php
/**
 * Test Generated example demonstrating the Mailing.clone API.
 *
 * @return array
 *   API result array
 */
function mailing_clone_example() {
  $params = [
    'id' => 27,
  ];

  try{
    $result = civicrm_api3('Mailing', 'clone', $params);
  }
  catch (CiviCRM_API3_Exception $e) {
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
function mailing_clone_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 28,
    'values' => [
      '28' => [
        'id' => '28',
        'domain_id' => '1',
        'header_id' => '1',
        'footer_id' => '2',
        'reply_id' => '8',
        'unsubscribe_id' => '5',
        'resubscribe_id' => '6',
        'optout_id' => '7',
        'name' => 'mailing name',
        'mailing_type' => 'standalone',
        'from_name' => 'FIXME',
        'from_email' => 'info@EXAMPLE.ORG',
        'replyto_email' => 'info@EXAMPLE.ORG',
        'template_type' => 'traditional',
        'template_options' => '',
        'subject' => 'Hello {contact.display_name}',
        'body_text' => 'This is {contact.display_name}.
https://civicrm.org
{domain.address}{action.optOutUrl}',
        'body_html' => '<link href=\'https://fonts.googleapis.com/css?family=Roboto+Condensed:400,700|Zilla+Slab:500,700\' rel=\'stylesheet\' type=\'text/css\'><p><a href=\"http://{action.forward}\">Forward this email</a><a href=\"{action.forward}\">Forward this email with no protocol</a></p<p>This is {contact.display_name}.</p><p><a href=\'https://civicrm.org/\'>CiviCRM.org</a></p><p>{domain.address}{action.optOutUrl}</p>',
        'url_tracking' => '1',
        'forward_replies' => 0,
        'auto_responder' => 0,
        'open_tracking' => '1',
        'is_completed' => '',
        'msg_template_id' => '',
        'override_verp' => '1',
        'created_id' => '154',
        'created_date' => '2013-07-28 08:49:19',
        'modified_date' => '2012-11-14 16:02:35',
        'scheduled_id' => '',
        'scheduled_date' => '',
        'approver_id' => '',
        'approval_date' => '',
        'approval_status_id' => '',
        'approval_note' => '',
        'is_archived' => 0,
        'visibility' => 'Public Pages',
        'campaign_id' => '',
        'dedupe_email' => '1',
        'sms_provider_id' => '',
        'hash' => '',
        'location_type_id' => '',
        'email_selection_method' => 'automatic',
        'language' => '',
      ],
    ],
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testClone"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/MailingTest.php
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
