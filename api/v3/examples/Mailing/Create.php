<?php
/**
 * Test Generated example demonstrating the Mailing.create API.
 *
 * @return array
 *   API result array
 */
function mailing_create_example() {
  $params = [
    'subject' => 'Hello {contact.display_name}',
    'body_text' => 'This is {contact.display_name}.
https://civicrm.org
{domain.address}{action.optOutUrl}',
    'body_html' => '<p>This is {contact.display_name}.</p><p><a href=\'https://civicrm.org/\'>CiviCRM.org</a></p><p>{domain.address}{action.optOutUrl}</p>',
    'name' => 'mailing name',
    'created_id' => 3,
    'header_id' => '',
    'footer_id' => '',
    'groups' => [
      'include' => [
        '0' => 2,
      ],
      'exclude' => [
        '0' => 3,
      ],
    ],
    'mailings' => [
      'include' => [],
      'exclude' => [],
    ],
    'options' => [
      'force_rollback' => 1,
    ],
    'api.mailing_job.create' => 1,
    'api.MailingRecipients.get' => [
      'mailing_id' => '$value.id',
      'api.contact.getvalue' => [
        'return' => 'display_name',
      ],
      'api.email.getvalue' => [
        'return' => 'email',
      ],
    ],
  ];

  try{
    $result = civicrm_api3('Mailing', 'create', $params);
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
function mailing_create_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 1,
    'values' => [
      '1' => [
        'id' => '1',
        'domain_id' => '1',
        'header_id' => '',
        'footer_id' => '',
        'reply_id' => '8',
        'unsubscribe_id' => '5',
        'resubscribe_id' => '6',
        'optout_id' => '7',
        'name' => 'mailing name',
        'mailing_type' => 'standalone',
        'from_name' => 'FIXME',
        'from_email' => 'info@EXAMPLE.ORG',
        'replyto_email' => 'info@EXAMPLE.ORG',
        'template_type' => '',
        'template_options' => '',
        'subject' => 'Hello {contact.display_name}',
        'body_text' => 'This is {contact.display_name}.
https://civicrm.org
{domain.address}{action.optOutUrl}',
        'body_html' => '<p>This is {contact.display_name}.</p><p><a href=\'https://civicrm.org/\'>CiviCRM.org</a></p><p>{domain.address}{action.optOutUrl}</p>',
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
        'scheduled_date' => '',
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
        'language' => '',
        'api.mailing_job.create' => [
          'is_error' => 0,
          'version' => 3,
          'count' => 1,
          'id' => 1,
          'values' => [
            '0' => [
              'id' => '1',
              'mailing_id' => '1',
              'scheduled_date' => '20130728085413',
              'start_date' => '',
              'end_date' => '',
              'status' => 'Scheduled',
              'is_test' => 0,
              'job_type' => '',
              'parent_id' => '',
              'job_offset' => '',
              'job_limit' => '',
            ],
          ],
        ],
        'api.MailingRecipients.get' => [
          'is_error' => 0,
          'version' => 3,
          'count' => 1,
          'id' => 1,
          'values' => [
            '0' => [
              'id' => '1',
              'mailing_id' => '1',
              'contact_id' => '4',
              'email_id' => '4',
              'api.contact.getvalue' => 'Mr. Includer Person II',
              'api.email.getvalue' => 'include.me@example.org',
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
* The test that created it is called "testMailerPreviewRecipients"
* and can be found at:
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
