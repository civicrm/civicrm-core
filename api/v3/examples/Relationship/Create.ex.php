<?php
/**
 * Test Generated example demonstrating the Relationship.create API.
 *
 * @return array
 *   API result array
 */
function relationship_create_example() {
  $params = [
    'contact_id_a' => 31,
    'contact_id_b' => 33,
    'relationship_type_id' => 18,
    'start_date' => '2010-10-30',
    'end_date' => '2010-12-30',
    'is_active' => 1,
    'note' => 'note',
  ];

  try{
    $result = civicrm_api3('Relationship', 'create', $params);
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
function relationship_create_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 1,
    'values' => [
      '1' => [
        'id' => '1',
        'contact_id_a' => '31',
        'contact_id_b' => '33',
        'relationship_type_id' => '18',
        'start_date' => '2013-07-29 00:00:00',
        'end_date' => '2013-08-04 00:00:00',
        'is_active' => '1',
        'description' => '',
        'is_permission_a_b' => 0,
        'is_permission_b_a' => 0,
        'case_id' => '',
      ],
    ],
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testRelationshipCreate"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/RelationshipTest.php
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
