<?php
/**
 * Test Generated example demonstrating the MessageTemplate.get API.
 *
 * @return array
 *   API result array
 */
function message_template_get_example() {
  $params = [
    'msg_title' => 'msg_title_472',
    'msg_subject' => 'msg_subject_472',
    'msg_text' => 'msg_text_472',
    'msg_html' => 'msg_html_472',
    'workflow_id' => 472,
    'is_default' => '1',
    'is_reserved' => 1,
  ];

  try{
    $result = civicrm_api3('MessageTemplate', 'get', $params);
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
function message_template_get_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 70,
    'values' => [
      '70' => [
        'id' => '70',
        'msg_title' => 'msg_title_472',
        'msg_subject' => 'msg_subject_472',
        'msg_text' => 'msg_text_472',
        'msg_html' => 'msg_html_472',
        'is_active' => '1',
        'workflow_id' => '472',
        'is_default' => '1',
        'is_reserved' => '1',
        'is_sms' => 0,
        'pdf_format_id' => '472',
      ],
    ],
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testGet"
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
