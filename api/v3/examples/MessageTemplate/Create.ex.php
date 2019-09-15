<?php
/**
 * Test Generated example demonstrating the MessageTemplate.create API.
 *
 * @return array
 *   API result array
 */
function message_template_create_example() {
  $params = [
    'msg_title' => 'msg_title_471',
    'msg_subject' => 'msg_subject_471',
    'msg_text' => 'msg_text_471',
    'msg_html' => 'msg_html_471',
    'workflow_id' => 471,
    'is_default' => '1',
    'is_reserved' => 1,
  ];

  try{
    $result = civicrm_api3('MessageTemplate', 'create', $params);
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
function message_template_create_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 69,
    'values' => [
      '69' => [
        'id' => '69',
        'msg_title' => 'msg_title_471',
        'msg_subject' => 'msg_subject_471',
        'msg_text' => 'msg_text_471',
        'msg_html' => 'msg_html_471',
        'is_active' => '1',
        'workflow_id' => '471',
        'is_default' => '1',
        'is_reserved' => '1',
        'is_sms' => '',
        'pdf_format_id' => '',
      ],
    ],
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testCreate"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/MessageTemplateTest.php
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
