<?php
/**
 * Test Generated example of using line_item create API
 * *
 */
function line_item_create_example(){
$params = array(
  'price_field_value_id' => 1,
  'price_field_id' => 1,
  'entity_table' => 'civicrm_contribution',
  'entity_id' => 1,
  'qty' => 1,
  'unit_price' => 50,
  'line_total' => 50,
  'debug' => 1,
);

try{
  $result = civicrm_api3('line_item', 'create', $params);
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
function line_item_create_expectedresult(){

  $expectedResult = array(
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 1,
  'values' => array(
      '1' => array(
          'id' => '1',
          'entity_table' => 'civicrm_contribution',
          'entity_id' => '1',
          'price_field_id' => '1',
          'label' => 'line item',
          'qty' => '1',
          'unit_price' => '50',
          'line_total' => '50',
          'participant_count' => '',
          'price_field_value_id' => '1',
          'financial_type_id' => '',
          'deductible_amount' => '',
        ),
    ),
);

  return $expectedResult;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testCreateLineItem and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/LineItemTest.php
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
