<?php
/**
 * Test Generated example demonstrating the Relationship.get API.
 *
 * @return array
 *   API result array
 */
function relationship_get_example() {
  $params = array(
    'id' => 1,
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
      'error' => $errorMessage,
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
    'count' => 1,
    'id' => 1,
    'values' => array(
      '1' => array(
        'id' => '1',
        'contact_id_a' => '47',
        'contact_id_b' => '49',
        'relationship_type_id' => '22',
        'start_date' => '2013-07-29 00:00:00',
        'is_active' => '1',
        'description' => '',
        'is_permission_a_b' => 0,
        'is_permission_b_a' => 0,
        'custom_1' => 'custom string',
        'custom_1_1' => 'custom string',
      ),
    ),
  );

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testGetWithCustom"
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
