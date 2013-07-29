<?php
/**
 * Test Generated example of using payment_processor create API
 * *
 */
function payment_processor_create_example(){
$params = array(
  'name' => 'API Test PP',
  'payment_processor_type_id' => 1,
  'class_name' => 'CRM_Core_Payment_APITest',
  'is_recur' => 0,
  'domain_id' => 1,
);

try{
  $result = civicrm_api3('payment_processor', 'create', $params);
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
function payment_processor_create_expectedresult(){

  $expectedResult = array(
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 1,
  'values' => array(
      '1' => array(
          'id' => '1',
          'domain_id' => '1',
          'name' => 'API Test PP',
          'description' => '',
          'payment_processor_type_id' => '1',
          'is_active' => '',
          'is_default' => 0,
          'is_test' => 0,
          'user_name' => '',
          'password' => '',
          'signature' => '',
          'url_site' => '',
          'url_api' => '',
          'url_recur' => '',
          'url_button' => '',
          'subject' => '',
          'class_name' => 'CRM_Core_Payment_APITest',
          'billing_mode' => '1',
          'is_recur' => 0,
          'payment_type' => '1',
        ),
    ),
);

  return $expectedResult;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testPaymentProcessorCreate and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/PaymentProcessorTest.php
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