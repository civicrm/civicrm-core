<?php

/*
 demonstrate use of getfields to interogate api
 */
function group_getfields_example(){
$params = array( 
  'version' => 3,
  'action' => 'create',
);

  $result = civicrm_api( 'group','getfields',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function group_getfields_expectedresult(){

  $expectedResult = array( 
  'is_error' => 0,
  'version' => 3,
  'count' => 19,
  'values' => array( 
      'id' => array( 
          'name' => 'id',
          'type' => 1,
          'title' => 'Group ID',
          'required' => true,
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
          'default' => 'User and User Admin Only',
          'enumValues' => 'User and User Admin Only,Public Pages',
          'options' => array( 
              '0' => 'User and User Admin Only',
              '1' => 'Public Pages',
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
    ),
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testgetfields and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/GroupTest.php
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