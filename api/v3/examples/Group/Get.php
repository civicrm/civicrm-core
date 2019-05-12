<?php
/**
 * Test Generated example demonstrating the Group.get API.
 *
 * @return array
 *   API result array
 */
function group_get_example() {
  $params = [
    'name' => 'Test Group 1',
  ];

  try{
    $result = civicrm_api3('Group', 'get', $params);
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
function group_get_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 4,
    'values' => [
      '4' => [
        'id' => '4',
        'name' => 'Test Group 1',
        'title' => 'New Test Group Created',
        'description' => 'New Test Group Created',
        'is_active' => '1',
        'visibility' => 'Public Pages',
        'where_clause' => ' ( `civicrm_group_contact-4`.group_id IN ( 4 )   ) ',
        'select_tables' => 'a:8:{s:15:\"civicrm_contact\";i:1;s:15:\"civicrm_address\";i:1;s:15:\"civicrm_country\";i:1;s:13:\"civicrm_email\";i:1;s:13:\"civicrm_phone\";i:1;s:10:\"civicrm_im\";i:1;s:19:\"civicrm_worldregion\";i:1;s:25:\"`civicrm_group_contact-4`\";s:165:\" LEFT JOIN civicrm_group_contact `civicrm_group_contact-4` ON (contact_a.id = `civicrm_group_contact-4`.contact_id AND `civicrm_group_contact-4`.status IN (\'Added\'))\";}',
        'where_tables' => 'a:2:{s:15:\"civicrm_contact\";i:1;s:25:\"`civicrm_group_contact-4`\";s:165:\" LEFT JOIN civicrm_group_contact `civicrm_group_contact-4` ON (contact_a.id = `civicrm_group_contact-4`.contact_id AND `civicrm_group_contact-4`.status IN (\'Added\'))\";}',
        'group_type' => [
          '0' => '1',
          '1' => '2',
        ],
        'is_hidden' => 0,
        'is_reserved' => 0,
      ],
    ],
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testGetGroupParamsWithGroupName"
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
