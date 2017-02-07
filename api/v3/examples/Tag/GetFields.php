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
  $params = array(
    'action' => 'create',
  );

  try{
    $result = civicrm_api3('Tag', 'getfields', $params);
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
function tag_getfields_expectedresult() {

  $expectedResult = array(
    'is_error' => 0,
    'version' => 3,
    'count' => 11,
    'values' => array(
      'id' => array(
        'name' => 'id',
        'type' => 1,
        'title' => 'Tag ID',
        'description' => 'Tag ID',
        'required' => TRUE,
        'table_name' => 'civicrm_tag',
        'entity' => 'Tag',
        'bao' => 'CRM_Core_BAO_Tag',
        'api.aliases' => array(
          '0' => 'tag',
        ),
      ),
      'name' => array(
        'name' => 'name',
        'type' => 2,
        'title' => 'Tag Name',
        'description' => 'Name of Tag.',
        'required' => TRUE,
        'maxlength' => 64,
        'size' => 30,
        'table_name' => 'civicrm_tag',
        'entity' => 'Tag',
        'bao' => 'CRM_Core_BAO_Tag',
        'api.required' => 1,
      ),
      'description' => array(
        'name' => 'description',
        'type' => 2,
        'title' => 'Description',
        'description' => 'Optional verbose description of the tag.',
        'maxlength' => 255,
        'size' => 45,
        'table_name' => 'civicrm_tag',
        'entity' => 'Tag',
        'bao' => 'CRM_Core_BAO_Tag',
      ),
      'parent_id' => array(
        'name' => 'parent_id',
        'type' => 1,
        'title' => 'Parent Tag',
        'description' => 'Optional parent id for this tag.',
        'default' => 'NULL',
        'table_name' => 'civicrm_tag',
        'entity' => 'Tag',
        'bao' => 'CRM_Core_BAO_Tag',
        'FKClassName' => 'CRM_Core_DAO_Tag',
        'FKApiName' => 'Tag',
      ),
      'is_selectable' => array(
        'name' => 'is_selectable',
        'type' => 16,
        'title' => 'Display Tag?',
        'description' => 'Is this tag selectable / displayed',
        'default' => '1',
        'table_name' => 'civicrm_tag',
        'entity' => 'Tag',
        'bao' => 'CRM_Core_BAO_Tag',
      ),
      'is_reserved' => array(
        'name' => 'is_reserved',
        'type' => 16,
        'title' => 'Reserved',
        'table_name' => 'civicrm_tag',
        'entity' => 'Tag',
        'bao' => 'CRM_Core_BAO_Tag',
      ),
      'is_tagset' => array(
        'name' => 'is_tagset',
        'type' => 16,
        'title' => 'Tagset',
        'table_name' => 'civicrm_tag',
        'entity' => 'Tag',
        'bao' => 'CRM_Core_BAO_Tag',
      ),
      'used_for' => array(
        'name' => 'used_for',
        'type' => 2,
        'title' => 'Used For',
        'maxlength' => 64,
        'size' => 30,
        'default' => 'NULL',
        'table_name' => 'civicrm_tag',
        'entity' => 'Tag',
        'bao' => 'CRM_Core_BAO_Tag',
        'html' => array(
          'type' => 'Select',
          'maxlength' => 64,
          'size' => 30,
        ),
        'pseudoconstant' => array(
          'optionGroupName' => 'tag_used_for',
          'optionEditPath' => 'civicrm/admin/options/tag_used_for',
        ),
        'api.default' => 'civicrm_contact',
      ),
      'created_id' => array(
        'name' => 'created_id',
        'type' => 1,
        'title' => 'Tag Created By',
        'description' => 'FK to civicrm_contact, who created this tag',
        'table_name' => 'civicrm_tag',
        'entity' => 'Tag',
        'bao' => 'CRM_Core_BAO_Tag',
        'FKClassName' => 'CRM_Contact_DAO_Contact',
        'FKApiName' => 'Contact',
      ),
      'color' => array(
        'name' => 'color',
        'type' => 2,
        'title' => 'Color',
        'description' => 'Hex color value e.g. #ffffff',
        'maxlength' => 255,
        'size' => 45,
        'default' => 'NULL',
        'table_name' => 'civicrm_tag',
        'entity' => 'Tag',
        'bao' => 'CRM_Core_BAO_Tag',
      ),
      'created_date' => array(
        'name' => 'created_date',
        'type' => 12,
        'title' => 'Tag Created Date',
        'description' => 'Date and time that tag was created.',
        'table_name' => 'civicrm_tag',
        'entity' => 'Tag',
        'bao' => 'CRM_Core_BAO_Tag',
      ),
    ),
  );

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testTagGetfields"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/TagTest.php
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
