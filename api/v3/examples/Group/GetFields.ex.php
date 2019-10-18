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
  $params = [
    'action' => 'create',
  ];

  try{
    $result = civicrm_api3('Group', 'getfields', $params);
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
function group_getfields_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 20,
    'values' => [
      'id' => [
        'name' => 'id',
        'type' => 1,
        'title' => 'Group ID',
        'description' => 'Group ID',
        'required' => TRUE,
        'where' => 'civicrm_group.id',
        'table_name' => 'civicrm_group',
        'entity' => 'Group',
        'bao' => 'CRM_Contact_BAO_Group',
        'localizable' => 0,
        'is_core_field' => TRUE,
        'api.aliases' => [
          '0' => 'group_id',
        ],
      ],
      'name' => [
        'name' => 'name',
        'type' => 2,
        'title' => 'Group Name',
        'description' => 'Internal name of Group.',
        'maxlength' => 64,
        'size' => 30,
        'where' => 'civicrm_group.name',
        'table_name' => 'civicrm_group',
        'entity' => 'Group',
        'bao' => 'CRM_Contact_BAO_Group',
        'localizable' => 0,
        'is_core_field' => TRUE,
      ],
      'title' => [
        'name' => 'title',
        'type' => 2,
        'title' => 'Group Title',
        'description' => 'Name of Group.',
        'maxlength' => 64,
        'size' => 30,
        'where' => 'civicrm_group.title',
        'table_name' => 'civicrm_group',
        'entity' => 'Group',
        'bao' => 'CRM_Contact_BAO_Group',
        'localizable' => 1,
        'html' => [
          'type' => 'Text',
          'maxlength' => 64,
          'size' => 30,
        ],
        'is_core_field' => TRUE,
        'api.required' => 1,
      ],
      'description' => [
        'name' => 'description',
        'type' => 32,
        'title' => 'Group Description',
        'description' => 'Optional verbose description of the group.',
        'rows' => 2,
        'cols' => 60,
        'where' => 'civicrm_group.description',
        'table_name' => 'civicrm_group',
        'entity' => 'Group',
        'bao' => 'CRM_Contact_BAO_Group',
        'localizable' => 0,
        'html' => [
          'type' => 'TextArea',
          'rows' => 2,
          'cols' => 60,
        ],
        'is_core_field' => TRUE,
      ],
      'source' => [
        'name' => 'source',
        'type' => 2,
        'title' => 'Group Source',
        'description' => 'Module or process which created this group.',
        'maxlength' => 64,
        'size' => 30,
        'where' => 'civicrm_group.source',
        'table_name' => 'civicrm_group',
        'entity' => 'Group',
        'bao' => 'CRM_Contact_BAO_Group',
        'localizable' => 0,
        'is_core_field' => TRUE,
      ],
      'saved_search_id' => [
        'name' => 'saved_search_id',
        'type' => 1,
        'title' => 'Saved Search ID',
        'description' => 'FK to saved search table.',
        'where' => 'civicrm_group.saved_search_id',
        'table_name' => 'civicrm_group',
        'entity' => 'Group',
        'bao' => 'CRM_Contact_BAO_Group',
        'localizable' => 0,
        'FKClassName' => 'CRM_Contact_DAO_SavedSearch',
        'is_core_field' => TRUE,
        'FKApiName' => 'SavedSearch',
      ],
      'is_active' => [
        'name' => 'is_active',
        'type' => 16,
        'title' => 'Group Enabled',
        'description' => 'Is this entry active?',
        'where' => 'civicrm_group.is_active',
        'table_name' => 'civicrm_group',
        'entity' => 'Group',
        'bao' => 'CRM_Contact_BAO_Group',
        'localizable' => 0,
        'is_core_field' => TRUE,
        'api.default' => 1,
      ],
      'visibility' => [
        'name' => 'visibility',
        'type' => 2,
        'title' => 'Group Visibility Setting',
        'description' => 'In what context(s) is this field visible.',
        'maxlength' => 24,
        'size' => 20,
        'where' => 'civicrm_group.visibility',
        'default' => 'User and User Admin Only',
        'table_name' => 'civicrm_group',
        'entity' => 'Group',
        'bao' => 'CRM_Contact_BAO_Group',
        'localizable' => 0,
        'html' => [
          'type' => 'Select',
          'maxlength' => 24,
          'size' => 20,
        ],
        'pseudoconstant' => [
          'callback' => 'CRM_Core_SelectValues::groupVisibility',
        ],
        'is_core_field' => TRUE,
      ],
      'where_clause' => [
        'name' => 'where_clause',
        'type' => 32,
        'title' => 'Group Where Clause',
        'description' => 'the sql where clause if a saved search acl',
        'where' => 'civicrm_group.where_clause',
        'table_name' => 'civicrm_group',
        'entity' => 'Group',
        'bao' => 'CRM_Contact_BAO_Group',
        'localizable' => 0,
        'is_core_field' => TRUE,
      ],
      'select_tables' => [
        'name' => 'select_tables',
        'type' => 32,
        'title' => 'Tables For Select Clause',
        'description' => 'the tables to be included in a select data',
        'where' => 'civicrm_group.select_tables',
        'table_name' => 'civicrm_group',
        'entity' => 'Group',
        'bao' => 'CRM_Contact_BAO_Group',
        'localizable' => 0,
        'serialize' => 4,
        'is_core_field' => TRUE,
      ],
      'where_tables' => [
        'name' => 'where_tables',
        'type' => 32,
        'title' => 'Tables For Where Clause',
        'description' => 'the tables to be included in the count statement',
        'where' => 'civicrm_group.where_tables',
        'table_name' => 'civicrm_group',
        'entity' => 'Group',
        'bao' => 'CRM_Contact_BAO_Group',
        'localizable' => 0,
        'serialize' => 4,
        'is_core_field' => TRUE,
      ],
      'group_type' => [
        'name' => 'group_type',
        'type' => 2,
        'title' => 'Group Type',
        'description' => 'FK to group type',
        'maxlength' => 128,
        'size' => 45,
        'where' => 'civicrm_group.group_type',
        'table_name' => 'civicrm_group',
        'entity' => 'Group',
        'bao' => 'CRM_Contact_BAO_Group',
        'localizable' => 0,
        'serialize' => 1,
        'pseudoconstant' => [
          'optionGroupName' => 'group_type',
          'optionEditPath' => 'civicrm/admin/options/group_type',
        ],
        'is_core_field' => TRUE,
      ],
      'cache_date' => [
        'name' => 'cache_date',
        'type' => 256,
        'title' => 'Group Cache Date',
        'description' => 'Date when we created the cache for a smart group',
        'required' => '',
        'where' => 'civicrm_group.cache_date',
        'table_name' => 'civicrm_group',
        'entity' => 'Group',
        'bao' => 'CRM_Contact_BAO_Group',
        'localizable' => 0,
        'is_core_field' => TRUE,
      ],
      'refresh_date' => [
        'name' => 'refresh_date',
        'type' => 256,
        'title' => 'Next Group Refresh Time',
        'description' => 'Date and time when we need to refresh the cache next.',
        'required' => '',
        'where' => 'civicrm_group.refresh_date',
        'table_name' => 'civicrm_group',
        'entity' => 'Group',
        'bao' => 'CRM_Contact_BAO_Group',
        'localizable' => 0,
        'is_core_field' => TRUE,
      ],
      'parents' => [
        'name' => 'parents',
        'type' => 32,
        'title' => 'Group Parents',
        'description' => 'IDs of the parent(s)',
        'where' => 'civicrm_group.parents',
        'table_name' => 'civicrm_group',
        'entity' => 'Group',
        'bao' => 'CRM_Contact_BAO_Group',
        'localizable' => 0,
        'serialize' => 5,
        'pseudoconstant' => [
          'callback' => 'CRM_Core_PseudoConstant::allGroup',
        ],
        'is_core_field' => TRUE,
      ],
      'children' => [
        'name' => 'children',
        'type' => 32,
        'title' => 'Group Children',
        'description' => 'IDs of the child(ren)',
        'where' => 'civicrm_group.children',
        'table_name' => 'civicrm_group',
        'entity' => 'Group',
        'bao' => 'CRM_Contact_BAO_Group',
        'localizable' => 0,
        'is_core_field' => TRUE,
      ],
      'is_hidden' => [
        'name' => 'is_hidden',
        'type' => 16,
        'title' => 'Group is Hidden',
        'description' => 'Is this group hidden?',
        'where' => 'civicrm_group.is_hidden',
        'table_name' => 'civicrm_group',
        'entity' => 'Group',
        'bao' => 'CRM_Contact_BAO_Group',
        'localizable' => 0,
        'is_core_field' => TRUE,
      ],
      'is_reserved' => [
        'name' => 'is_reserved',
        'type' => 16,
        'title' => 'Group is Reserved',
        'where' => 'civicrm_group.is_reserved',
        'table_name' => 'civicrm_group',
        'entity' => 'Group',
        'bao' => 'CRM_Contact_BAO_Group',
        'localizable' => 0,
        'is_core_field' => TRUE,
      ],
      'created_id' => [
        'name' => 'created_id',
        'type' => 1,
        'title' => 'Group Created By',
        'description' => 'FK to contact table.',
        'where' => 'civicrm_group.created_id',
        'table_name' => 'civicrm_group',
        'entity' => 'Group',
        'bao' => 'CRM_Contact_BAO_Group',
        'localizable' => 0,
        'FKClassName' => 'CRM_Contact_DAO_Contact',
        'is_core_field' => TRUE,
        'FKApiName' => 'Contact',
      ],
      'modified_id' => [
        'name' => 'modified_id',
        'type' => 1,
        'title' => 'Group Modified By',
        'description' => 'FK to contact table.',
        'where' => 'civicrm_group.modified_id',
        'table_name' => 'civicrm_group',
        'entity' => 'Group',
        'bao' => 'CRM_Contact_BAO_Group',
        'localizable' => 0,
        'FKClassName' => 'CRM_Contact_DAO_Contact',
        'is_core_field' => TRUE,
        'FKApiName' => 'Contact',
      ],
    ],
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testgetfields"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/GroupTest.php
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
