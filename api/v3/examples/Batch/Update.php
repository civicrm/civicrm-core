<?php
/**
 * Test Generated example of using batch update API.
 *
 * @return array
 *   API result array
 */
function batch_update_example() {
  $params = array(
    'name' => 'New_Batch_04',
    'title' => 'New Batch 04',
    'description' => 'This is description for New Batch 04',
    'total' => '400.44',
    'item_count' => 4,
    'id' => 3,
  );

  try{
    $result = civicrm_api3('batch', 'update', $params);
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
function batch_update_expectedresult() {

  $expectedResult = array(
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 3,
    'values' => array(
      '3' => array(
        'id' => '3',
        'name' => 'New_Batch_04',
        'title' => 'New Batch 04',
        'description' => 'This is description for New Batch 04',
        'created_id' => '',
        'created_date' => '',
        'modified_id' => '',
        'modified_date' => '',
        'saved_search_id' => '',
        'status_id' => '',
        'type_id' => '',
        'mode_id' => '',
        'total' => '400.44',
        'item_count' => '4',
        'payment_instrument_id' => '',
        'exported_date' => '',
        'data' => '',
      ),
    ),
  );

  return $expectedResult;
}

/**
* This example has been generated from the API test suite.
* The test that created it is called
* testUpdate
* and can be found in
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/BatchTest.php
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
