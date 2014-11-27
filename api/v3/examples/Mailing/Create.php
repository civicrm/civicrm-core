<?php
/**
 * Test Generated example of using mailing create API
 * *
 */
function mailing_create_example(){
$params = array(
  'subject' => 'maild',
  'body_text' => 'bdkfhdskfhduew',
  'name' => 'mailing name',
  'created_id' => 1,
);

try{
  $result = civicrm_api3('mailing', 'create', $params);
}
catch (CiviCRM_API3_Exception $e) {
  // handle error here
  $errorMessage = $e->getMessage();
  $errorCode = $e->getErrorCode();
  $errorData = $e->getExtraParams();
  return array('error' => $errorMessage, 'error_code' => $errorCode, 'error_data' => $errorData);
}

return $result;
}

/**
 * Function returns array of result expected from previous function
 */
function mailing_create_expectedresult(){

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
          'reply_id' => '',
          'unsubscribe_id' => '',
          'resubscribe_id' => '',
          'optout_id' => '',
          'name' => 'mailing name',
          'from_name' => 'FIXME',
          'from_email' => 'info@EXAMPLE.ORG',
          'replyto_email' => 'info@EXAMPLE.ORG',
          'subject' => 'maild',
          'body_text' => 'bdkfhdskfhduew',
          'body_html' => '',
          'url_tracking' => '1',
          'forward_replies' => '',
          'auto_responder' => 0,
          'open_tracking' => '1',
          'is_completed' => '',
          'msg_template_id' => '',
          'override_verp' => '1',
          'created_id' => '1',
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
          'dedupe_email' => '',
          'sms_provider_id' => '',
          'hash' => '',
          'api.mailing_job.create' => array(
              'is_error' => 0,
              'version' => 3,
              'count' => 1,
              'id' => 1,
              'values' => array(
                  '0' => array(
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
                    ),
                ),
            ),
        ),
    ),
);

  return $expectedResult;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testMailerCreateSuccess and can be found in
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
