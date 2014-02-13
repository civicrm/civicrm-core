<?php
/**
 * Test Generated example of using price_field_value get API
 * *
 */
function price_field_value_get_example(){
$params = array(
  'name' => 'contribution_amount',
);

try{
  $result = civicrm_api3('price_field_value', 'get', $params);
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
function price_field_value_get_expectedresult(){

  $expectedResult = array(
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 1,
  'values' => array(
      '1' => array(
          'id' => '1',
          'price_field_id' => '1',
          'name' => 'contribution_amount',
          'label' => 'Contribution Amount',
          'amount' => '1',
          'weight' => '1',
          'is_default' => 0,
          'is_active' => '1',
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
* testGetBasicPriceFieldValue and can be found in
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/PriceFieldValueTest.php
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
