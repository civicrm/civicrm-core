<?php
/**
 * Test Generated example demonstrating the Relationship.get API.
 *
 * Demonstrates use of IN filter.
 *
 * @return array
 *   API result array
 */
function relationship_get_example() {
  $params = array(
    'relationship_type_id' => array(
      'IN' => array(
        '0' => 36,
        '1' => 37,
      ),
    ),
  );

  try{
    $result = civicrm_api3('Relationship', 'get', $params);
  }
  catch (CiviCRM_API3_Exception $e) {
    // Handle error here.
    $errorMessage = $e->getMessage();
    $errorCode = $e->getErrorCode();
    $errorData = $e->getExtraParams();
    return array(
      'is_error' => 1,
      'error_message' => $errorMessage,
      'error_code' => $errorCode,
      'error_data' => $errorData,
    );
  }

  return $result;
}

/**
 * Function returns array of result expected from previous function.
 *
 * @return array
 *   API result array
 */
function relationship_get_expectedresult() {

  $expectedResult = array(
    'is_error' => 0,
    'version' => 3,
    'count' => 2,
    'values' => array(
      '2' => array(
        'id' => '2',
        'contact_id_a' => '99',
        'contact_id_b' => '101',
        'relationship_type_id' => '36',
        'start_date' => '2013-07-29 00:00:00',
        'is_active' => '1',
        'is_permission_a_b' => 0,
        'is_permission_b_a' => 0,
      ),
      '3' => array(
        'id' => '3',
        'contact_id_a' => '99',
        'contact_id_b' => '101',
        'relationship_type_id' => '37',
        'start_date' => '2013-07-29 00:00:00',
        'is_active' => '1',
        'is_permission_a_b' => 0,
        'is_permission_b_a' => 0,
      ),
    ),
  );

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testGetTypeOperators"
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
