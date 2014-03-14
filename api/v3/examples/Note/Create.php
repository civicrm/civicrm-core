<?php
/**
 * Test Generated example of using note create API
 * *
 */
function note_create_example(){
$params = array(
  'entity_table' => 'civicrm_contact',
  'entity_id' => 1,
  'note' => 'Hello!!! m testing Note',
  'contact_id' => 1,
  'modified_date' => '2011-01-31',
  'subject' => 'Test Note',
);

try{
  $result = civicrm_api3('note', 'create', $params);
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
function note_create_expectedresult(){

  $expectedResult = array(
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 2,
  'values' => array(
      '2' => array(
          'id' => '2',
          'entity_table' => 'civicrm_contact',
          'entity_id' => '1',
          'note' => 'Hello!!! m testing Note',
          'contact_id' => '1',
          'modified_date' => '2012-11-14 16:02:35',
          'subject' => 'Test Note',
          'privacy' => 0,
        ),
    ),
);

  return $expectedResult;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testCreate and can be found in
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/NoteTest.php
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
