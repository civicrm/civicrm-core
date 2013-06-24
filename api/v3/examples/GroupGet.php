<?php

/*
 
 */
function group_get_example(){
$params = array(
  'version' => 3,
  'name' => 'Test Group 1_5',
);

  $result = civicrm_api( 'group','get',$params );

  return $result;
}

/*
 * Function returns array of result expected from previous function
 */
function group_get_expectedresult(){

  $expectedResult = array(
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 5,
  'values' => array(
      '5' => array(
          'id' => '5',
          'name' => 'Test Group 1_5',
          'title' => 'New Test Group Created',
          'description' => 'New Test Group Created',
          'source' => '',
          'saved_search_id' => '',
          'is_active' => '1',
          'visibility' => 'Public Pages',
          'where_clause' => ' ( `civicrm_group_contact-5`.group_id IN ( 5 ) AND `civicrm_group_contact-5`.status IN ("Added") ) ',
          'select_tables' => 'a:9:{s:15:"civicrm_contact";i:1;s:15:"civicrm_address";i:1;s:22:"civicrm_state_province";i:1;s:15:"civicrm_country";i:1;s:13:"civicrm_email";i:1;s:13:"civicrm_phone";i:1;s:10:"civicrm_im";i:1;s:19:"civicrm_worldregion";i:1;s:25:"`civicrm_group_contact-5`";s:114:" LEFT JOIN civicrm_group_contact `civicrm_group_contact-5` ON contact_a.id = `civicrm_group_contact-5`.contact_id ";}',
          'where_tables' => 'a:2:{s:15:"civicrm_contact";i:1;s:25:"`civicrm_group_contact-5`";s:114:" LEFT JOIN civicrm_group_contact `civicrm_group_contact-5` ON contact_a.id = `civicrm_group_contact-5`.contact_id ";}',
          'group_type' => array(
              '0' => '1',
              '1' => '2',
            ),
          'cache_date' => '',
          'refresh_date' => '',
          'parents' => '',
          'children' => '',
          'is_hidden' => 0,
          'is_reserved' => 0,
          'created_id' => '',
        ),
    ),
);

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testGetGroupParamsWithGroupName and can be found in
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