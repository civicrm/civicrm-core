<?php

/*
 
 */
function payment_processor_type_create_example(){
$params = array( 
  'version' => 3,
  'sequential' => 1,
  'name' => 'API_Test_PP',
  'title' => 'API Test Payment Processor',
  'class_name' => 'CRM_Core_Payment_APITest',
  'billing_mode' => 'form',
  'is_recur' => 0,
);

  $result = civicrm_api( 'payment_processor_type','create',$params );

  return $result;
}

/*
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

  return $expectedResult  ;
}


/*
* This example has been generated from the API test suite. The test that created it is called
*
* testPaymentProcessorTypeCreate and can be found in
* http://svn.civicrm.org/civicrm/trunk/tests/phpunit/CiviTest/api/v3/PaymentProcessorTypeTest.php
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