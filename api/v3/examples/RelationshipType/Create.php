<?php
/**
 * Test Generated example demonstrating the RelationshipType.create API.
 *
 * @return array
 *   API result array
 */
function relationship_type_create_example() {
  $params = [
    'name_a_b' => 'Relation 1 for relationship type create',
    'name_b_a' => 'Relation 2 for relationship type create',
    'contact_type_a' => 'Individual',
    'contact_type_b' => 'Organization',
    'is_reserved' => 1,
    'is_active' => 1,
    'sequential' => 1,
  ];

  try{
    $result = civicrm_api3('RelationshipType', 'create', $params);
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
function relationship_type_create_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 1,
    'values' => [
      '0' => [
        'id' => '1',
        'name_a_b' => 'Relation 1 for relationship type create',
        'label_a_b' => 'Relation 1 for relationship type create',
        'name_b_a' => 'Relation 2 for relationship type create',
        'label_b_a' => 'Relation 2 for relationship type create',
        'description' => '',
        'contact_type_a' => 'Individual',
        'contact_type_b' => 'Organization',
        'contact_sub_type_a' => '',
        'contact_sub_type_b' => '',
        'is_reserved' => '1',
        'is_active' => '1',
      ],
    ],
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testRelationshipTypeCreate"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/RelationshipTypeTest.php
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
