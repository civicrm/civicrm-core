<?php
/**
 * Test Generated example demonstrating the PaymentToken.create API.
 *
 * Create a payment token - Note use of relative dates here:
 * @link http://www.php.net/manual/en/datetime.formats.relative.php.
 *
 * @return array
 *   API result array
 */
function payment_token_create_example() {
  $params = [
    'token' => 'fancy-token-xxxx',
    'contact_id' => 3,
    'created_id' => 3,
    'payment_processor_id' => 1,
  ];

  try{
    $result = civicrm_api3('PaymentToken', 'create', $params);
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
function payment_token_create_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 1,
    'values' => [
      '1' => [
        'id' => '1',
        'contact_id' => '3',
        'payment_processor_id' => '1',
        'token' => 'fancy-token-xxxx',
        'created_date' => '2013-07-28 08:49:19',
        'created_id' => '3',
        'expiry_date' => '',
        'email' => '',
        'billing_first_name' => '',
        'billing_middle_name' => '',
        'billing_last_name' => '',
        'masked_account_number' => '',
        'ip_address' => '',
      ],
    ],
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testCreatePaymentToken"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/PaymentTokenTest.php
*
* You can see the outcome of the API tests at
* https://test.civicrm.org/job/CiviCRM-master-git/
*
* To Learn about the API read
* http://wiki.civicrm.org/confluence/display/CRMDOC/Using+the+API
*
* Browse the api on your own site with the api explorer
* http://MYSITE.ORG/path/to/civicrm/api
*
* Read more about testing here
* http://wiki.civicrm.org/confluence/display/CRM/Testing
*
* API Standards documentation:
* http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
*/
