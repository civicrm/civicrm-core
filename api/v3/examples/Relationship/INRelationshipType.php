<?php
/**
 * Test Generated example of using relationship get API
 * demonstrates use of IN filter *
 */
function relationship_get_example(){
$params = array(
  'relationship_type_id' => array(
      'IN' => array(
          '0' => 32,
          '1' => 33,
        ),
    ),
);

try{
  $result = civicrm_api3('relationship', 'get', $params);
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
function relationship_get_expectedresult(){

  $expectedResult = array(
  'is_error' => 0,
  'version' => 3,
  'count' => 2,
  'values' => array(
      '2' => array(
          'id' => '2',
          'contact_id_a' => '63',
          'contact_id_b' => '64',
          'relationship_type_id' => '32',
          'start_date' => '2013-07-29 00:00:00',
          'is_active' => '1',
          'description' => '',
          'is_permission_a_b' => 0,
          'is_permission_b_a' => 0,
        ),
      '3' => array(
          'id' => '3',
          'contact_id_a' => '63',
          'contact_id_b' => '64',
          'relationship_type_id' => '33',
          'start_date' => '2013-07-29 00:00:00',
          'is_active' => '1',
          'description' => '',
          'is_permission_a_b' => 0,
          'is_permission_b_a' => 0,
        ),
    ),
);

  return $expectedResult;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testGetTypeOperators and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/RelationshipTest.php
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