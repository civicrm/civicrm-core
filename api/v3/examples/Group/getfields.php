<?php
/**
 * Test Generated example of using group getfields API
 * demonstrate use of getfields to interrogate api *
 */
function group_getfields_example(){
$params = array(
  'action' => 'create',
);

try{
  $result = civicrm_api3('group', 'getfields', $params);
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
function group_getfields_expectedresult(){

  $expectedResult = array(
  'is_error' => 0,
  'version' => 3,
  'count' => 20,
  'values' => array(
      'id' => array(
          'name' => 'id',
          'type' => 1,
          'title' => 'Group ID',
          'required' => true,
          'api.aliases' => array(
              '0' => 'group_id',
            ),
        ),
      'name' => array(
          'name' => 'name',
          'type' => 2,
          'title' => 'Group Name',
          'maxlength' => 64,
          'size' => 30,
        ),
      'title' => array(
          'name' => 'title',
          'type' => 2,
          'title' => 'Group Title',
          'maxlength' => 64,
          'size' => 30,
          'api.required' => 1,
        ),
      'description' => array(
          'name' => 'description',
          'type' => 32,
          'title' => 'Group Description',
          'rows' => 2,
          'cols' => 60,
          'html' => array(
              'type' => 'TextArea',
            ),
        ),
      'source' => array(
          'name' => 'source',
          'type' => 2,
          'title' => 'Group Source',
          'maxlength' => 64,
          'size' => 30,
        ),
      'saved_search_id' => array(
          'name' => 'saved_search_id',
          'type' => 1,
          'title' => 'Saved Search ID',
          'FKClassName' => 'CRM_Contact_DAO_SavedSearch',
        ),
      'is_active' => array(
          'name' => 'is_active',
          'type' => 16,
          'title' => 'Group Enabled',
          'api.default' => 1,
        ),
      'visibility' => array(
          'name' => 'visibility',
          'type' => 2,
          'title' => 'Group Visibility Setting',
          'maxlength' => 24,
          'size' => 20,
          'default' => 'User and User Admin Only',
          'html' => array(
              'type' => 'Select',
            ),
          'pseudoconstant' => array(
              'callback' => 'CRM_Core_SelectValues::groupVisibility',
            ),
        ),
      'where_clause' => array(
          'name' => 'where_clause',
          'type' => 32,
          'title' => 'Group Where Clause',
        ),
      'select_tables' => array(
          'name' => 'select_tables',
          'type' => 32,
          'title' => 'Tables For Select Clause',
        ),
      'where_tables' => array(
          'name' => 'where_tables',
          'type' => 32,
          'title' => 'Tables For Where Clause',
        ),
      'group_type' => array(
          'name' => 'group_type',
          'type' => 2,
          'title' => 'Group Type',
          'maxlength' => 128,
          'size' => 45,
        ),
      'cache_date' => array(
          'name' => 'cache_date',
          'type' => 12,
          'title' => 'Group Cache Date',
        ),
      'refresh_date' => array(
          'name' => 'refresh_date',
          'type' => 12,
          'title' => 'Next Group Refresh Time',
        ),
      'parents' => array(
          'name' => 'parents',
          'type' => 32,
          'title' => 'Group Parents',
        ),
      'children' => array(
          'name' => 'children',
          'type' => 32,
          'title' => 'Group Children',
        ),
      'is_hidden' => array(
          'name' => 'is_hidden',
          'type' => 16,
          'title' => 'Group is Hidden',
        ),
      'is_reserved' => array(
          'name' => 'is_reserved',
          'type' => 16,
          'title' => 'Group is Reserved',
        ),
      'created_id' => array(
          'name' => 'created_id',
          'type' => 1,
          'title' => 'Group Created By',
          'FKClassName' => 'CRM_Contact_DAO_Contact',
        ),
      'modified_id' => array(
          'name' => 'modified_id',
          'type' => 1,
          'title' => 'Group Modified By',
          'FKClassName' => 'CRM_Contact_DAO_Contact',
        ),
    ),
);

  return $expectedResult;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testgetfields and can be found in
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/GroupTest.php
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
