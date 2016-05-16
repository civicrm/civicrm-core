<?php
/**
 * Test Generated example of using message_template create API
 * *
 */
function message_template_create_example(){
$params = array(
  'msg_title' => 'msg_title_55',
  'msg_subject' => 'msg_subject_55',
  'msg_text' => 'msg_text_55',
  'msg_html' => 'msg_html_55',
  'workflow_id' => 55,
  'is_default' => '1',
  'is_reserved' => 1,
  'pdf_format_id' => '1',
);

try{
  $result = civicrm_api3('message_template', 'create', $params);
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
function message_template_create_expectedresult(){

  $expectedResult = array(
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 2,
  'values' => array(
      '2' => array(
          'id' => '2',
          'msg_title' => 'msg_title_55',
          'msg_subject' => 'msg_subject_55',
          'msg_text' => 'msg_text_55',
          'msg_html' => 'msg_html_55',
          'is_active' => '1',
          'workflow_id' => '55',
          'is_default' => '1',
          'is_reserved' => '1',
          'pdf_format_id' => '1',
        ),
    ),
);

  return $expectedResult;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testCreate and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/MessageTemplateTest.php
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
