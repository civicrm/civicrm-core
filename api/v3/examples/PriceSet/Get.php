<?php
/**
 * Test Generated example of using price_set get API
 * *
 */
function price_set_get_example(){
$params = array(
  'name' => 'default_contribution_amount',
);

try{
  $result = civicrm_api3('price_set', 'get', $params);
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
function price_set_get_expectedresult(){

  $expectedResult = array(
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 1,
  'values' => array(
      '1' => array(
          'id' => '1',
          'name' => 'default_contribution_amount',
          'title' => 'Contribution Amount',
          'is_active' => '1',
          'extends' => '2',
          'is_quick_config' => '1',
          'is_reserved' => '1',
          'entity' => array(),
        ),
    ),
);

  return $expectedResult;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testGetBasicPriceSet and can be found in
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
