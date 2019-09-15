<?php
/**
 * Test Generated example demonstrating the PaymentProcessor.create API.
 *
 * @return array
 *   API result array
 */
function payment_processor_create_example() {
  $params = [
    'name' => 'API Test PP',
    'payment_processor_type_id' => 1,
    'class_name' => 'CRM_Core_Payment_APITest',
    'is_recur' => 0,
    'domain_id' => 1,
  ];

  try{
    $result = civicrm_api3('PaymentProcessor', 'create', $params);
  }
  catch (CiviCRM_API3_Exception $e) {
    // Handle error here.
    $errorMessage = $e->getMessage();
    $errorCode = $e->getErrorCode();
    $errorData = $e->getExtraParams();
    return [
      'is_error' => 1,
      'error_message' => $errorMessage,
      'error_code' => $errorCode,
      'error_data' => $errorData,
    ];
  }

  return $result;
}

/**
 * Function returns array of result expected from previous function.
 *
 * @return array
 *   API result array
 */
function payment_processor_create_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 1,
    'values' => [
      '1' => [
        'id' => '1',
        'domain_id' => '1',
        'name' => 'API Test PP',
        'title' => '',
        'description' => '',
        'payment_processor_type_id' => '1',
        'is_active' => '1',
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
        'payment_instrument_id' => '1',
        'accepted_credit_cards' => '',
      ],
    ],
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testPaymentProcessorCreate"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/PaymentProcessorTest.php
*
* You can see the outcome of the API tests at
* https://test.civicrm.org/job/CiviCRM-Core-Matrix/
*
* To Learn about the API read
* https://docs.civicrm.org/dev/en/latest/api/
*
* Browse the API on your own site with the API Explorer. It is in the main
* CiviCRM menu, under: Support > Development > API Explorer.
*
* Read more about testing here
* https://docs.civicrm.org/dev/en/latest/testing/
*
* API Standards documentation:
* https://docs.civicrm.org/dev/en/latest/framework/api-architecture/
*/
