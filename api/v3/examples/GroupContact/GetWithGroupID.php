<?php
/**
 * Test Generated example of using group_contact get API
 * Get all from group and display contacts *
 */
function group_contact_get_example(){
$params = array(
  'group_id' => 1,
  'api.group.get' => 1,
  'sequential' => 1,
);

try{
  $result = civicrm_api3('group_contact', 'get', $params);
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
function group_contact_get_expectedresult(){

  $expectedResult = array(
  'is_error' => 0,
  'version' => 3,
  'count' => 2,
  'values' => array(
      '0' => array(
          'id' => '1',
          'group_id' => '1',
          'contact_id' => '3',
          'status' => 'Added',
          'api.group.get' => array(
              'is_error' => 0,
              'version' => 3,
              'count' => 1,
              'id' => 1,
              'values' => array(
                  '0' => array(
                      'id' => '1',
                      'name' => 'Test Group 1',
                      'title' => 'New Test Group Created',
                      'description' => 'New Test Group Created',
                      'is_active' => '1',
                      'visibility' => 'Public Pages',
                      'where_clause' => ' ( `civicrm_group_contact-1`.group_id IN ( 1 ) AND `civicrm_group_contact-1`.status IN (\"Added\") ) ',
                      'select_tables' => 'a:8:{s:15:\"civicrm_contact\";i:1;s:15:\"civicrm_address\";i:1;s:15:\"civicrm_country\";i:1;s:13:\"civicrm_email\";i:1;s:13:\"civicrm_phone\";i:1;s:10:\"civicrm_im\";i:1;s:19:\"civicrm_worldregion\";i:1;s:25:\"`civicrm_group_contact-1`\";s:165:\" LEFT JOIN civicrm_group_contact `civicrm_group_contact-1` ON ( contact_a.id = `civicrm_group_contact-1`.contact_id AND `civicrm_group_contact-1`.group_id IN ( 1 ) )\";}',
                      'where_tables' => 'a:2:{s:15:\"civicrm_contact\";i:1;s:25:\"`civicrm_group_contact-1`\";s:165:\" LEFT JOIN civicrm_group_contact `civicrm_group_contact-1` ON ( contact_a.id = `civicrm_group_contact-1`.contact_id AND `civicrm_group_contact-1`.group_id IN ( 1 ) )\";}',
                      'group_type' => array(
                          '0' => '1',
                          '1' => '2',
                        ),
                      'is_hidden' => 0,
                      'is_reserved' => 0,
                    ),
                ),
            ),
        ),
      '1' => array(
          'id' => '2',
          'group_id' => '1',
          'contact_id' => '1',
          'status' => 'Added',
          'api.group.get' => array(
              'is_error' => 0,
              'version' => 3,
              'count' => 1,
              'id' => 1,
              'values' => array(
                  '0' => array(
                      'id' => '1',
                      'name' => 'Test Group 1',
                      'title' => 'New Test Group Created',
                      'description' => 'New Test Group Created',
                      'is_active' => '1',
                      'visibility' => 'Public Pages',
                      'where_clause' => ' ( `civicrm_group_contact-1`.group_id IN ( 1 ) AND `civicrm_group_contact-1`.status IN (\"Added\") ) ',
                      'select_tables' => 'a:8:{s:15:\"civicrm_contact\";i:1;s:15:\"civicrm_address\";i:1;s:15:\"civicrm_country\";i:1;s:13:\"civicrm_email\";i:1;s:13:\"civicrm_phone\";i:1;s:10:\"civicrm_im\";i:1;s:19:\"civicrm_worldregion\";i:1;s:25:\"`civicrm_group_contact-1`\";s:165:\" LEFT JOIN civicrm_group_contact `civicrm_group_contact-1` ON ( contact_a.id = `civicrm_group_contact-1`.contact_id AND `civicrm_group_contact-1`.group_id IN ( 1 ) )\";}',
                      'where_tables' => 'a:2:{s:15:\"civicrm_contact\";i:1;s:25:\"`civicrm_group_contact-1`\";s:165:\" LEFT JOIN civicrm_group_contact `civicrm_group_contact-1` ON ( contact_a.id = `civicrm_group_contact-1`.contact_id AND `civicrm_group_contact-1`.group_id IN ( 1 ) )\";}',
                      'group_type' => array(
                          '0' => '1',
                          '1' => '2',
                        ),
                      'is_hidden' => 0,
                      'is_reserved' => 0,
                    ),
                ),
            ),
        ),
    ),
);

  return $expectedResult;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testGetGroupID and can be found in
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/GroupContactTest.php
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
