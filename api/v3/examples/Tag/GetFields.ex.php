<?php
/**
 * Test Generated example demonstrating the Tag.getfields API.
 *
 * Demonstrate use of getfields to interrogate api.
 *
 * @return array
 *   API result array
 */
function tag_getfields_example() {
  $params = [
    'action' => 'create',
  ];

  try{
    $result = civicrm_api3('Tag', 'getfields', $params);
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
function tag_getfields_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 11,
    'values' => [
      'id' => [
        'name' => 'id',
        'type' => 1,
        'title' => 'Tag ID',
        'description' => 'Tag ID',
        'required' => TRUE,
        'where' => 'civicrm_tag.id',
        'table_name' => 'civicrm_tag',
        'entity' => 'Tag',
        'bao' => 'CRM_Core_BAO_Tag',
        'localizable' => 0,
        'is_core_field' => TRUE,
        'api.aliases' => [
          '0' => 'tag',
        ],
      ],
      'name' => [
        'name' => 'name',
        'type' => 2,
        'title' => 'Tag Name',
        'description' => 'Name of Tag.',
        'required' => TRUE,
        'maxlength' => 64,
        'size' => 30,
        'where' => 'civicrm_tag.name',
        'table_name' => 'civicrm_tag',
        'entity' => 'Tag',
        'bao' => 'CRM_Core_BAO_Tag',
        'localizable' => 0,
        'is_core_field' => TRUE,
        'api.required' => 1,
      ],
      'description' => [
        'name' => 'description',
        'type' => 2,
        'title' => 'Description',
        'description' => 'Optional verbose description of the tag.',
        'maxlength' => 255,
        'size' => 45,
        'where' => 'civicrm_tag.description',
        'table_name' => 'civicrm_tag',
        'entity' => 'Tag',
        'bao' => 'CRM_Core_BAO_Tag',
        'localizable' => 0,
        'is_core_field' => TRUE,
      ],
      'parent_id' => [
        'name' => 'parent_id',
        'type' => 1,
        'title' => 'Parent Tag',
        'description' => 'Optional parent id for this tag.',
        'where' => 'civicrm_tag.parent_id',
        'default' => 'NULL',
        'table_name' => 'civicrm_tag',
        'entity' => 'Tag',
        'bao' => 'CRM_Core_BAO_Tag',
        'localizable' => 0,
        'FKClassName' => 'CRM_Core_DAO_Tag',
        'is_core_field' => TRUE,
        'FKApiName' => 'Tag',
      ],
      'is_selectable' => [
        'name' => 'is_selectable',
        'type' => 16,
        'title' => 'Display Tag?',
        'description' => 'Is this tag selectable / displayed',
        'where' => 'civicrm_tag.is_selectable',
        'default' => '1',
        'table_name' => 'civicrm_tag',
        'entity' => 'Tag',
        'bao' => 'CRM_Core_BAO_Tag',
        'localizable' => 0,
        'is_core_field' => TRUE,
      ],
      'is_reserved' => [
        'name' => 'is_reserved',
        'type' => 16,
        'title' => 'Reserved',
        'where' => 'civicrm_tag.is_reserved',
        'table_name' => 'civicrm_tag',
        'entity' => 'Tag',
        'bao' => 'CRM_Core_BAO_Tag',
        'localizable' => 0,
        'is_core_field' => TRUE,
      ],
      'is_tagset' => [
        'name' => 'is_tagset',
        'type' => 16,
        'title' => 'Tagset',
        'where' => 'civicrm_tag.is_tagset',
        'table_name' => 'civicrm_tag',
        'entity' => 'Tag',
        'bao' => 'CRM_Core_BAO_Tag',
        'localizable' => 0,
        'is_core_field' => TRUE,
      ],
      'used_for' => [
        'name' => 'used_for',
        'type' => 2,
        'title' => 'Used For',
        'maxlength' => 64,
        'size' => 30,
        'where' => 'civicrm_tag.used_for',
        'default' => 'NULL',
        'table_name' => 'civicrm_tag',
        'entity' => 'Tag',
        'bao' => 'CRM_Core_BAO_Tag',
        'localizable' => 0,
        'serialize' => 5,
        'html' => [
          'type' => 'Select',
          'maxlength' => 64,
          'size' => 30,
        ],
        'pseudoconstant' => [
          'optionGroupName' => 'tag_used_for',
          'optionEditPath' => 'civicrm/admin/options/tag_used_for',
        ],
        'is_core_field' => TRUE,
        'api.default' => 'civicrm_contact',
      ],
      'created_id' => [
        'name' => 'created_id',
        'type' => 1,
        'title' => 'Tag Created By',
        'description' => 'FK to civicrm_contact, who created this tag',
        'where' => 'civicrm_tag.created_id',
        'table_name' => 'civicrm_tag',
        'entity' => 'Tag',
        'bao' => 'CRM_Core_BAO_Tag',
        'localizable' => 0,
        'FKClassName' => 'CRM_Contact_DAO_Contact',
        'is_core_field' => TRUE,
        'FKApiName' => 'Contact',
      ],
      'color' => [
        'name' => 'color',
        'type' => 2,
        'title' => 'Color',
        'description' => 'Hex color value e.g. #ffffff',
        'maxlength' => 255,
        'size' => 45,
        'where' => 'civicrm_tag.color',
        'default' => 'NULL',
        'table_name' => 'civicrm_tag',
        'entity' => 'Tag',
        'bao' => 'CRM_Core_BAO_Tag',
        'localizable' => 0,
        'is_core_field' => TRUE,
      ],
      'created_date' => [
        'name' => 'created_date',
        'type' => 12,
        'title' => 'Tag Created Date',
        'description' => 'Date and time that tag was created.',
        'where' => 'civicrm_tag.created_date',
        'table_name' => 'civicrm_tag',
        'entity' => 'Tag',
        'bao' => 'CRM_Core_BAO_Tag',
        'localizable' => 0,
        'is_core_field' => TRUE,
      ],
    ],
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testTagGetfields"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/TagTest.php
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
