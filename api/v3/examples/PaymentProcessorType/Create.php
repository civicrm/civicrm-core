<?php
/**
 * Test Generated example of using payment_processor_type create API
 * *
 */
function payment_processor_type_create_example(){
$params = array(
  'sequential' => 1,
  'name' => 'API_Test_PP',
  'title' => 'API Test Payment Processor',
  'class_name' => 'CRM_Core_Payment_APITest',
  'billing_mode' => 'form',
  'is_recur' => 0,
);

try{
  $result = civicrm_api3('payment_processor_type', 'create', $params);
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
function payment_processor_type_create_expectedresult(){

  $expectedResult = array(
  'is_error' => 0,
  'version' => 3,
  'count' => 1,
  'id' => 1,
  'values' => array(
      '0' => array(
          'id' => '1',
          'name' => 'API_Test_PP',
          'title' => 'API Test Payment Processor',
          'description' => '',
          'is_active' => '1',
          'is_default' => '',
          'user_name_label' => '',
          'password_label' => '',
          'signature_label' => '',
          'subject_label' => '',
          'class_name' => 'CRM_Core_Payment_APITest',
          'url_site_default' => '',
          'url_api_default' => '',
          'url_recur_default' => '',
          'url_button_default' => '',
          'url_site_test_default' => '',
          'url_api_test_default' => '',
          'url_recur_test_default' => '',
          'url_button_test_default' => '',
          'billing_mode' => '1',
          'is_recur' => 0,
          'payment_type' => '',
        ),
    ),
);

  return $expectedResult;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testPaymentProcessorTypeCreate and can be found in
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/PaymentProcessorTypeTest.php
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
