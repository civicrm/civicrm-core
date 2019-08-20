<?php
/**
 * Test Generated example demonstrating the Note.create API.
 *
 * @return array
 *   API result array
 */
function note_create_example() {
  $params = [
    'entity_table' => 'civicrm_contact',
    'entity_id' => 15,
    'note' => 'Hello!!! m testing Note',
    'contact_id' => 15,
    'modified_date' => '2011-01-31',
    'subject' => 'Test Note',
  ];

  try{
    $result = civicrm_api3('Note', 'create', $params);
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
function note_create_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 14,
    'values' => [
      '14' => [
        'id' => '14',
        'entity_table' => 'civicrm_contact',
        'entity_id' => '15',
        'note' => 'Hello!!! m testing Note',
        'contact_id' => '15',
        'modified_date' => '2012-11-14 16:02:35',
        'subject' => 'Test Note',
        'privacy' => 0,
      ],
    ],
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testCreate"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/NoteTest.php
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
