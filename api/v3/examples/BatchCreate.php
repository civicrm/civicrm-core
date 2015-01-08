<?php
/**
 * Test Generated example of using batch create API
 * *
 */
function batch_create_example(){
$params = array(
  'name' => 'New_Batch_03',
  'title' => 'New Batch 03',
  'description' => 'This is description for New Batch 03',
  'total' => '300.33',
  'item_count' => 3,
  'status_id' => 1,
);

try{
  $result = civicrm_api3('batch', 'create', $params);
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
function batch_create_expectedresult(){

  $expectedResult = array(
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 1,
  'values' => array(
      '1' => array(
          'id' => '1',
          'name' => 'New_Batch_03',
          'title' => 'New Batch 03',
          'description' => 'This is description for New Batch 03',
          'created_id' => '',
          'created_date' => '',
          'modified_id' => '',
          'modified_date' => '2012-11-14 16:02:35',
          'saved_search_id' => '',
          'status_id' => '1',
          'type_id' => '',
          'mode_id' => '',
          'total' => '300.33',
          'item_count' => '3',
          'payment_instrument_id' => '',
          'exported_date' => '',
          'data' => '',
        ),
    ),
);

  return $expectedResult;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testCreate and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/BatchTest.php
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
