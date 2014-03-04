<?php
/**
 * Test Generated example of using relationship_type create API
 * *
 */
function relationship_type_create_example(){
$params = array(
  'name_a_b' => 'Relation 1 for relationship type create',
  'name_b_a' => 'Relation 2 for relationship type create',
  'contact_type_a' => 'Individual',
  'contact_type_b' => 'Organization',
  'is_reserved' => 1,
  'is_active' => 1,
  'sequential' => 1,
);

try{
  $result = civicrm_api3('relationship_type', 'create', $params);
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
function relationship_type_create_expectedresult(){

  $expectedResult = array(
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 1,
  'values' => array(
      '0' => array(
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
        ),
    ),
);

  return $expectedResult;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testRelationshipTypeCreate and can be found in
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/RelationshipTypeTest.php
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
