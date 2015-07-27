<?php
/**
 * Test Generated example demonstrating the GroupContact.get API.
 *
 * Get all from group and display contacts.
 *
 * @return array
 *   API result array
 */
function group_contact_get_example() {
  $params = array(
    'group_id' => 3,
    'api.group.get' => 1,
    'sequential' => 1,
  );

  try{
    $result = civicrm_api3('GroupContact', 'get', $params);
  }
  catch (CiviCRM_API3_Exception $e) {
    // Handle error here.
    $errorMessage = $e->getMessage();
    $errorCode = $e->getErrorCode();
    $errorData = $e->getExtraParams();
    return array(
      'error' => $errorMessage,
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
function group_contact_get_expectedresult() {

  $expectedResult = array(
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 2,
    'values' => array(
      '0' => array(
        'id' => '2',
        'group_id' => '3',
        'contact_id' => '4',
        'status' => 'Added',
        'api.group.get' => array(
          'is_error' => 0,
          'version' => 3,
          'count' => 1,
          'id' => 3,
          'values' => array(
            '0' => array(
              'id' => '3',
              'name' => 'Test Group 1',
              'title' => 'New Test Group Created',
              'description' => 'New Test Group Created',
              'is_active' => '1',
              'visibility' => 'Public Pages',
              'where_clause' => ' ( `civicrm_group_contact-3`.group_id IN ( 3 ) AND `civicrm_group_contact-3`.status IN (\"Added\") ) ',
              'select_tables' => 'a:8:{s:15:\"civicrm_contact\";i:1;s:15:\"civicrm_address\";i:1;s:15:\"civicrm_country\";i:1;s:13:\"civicrm_email\";i:1;s:13:\"civicrm_phone\";i:1;s:10:\"civicrm_im\";i:1;s:19:\"civicrm_worldregion\";i:1;s:25:\"`civicrm_group_contact-3`\";s:165:\" LEFT JOIN civicrm_group_contact `civicrm_group_contact-3` ON ( contact_a.id = `civicrm_group_contact-3`.contact_id AND `civicrm_group_contact-3`.group_id IN ( 3 ) )\";}',
              'where_tables' => 'a:2:{s:15:\"civicrm_contact\";i:1;s:25:\"`civicrm_group_contact-3`\";s:165:\" LEFT JOIN civicrm_group_contact `civicrm_group_contact-3` ON ( contact_a.id = `civicrm_group_contact-3`.contact_id AND `civicrm_group_contact-3`.group_id IN ( 3 ) )\";}',
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
* This example has been generated from the API test suite.
* The test that created it is called "testGetGroupID"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/GroupContactTest.php
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
