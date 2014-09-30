<?php
/**
 * Test Generated example of using tag getfields API
 * demonstrate use of getfields to interrogate api *
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
          'title' => 'Tag ID',
          'required' => true,
          'api.aliases' => array(
              '0' => 'tag',
            ),
        ),
      'name' => array(
          'name' => 'name',
          'type' => 2,
          'title' => 'Tag Name',
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
          'title' => 'Parent Tag',
          'default' => 'NULL',
          'FKClassName' => 'CRM_Core_DAO_Tag',
        ),
      'is_selectable' => array(
          'name' => 'is_selectable',
          'type' => 16,
          'title' => 'Display Tag?',
          'default' => '1',
        ),
      'is_reserved' => array(
          'name' => 'is_reserved',
          'type' => 16,
          'title' => 'Reserved',
        ),
      'is_tagset' => array(
          'name' => 'is_tagset',
          'type' => 16,
          'title' => 'Tagset',
        ),
      'used_for' => array(
          'name' => 'used_for',
          'type' => 2,
          'title' => 'Used For',
          'maxlength' => 64,
          'size' => 30,
          'default' => 'NULL',
          'html' => array(
              'type' => 'Select',
            ),
          'pseudoconstant' => array(
              'optionGroupName' => 'tag_used_for',
            ),
          'api.default' => 'civicrm_contact',
        ),
      'created_id' => array(
          'name' => 'created_id',
          'type' => 1,
          'title' => 'Tag Created By',
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
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/TagTest.php
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
