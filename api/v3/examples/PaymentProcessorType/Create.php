<?php
/**
 * Test Generated example demonstrating the PaymentProcessorType.create API.
 *
 * @return array
 *   API result array
 */
function payment_processor_type_create_example() {
  $params = [
    'sequential' => 1,
    'name' => 'API_Test_PP',
    'title' => 'API Test Payment Processor',
    'class_name' => 'CRM_Core_Payment_APITest',
    'billing_mode' => 'form',
    'is_recur' => 0,
  ];

  try{
    $result = civicrm_api3('PaymentProcessorType', 'create', $params);
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
function payment_processor_type_create_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 13,
    'values' => [
      '0' => [
        'id' => '13',
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
        'payment_instrument_id' => '1',
      ],
    ],
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testPaymentProcessorTypeCreate"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/PaymentProcessorTypeTest.php
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
