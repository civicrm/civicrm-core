<?php
/**
 * Test Generated example demonstrating the Survey.create API.
 *
 * @return array
 *   API result array
 */
function survey_create_example() {
  $params = [
    'title' => 'survey title',
    'activity_type_id' => '30',
    'max_number_of_contacts' => 12,
    'instructions' => 'Call people, ask for money',
  ];

  try{
    $result = civicrm_api3('Survey', 'create', $params);
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
function survey_create_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 1,
    'values' => [
      '1' => [
        'id' => '1',
        'title' => 'survey title',
        'campaign_id' => '',
        'activity_type_id' => '30',
        'recontact_interval' => '',
        'instructions' => 'Call people, ask for money',
        'release_frequency' => '',
        'max_number_of_contacts' => '12',
        'default_number_of_contacts' => '',
        'is_active' => '',
        'is_default' => '',
        'created_id' => '',
        'created_date' => '2013-07-28 08:49:19',
        'last_modified_id' => '',
        'last_modified_date' => '',
        'result_id' => '',
        'bypass_confirm' => '',
        'thankyou_title' => '',
        'thankyou_text' => '',
        'is_share' => '',
      ],
    ],
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testCreateSurvey"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/SurveyTest.php
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
