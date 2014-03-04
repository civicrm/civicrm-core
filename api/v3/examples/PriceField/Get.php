<?php
/**
 * Test Generated example of using price_field get API
 * *
 */
function price_field_get_example(){
$params = array(
  'name' => 'contribution_amount',
);

try{
  $result = civicrm_api3('price_field', 'get', $params);
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
function price_field_get_expectedresult(){

  $expectedResult = array(
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 1,
  'values' => array(
      '1' => array(
          'id' => '1',
          'price_set_id' => '1',
          'name' => 'contribution_amount',
          'label' => 'Contribution Amount',
          'html_type' => 'Text',
          'is_enter_qty' => 0,
          'weight' => '1',
          'is_display_amounts' => '1',
          'options_per_line' => '1',
          'is_active' => '1',
          'is_required' => '1',
          'visibility_id' => '1',
        ),
    ),
);

  return $expectedResult;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testGetBasicPriceField and can be found in
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/PriceFieldTest.php
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
