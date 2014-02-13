<?php
/**
 * Test Generated example of using line_item get API
 * *
 */
function line_item_get_example(){
$params = array(
  'entity_table' => 'civicrm_contribution',
);

try{
  $result = civicrm_api3('line_item', 'get', $params);
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
function line_item_get_expectedresult(){

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
          'label' => 'Contribution Amount',
          'qty' => '1',
          'unit_price' => '100.00',
          'line_total' => '100.00',
          'price_field_value_id' => '1',
          'financial_type_id' => '1',
          'deductible_amount' => '0.00',
        ),
    ),
);

  return $expectedResult;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testGetBasicLineItem and can be found in
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/LineItemTest.php
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
