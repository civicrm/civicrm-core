<?php
/**
 * Test Generated example of using price_set create API
 * *
 */
function price_set_create_example(){
$params = array(
  'name' => 'default_goat_priceset',
  'title' => 'Goat accessories',
  'is_active' => 1,
  'help_pre' => 'Please describe your goat in detail',
  'help_post' => 'thank you for your time',
  'extends' => 2,
  'financial_type_id' => 1,
  'is_quick_config' => 1,
  'is_reserved' => 1,
);

try{
  $result = civicrm_api3('price_set', 'create', $params);
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
function price_set_create_expectedresult(){

  $expectedResult = array(
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 15,
  'values' => array(
      '15' => array(
          'id' => '15',
          'domain_id' => '',
          'name' => 'default_goat_priceset',
          'title' => 'Goat accessories',
          'is_active' => '1',
          'help_pre' => 'Please describe your goat in detail',
          'help_post' => 'thank you for your time',
          'javascript' => '',
          'extends' => '2',
          'financial_type_id' => '1',
          'is_quick_config' => '1',
          'is_reserved' => '1',
        ),
    ),
);

  return $expectedResult;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testCreatePriceSet and can be found in
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/PriceSetTest.php
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
