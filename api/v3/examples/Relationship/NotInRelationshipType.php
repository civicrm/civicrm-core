<?php
/**
 * Test Generated example of using relationship get API
 * demonstrates use of NOT IN filter *
 */
function relationship_get_example(){
$params = array(
  'relationship_type_id' => array(
      'NOT IN' => array(
          '0' => 33,
          '1' => 34,
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
      '1' => array(
          'id' => '1',
          'contact_id_a' => '87',
          'contact_id_b' => '89',
          'relationship_type_id' => '32',
          'start_date' => '2013-07-29 00:00:00',
          'is_active' => '1',
          'description' => '',
          'is_permission_a_b' => 0,
          'is_permission_b_a' => 0,
        ),
      '4' => array(
          'id' => '4',
          'contact_id_a' => '87',
          'contact_id_b' => '89',
          'relationship_type_id' => '35',
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
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/RelationshipTest.php
*
* You can see the outcome of the API tests at
* https://test.civicrm.org/job/CiviCRM-master-git/
*
* To Learn about the API read
* http://wiki.civicrm.org/confluence/display/CRMDOC/Using+the+API
*
* Browse the api on your own site with the api explorer
* http://MYSITE.ORG/path/to/civicrm/api/explorer
*
* Read more about testing here
* http://wiki.civicrm.org/confluence/display/CRM/Testing
*
* API Standards documentation:
* http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
*/
