<?php
/**
 * Test Generated example of using price_field create API
 * *
 */
function price_field_create_example(){
$params = array(
  'price_set_id' => 3,
  'name' => 'grassvariety',
  'label' => 'Grass Variety',
  'html_type' => 'Text',
  'is_enter_qty' => 1,
  'is_active' => 1,
);

try{
  $result = civicrm_api3('price_field', 'create', $params);
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
function price_field_create_expectedresult(){

  $expectedResult = array(
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 2,
  'values' => array(
      '2' => array(
          'id' => '2',
          'price_set_id' => '3',
          'name' => 'grassvariety',
          'label' => 'Grass Variety',
          'html_type' => 'Text',
          'is_enter_qty' => '1',
          'help_pre' => '',
          'help_post' => '',
          'weight' => '',
          'is_display_amounts' => '',
          'options_per_line' => '',
          'is_active' => '1',
          'is_required' => '',
          'active_on' => '',
          'expire_on' => '',
          'javascript' => '',
          'visibility_id' => '',
        ),
    ),
);

  return $expectedResult;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testCreatePriceField and can be found in
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
