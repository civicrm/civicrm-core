<?php
/**
 * Test Generated example demonstrating the Order.create API.
 *
 * Create order for participant
 *
 * @return array
 *   API result array
 */
function order_create_example() {
  $params = [
    'contact_id' => 11,
    'receive_date' => '2010-01-20',
    'total_amount' => 300,
    'financial_type_id' => 1,
    'contribution_status_id' => 1,
    'line_items' => [
      '0' => [
        'line_item' => [
          '2' => [
            'price_field_id' => '2',
            'price_field_value_id' => '2',
            'label' => 'Price Field 1',
            'field_title' => 'Price Field 1',
            'qty' => 1,
            'unit_price' => '100.000000000',
            'line_total' => '100.000000000',
            'financial_type_id' => '4',
            'entity_table' => 'civicrm_participant',
          ],
          '3' => [
            'price_field_id' => '2',
            'price_field_value_id' => '3',
            'label' => 'Price Field 2',
            'field_title' => 'Price Field 2',
            'qty' => 1,
            'unit_price' => '200.000000000',
            'line_total' => '200.000000000',
            'financial_type_id' => '4',
            'entity_table' => 'civicrm_participant',
          ],
        ],
        'params' => [
          'contact_id' => 11,
          'event_id' => 1,
          'status_id' => 1,
          'role_id' => 1,
          'register_date' => '2007-07-21 00:00:00',
          'source' => 'Online Event Registration: API Testing',
        ],
      ],
    ],
  ];

  try{
    $result = civicrm_api3('Order', 'create', $params);
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
function order_create_expectedresult() {

  $expectedResult = [
    'is_error' => 0,
    'version' => 3,
    'count' => 1,
    'id' => 1,
    'values' => [
      '1' => [
        'id' => '1',
        'contact_id' => '11',
        'financial_type_id' => '1',
        'contribution_page_id' => '',
        'payment_instrument_id' => '4',
        'receive_date' => '20100120000000',
        'non_deductible_amount' => '',
        'total_amount' => '300',
        'fee_amount' => 0,
        'net_amount' => '300',
        'trxn_id' => '',
        'invoice_id' => '',
        'invoice_number' => '',
        'currency' => 'USD',
        'cancel_date' => '',
        'cancel_reason' => '',
        'receipt_date' => '',
        'thankyou_date' => '',
        'source' => '',
        'amount_level' => '',
        'contribution_recur_id' => '',
        'is_test' => '',
        'is_pay_later' => '',
        'contribution_status_id' => '1',
        'address_id' => '',
        'check_number' => '',
        'campaign_id' => '',
        'creditnote_id' => '',
        'tax_amount' => '',
        'revenue_recognition_date' => '',
        'contribution_type_id' => '1',
      ],
    ],
  ];

  return $expectedResult;
}

/*
* This example has been generated from the API test suite.
* The test that created it is called "testAddOrderForParticipant"
* and can be found at:
* https://github.com/civicrm/civicrm-core/blob/master/tests/phpunit/api/v3/OrderTest.php
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
