<?php
/**
 * Test Generated example demonstrating the Group.getfields API.
 *
 * Demonstrate use of getfields to interrogate api.
 *
 * @return array
 *   API result array
 */
function group_getfields_example() {
  $params = array(
    'action' => 'create',
  );

  try{
    $result = civicrm_api3('Group', 'getfields', $params);
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
function group_getfields_expectedresult() {

  $expectedResult = array(
    'is_error' => 0,
    'version' => 3,
    'count' => 20,
    'values' => array(
      'id' => array(
        'name' => 'id',
        'type' => 1,
        'title' => 'Group ID',
        'description' => 'Group ID',
        'required' => TRUE,
        'table_name' => 'civicrm_group',
        'entity' => 'Group',
        'bao' => 'CRM_Contact_BAO_Group',
        'api.aliases' => array(
          '0' => 'group_id',
        ),
      ),
      'name' => array(
        'name' => 'name',
        'type' => 2,
        'title' => 'Group Name',
        'description' => 'Internal name of Group.',
        'maxlength' => 64,
        'size' => 30,
        'table_name' => 'civicrm_group',
        'entity' => 'Group',
        'bao' => 'CRM_Contact_BAO_Group',
      ),
      'title' => array(
        'name' => 'title',
        'type' => 2,
        'title' => 'Group Title',
        'description' => 'Name of Group.',
        'maxlength' => 64,
        'size' => 30,
        'table_name' => 'civicrm_group',
        'entity' => 'Group',
        'bao' => 'CRM_Contact_BAO_Group',
        'api.required' => 1,
      ),
      'description' => array(
        'name' => 'description',
        'type' => 32,
        'title' => 'Group Description',
        'description' => 'Optional verbose description of the group.',
        'rows' => 2,
        'cols' => 60,
        'table_name' => 'civicrm_group',
        'entity' => 'Group',
        'bao' => 'CRM_Contact_BAO_Group',
        'html' => array(
          'type' => 'TextArea',
          'rows' => 2,
          'cols' => 60,
        ),
      ),
      'source' => array(
        'name' => 'source',
        'type' => 2,
        'title' => 'Group Source',
        'description' => 'Module or process which created this group.',
        'maxlength' => 64,
        'size' => 30,
        'table_name' => 'civicrm_group',
        'entity' => 'Group',
        'bao' => 'CRM_Contact_BAO_Group',
      ),
      'saved_search_id' => array(
        'name' => 'saved_search_id',
        'type' => 1,
        'title' => 'Saved Search ID',
        'description' => 'FK to saved search table.',
        'table_name' => 'civicrm_group',
        'entity' => 'Group',
        'bao' => 'CRM_Contact_BAO_Group',
        'FKClassName' => 'CRM_Contact_DAO_SavedSearch',
        'FKApiName' => 'SavedSearch',
      ),
      'is_active' => array(
        'name' => 'is_active',
        'type' => 16,
        'title' => 'Group Enabled',
        'description' => 'Is this entry active?',
        'table_name' => 'civicrm_group',
        'entity' => 'Group',
        'bao' => 'CRM_Contact_BAO_Group',
        'api.default' => 1,
      ),
      'visibility' => array(
        'name' => 'visibility',
        'type' => 2,
        'title' => 'Group Visibility Setting',
        'description' => 'In what context(s) is this field visible.',
        'maxlength' => 24,
        'size' => 20,
        'default' => 'User and User Admin Only',
        'table_name' => 'civicrm_group',
        'entity' => 'Group',
        'bao' => 'CRM_Contact_BAO_Group',
        'html' => array(
          'type' => 'Select',
          'maxlength' => 24,
          'size' => 20,
        ),
        'pseudoconstant' => array(
          'callback' => 'CRM_Core_SelectValues::groupVisibility',
        ),
      ),
      'where_clause' => array(
        'name' => 'where_clause',
        'type' => 32,
        'title' => 'Group Where Clause',
        'description' => 'the sql where clause if a saved search acl',
        'table_name' => 'civicrm_group',
        'entity' => 'Group',
        'bao' => 'CRM_Contact_BAO_Group',
      ),
      'select_tables' => array(
        'name' => 'select_tables',
        'type' => 32,
        'title' => 'Tables For Select Clause',
        'description' => 'the tables to be included in a select data',
        'table_name' => 'civicrm_group',
        'entity' => 'Group',
        'bao' => 'CRM_Contact_BAO_Group',
      ),
      'where_tables' => array(
        'name' => 'where_tables',
        'type' => 32,
        'title' => 'Tables For Where Clause',
        'description' => 'the tables to be included in the count statement',
        'table_name' => 'civicrm_group',
        'entity' => 'Group',
        'bao' => 'CRM_Contact_BAO_Group',
      ),
      'group_type' => array(
        'name' => 'group_type',
        'type' => 2,
        'title' => 'Group Type',
        'description' => 'FK to group type',
        'maxlength' => 128,
        'size' => 45,
        'table_name' => 'civicrm_group',
        'entity' => 'Group',
        'bao' => 'CRM_Contact_BAO_Group',
        'pseudoconstant' => array(
          'optionGroupName' => 'group_type',
          'optionEditPath' => 'civicrm/admin/options/group_type',
        ),
      ),
      'cache_date' => array(
        'name' => 'cache_date',
        'type' => 256,
        'title' => 'Group Cache Date',
        'description' => 'Date when we created the cache for a smart group',
        'required' => '',
        'table_name' => 'civicrm_group',
        'entity' => 'Group',
        'bao' => 'CRM_Contact_BAO_Group',
      ),
      'refresh_date' => array(
        'name' => 'refresh_date',
        'type' => 256,
        'title' => 'Next Group Refresh Time',
        'description' => 'Date and time when we need to refresh the cache next.',
        'required' => '',
        'table_name' => 'civicrm_group',
        'entity' => 'Group',
        'bao' => 'CRM_Contact_BAO_Group',
      ),
      'parents' => array(
        'name' => 'parents',
        'type' => 32,
        'title' => 'Group Parents',
        'description' => 'IDs of the parent(s)',
        'table_name' => 'civicrm_group',
        'entity' => 'Group',
        'bao' => 'CRM_Contact_BAO_Group',
      ),
      'children' => array(
        'name' => 'children',
        'type' => 32,
        'title' => 'Group Children',
        'description' => 'IDs of the child(ren)',
        'table_name' => 'civicrm_group',
        'entity' => 'Group',
        'bao' => 'CRM_Contact_BAO_Group',
      ),
      'is_hidden' => array(
        'name' => 'is_hidden',
        'type' => 16,
        'title' => 'Group is Hidden',
        'description' => 'Is this group hidden?',
        'table_name' => 'civicrm_group',
        'entity' => 'Group',
        'bao' => 'CRM_Contact_BAO_Group',
      ),
      'is_reserved' => array(
        'name' => 'is_reserved',
        'type' => 16,
        'title' => 'Group is Reserved',
        'table_name' => 'civicrm_group',
        'entity' => 'Group',
        'bao' => 'CRM_Contact_BAO_Group',
      ),
      'created_id' => array(
        'name' => 'created_id',
        'type' => 1,
        'title' => 'Group Created By',
        'description' => 'FK to contact table.',
        'table_name' => 'civicrm_group',
        'entity' => 'Group',
        'bao' => 'CRM_Contact_BAO_Group',
        'FKClassName' => 'CRM_Contact_DAO_Contact',
        'FKApiName' => 'Contact',
      ),
      'modified_id' => array(
        'name' => 'modified_id',
        'type' => 1,
        'title' => 'Group Modified By',
        'description' => 'FK to contact table.',
        'table_name' => 'civicrm_group',
        'entity' => 'Group',
        'bao' => 'CRM_Contact_BAO_Group',
        'FKClassName' => 'CRM_Contact_DAO_Contact',
        'FKApiName' => 'Contact',
      ),
    ),
  );

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testgetfields"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/GroupTest.php
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
