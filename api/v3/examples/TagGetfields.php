<?php
/**
 * Test Generated example of using tag getfields API
 * demonstrate use of getfields to interogate api *
 */
function tag_getfields_example(){
$params = array(
  'action' => 'create',
);

try{
  $result = civicrm_api3('tag', 'getfields', $params);
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
function tag_getfields_expectedresult(){

  $expectedResult = array(
  'is_error' => 0,
  'version' => 3,
  'count' => 10,
  'values' => array(
      'id' => array(
          'name' => 'id',
          'type' => 1,
          'required' => true,
          'api.aliases' => array(
              '0' => 'tag',
            ),
        ),
      'name' => array(
          'name' => 'name',
          'type' => 2,
          'title' => 'Name',
          'required' => true,
          'maxlength' => 64,
          'size' => 30,
          'api.required' => 1,
        ),
      'description' => array(
          'name' => 'description',
          'type' => 2,
          'title' => 'Description',
          'maxlength' => 255,
          'size' => 45,
        ),
      'parent_id' => array(
          'name' => 'parent_id',
          'type' => 1,
          'default' => 'UL',
          'FKClassName' => 'CRM_Core_DAO_Tag',
        ),
      'is_selectable' => array(
          'name' => 'is_selectable',
          'type' => 16,
        ),
      'is_reserved' => array(
          'name' => 'is_reserved',
          'type' => 16,
        ),
      'is_tagset' => array(
          'name' => 'is_tagset',
          'type' => 16,
        ),
      'used_for' => array(
          'name' => 'used_for',
          'type' => 2,
          'title' => 'Used For',
          'maxlength' => 64,
          'size' => 30,
          'default' => 'UL',
          'api.default' => 'civicrm_contact',
        ),
      'created_id' => array(
          'name' => 'created_id',
          'type' => 1,
          'FKClassName' => 'CRM_Contact_DAO_Contact',
        ),
      'created_date' => array(
          'name' => 'created_date',
          'type' => 12,
          'title' => 'Tag Created Date',
        ),
    ),
);

  return $expectedResult;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testTagGetfields and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/TagTest.php
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