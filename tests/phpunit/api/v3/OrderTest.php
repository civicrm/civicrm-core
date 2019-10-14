<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *  Test APIv3 civicrm_contribute_* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Contribution
 * @group headless
 */
class api_v3_OrderTest extends CiviUnitTestCase {

  protected $_individualId;
  protected $_financialTypeId = 1;
  public $debug = 0;

  /**
   * Setup function.
   *
   * @throws \CRM_Core_Exception
   */
  public function setUp() {
    parent::setUp();

    $this->_apiversion = 3;
    $this->_individualId = $this->individualCreate();
  }

  /**
   * Clean up after each test.
   *
   * @throws \CRM_Core_Exception
   */
  public function tearDown() {
    $this->quickCleanUpFinancialEntities();
    $this->quickCleanup(['civicrm_uf_match']);
  }

  /**
   * Test Get order api.
   */
  public function testGetOrder() {
    $contribution = $this->addOrder(FALSE, 100);

    $params = [
      'contribution_id' => $contribution['id'],
    ];

    $order = $this->callAPIAndDocument('Order', 'get', $params, __FUNCTION__, __FILE__);

    $this->assertEquals(1, $order['count']);
    $expectedResult = [
      $contribution['id'] => [
        'total_amount' => 100,
        'contribution_id' => $contribution['id'],
        'contribution_status' => 'Completed',
        'net_amount' => 100,
      ],
    ];
    $lineItems[] = [
      'entity_table' => 'civicrm_contribution',
      'entity_id' => $contribution['id'],
      'contribution_id' => $contribution['id'],
      'unit_price' => 100,
      'line_total' => 100,
      'financial_type_id' => 1,
    ];
    $this->checkPaymentResult($order, $expectedResult, $lineItems);
    $this->callAPISuccess('Contribution', 'Delete', [
      'id' => $contribution['id'],
    ]);
  }

  /**
   * Test Get Order api for participant contribution.
   */
  public function testGetOrderParticipant() {
    $this->addOrder(FALSE, 100);
    list($items, $contribution) = $this->createParticipantWithContribution();

    $params = [
      'contribution_id' => $contribution['id'],
    ];

    $order = $this->callAPISuccess('Order', 'get', $params);

    $this->assertEquals(2, count($order['values'][$contribution['id']]['line_items']));
    $this->callAPISuccess('Contribution', 'Delete', [
      'id' => $contribution['id'],
    ]);
  }

  /**
   * Function to assert db values.
   */
  public function checkPaymentResult($results, $expectedResult, $lineItems = NULL) {
    foreach ($expectedResult[$results['id']] as $key => $value) {
      $this->assertEquals($results['values'][$results['id']][$key], $value);
    }

    if ($lineItems) {
      foreach ($lineItems as $key => $items) {
        foreach ($items as $k => $item) {
          $this->assertEquals($results['values'][$results['id']]['line_items'][$key][$k], $item);
        }
      }
    }
  }

  /**
   * Add order.
   *
   * @param bool $isPriceSet
   * @param float $amount
   * @param array $extraParams
   *
   * @return array
   */
  public function addOrder($isPriceSet, $amount = 300.00, $extraParams = []) {
    $p = [
      'contact_id' => $this->_individualId,
      'receive_date' => '2010-01-20',
      'total_amount' => $amount,
      'financial_type_id' => $this->_financialTypeId,
      'contribution_status_id' => 1,
    ];

    if ($isPriceSet) {
      $priceFields = $this->createPriceSet();
      foreach ($priceFields['values'] as $key => $priceField) {
        $lineItems[1][$key] = [
          'price_field_id' => $priceField['price_field_id'],
          'price_field_value_id' => $priceField['id'],
          'label' => $priceField['label'],
          'field_title' => $priceField['label'],
          'qty' => 1,
          'unit_price' => $priceField['amount'],
          'line_total' => $priceField['amount'],
          'financial_type_id' => $priceField['financial_type_id'],
        ];
      }
      $p['line_item'] = $lineItems;
    }
    $p = array_merge($extraParams, $p);
    return $this->callAPISuccess('Contribution', 'create', $p);
  }

  /**
   * Test create order api
   */
  public function testAddOrder() {
    $order = $this->addOrder(FALSE, 100);
    $params = [
      'contribution_id' => $order['id'],
    ];
    $order = $this->callAPISuccess('order', 'get', $params);
    $expectedResult = [
      $order['id'] => [
        'total_amount' => 100,
        'contribution_id' => $order['id'],
        'contribution_status' => 'Completed',
        'net_amount' => 100,
      ],
    ];
    $lineItems[] = [
      'entity_table' => 'civicrm_contribution',
      'entity_id' => $order['id'],
      'contribution_id' => $order['id'],
      'unit_price' => 100,
      'line_total' => 100,
      'financial_type_id' => 1,
    ];
    $this->checkPaymentResult($order, $expectedResult, $lineItems);
    $this->callAPISuccess('Contribution', 'Delete', [
      'id' => $order['id'],
    ]);
  }

  /**
   * Test create order api for membership
   *
   * @throws \CRM_Core_Exception
   */
  public function testAddOrderForMembership() {
    $membershipType = $this->membershipTypeCreate();
    $membershipType1 = $this->membershipTypeCreate();
    $membershipType = $membershipTypes = [$membershipType, $membershipType1];
    $p = [
      'contact_id' => $this->_individualId,
      'receive_date' => '2010-01-20',
      'financial_type_id' => 'Event Fee',
      'contribution_status_id' => 1,
    ];
    $priceFields = $this->createPriceSet();
    foreach ($priceFields['values'] as $key => $priceField) {
      $lineItems[$key] = [
        'price_field_id' => $priceField['price_field_id'],
        'price_field_value_id' => $priceField['id'],
        'label' => $priceField['label'],
        'field_title' => $priceField['label'],
        'qty' => 1,
        'unit_price' => $priceField['amount'],
        'line_total' => $priceField['amount'],
        'financial_type_id' => $priceField['financial_type_id'],
        'entity_table' => 'civicrm_membership',
        'membership_type_id' => array_pop($membershipType),
      ];
    }
    $p['line_items'][] = [
      'line_item' => [array_pop($lineItems)],
      'params' => [
        'contact_id' => $this->_individualId,
        'membership_type_id' => array_pop($membershipTypes),
        'join_date' => '2006-01-21',
        'start_date' => '2006-01-21',
        'end_date' => '2006-12-21',
        'source' => 'Payment',
        'is_override' => 1,
        'status_id' => 1,
      ],
    ];
    $order = $this->callAPIAndDocument('order', 'create', $p, __FUNCTION__, __FILE__);
    $params = [
      'contribution_id' => $order['id'],
    ];
    $order = $this->callAPISuccess('order', 'get', $params);
    $expectedResult = [
      $order['id'] => [
        'total_amount' => 200,
        'contribution_id' => $order['id'],
        'contribution_status' => 'Completed',
        'net_amount' => 200,
      ],
    ];
    $this->checkPaymentResult($order, $expectedResult);
    $this->callAPISuccessGetCount('MembershipPayment', $params, 1);
    $this->callAPISuccess('Contribution', 'Delete', [
      'id' => $order['id'],
    ]);
    $p['line_items'][] = [
      'line_item' => [array_pop($lineItems)],
      'params' => [
        'contact_id' => $this->_individualId,
        'membership_type_id' => array_pop($membershipTypes),
        'join_date' => '2006-01-21',
        'start_date' => '2006-01-21',
        'end_date' => '2006-12-21',
        'source' => 'Payment',
        'is_override' => 1,
        'status_id' => 1,
      ],
    ];
    $p['total_amount'] = 300;
    $order = $this->callAPISuccess('order', 'create', $p);
    $expectedResult = [
      $order['id'] => [
        'total_amount' => 300,
        'contribution_status' => 'Completed',
        'net_amount' => 300,
      ],
    ];
    $paymentMembership = [
      'contribution_id' => $order['id'],
    ];
    $order = $this->callAPISuccess('order', 'get', $paymentMembership);
    $this->checkPaymentResult($order, $expectedResult);
    $this->callAPISuccessGetCount('MembershipPayment', $paymentMembership, 2);
    $this->callAPISuccess('Contribution', 'Delete', [
      'id' => $order['id'],
    ]);
  }

  /**
   * Test create order api for participant
   *
   * @throws \CRM_Core_Exception
   */
  public function testAddOrderForParticipant() {
    $event = $this->eventCreate();
    $this->_eventId = $event['id'];
    $p = [
      'contact_id' => $this->_individualId,
      'receive_date' => '2010-01-20',
      'total_amount' => 300,
      'financial_type_id' => $this->_financialTypeId,
      'contribution_status_id' => 1,
    ];
    $priceFields = $this->createPriceSet();
    foreach ($priceFields['values'] as $key => $priceField) {
      $lineItems[$key] = [
        'price_field_id' => $priceField['price_field_id'],
        'price_field_value_id' => $priceField['id'],
        'label' => $priceField['label'],
        'field_title' => $priceField['label'],
        'qty' => 1,
        'unit_price' => $priceField['amount'],
        'line_total' => $priceField['amount'],
        'financial_type_id' => $priceField['financial_type_id'],
        'entity_table' => 'civicrm_participant',
      ];
    }
    $p['line_items'][] = [
      'line_item' => $lineItems,
      'params' => [
        'contact_id' => $this->_individualId,
        'event_id' => $this->_eventId,
        'status_id' => 1,
        'role_id' => 1,
        'register_date' => '2007-07-21 00:00:00',
        'source' => 'Online Event Registration: API Testing',
      ],
    ];
    $order = $this->callAPIAndDocument('order', 'create', $p, __FUNCTION__, __FILE__, 'Create order for participant', 'CreateOrderParticipant');
    $params = [
      'contribution_id' => $order['id'],
    ];
    $order = $this->callAPISuccess('order', 'get', $params);
    $expectedResult = [
      $order['id'] => [
        'total_amount' => 300,
        'contribution_id' => $order['id'],
        'contribution_status' => 'Completed',
        'net_amount' => 300,
      ],
    ];
    $this->checkPaymentResult($order, $expectedResult);
    $this->callAPISuccessGetCount('ParticipantPayment', $params, 1);
    $this->callAPISuccess('Contribution', 'Delete', [
      'id' => $order['id'],
    ]);
    $p['line_items'][] = [
      'line_item' => $lineItems,
      'params' => [
        'contact_id' => $this->individualCreate(),
        'event_id' => $this->_eventId,
        'status_id' => 1,
        'role_id' => 1,
        'register_date' => '2007-07-21 00:00:00',
        'source' => 'Online Event Registration: API Testing',
      ],
    ];
    $p['total_amount'] = 600;
    $order = $this->callAPISuccess('order', 'create', $p);
    $expectedResult = [
      $order['id'] => [
        'total_amount' => 600,
        'contribution_status' => 'Completed',
        'net_amount' => 600,
      ],
    ];
    $paymentParticipant = [
      'contribution_id' => $order['id'],
    ];
    $order = $this->callAPISuccess('order', 'get', $paymentParticipant);
    $this->checkPaymentResult($order, $expectedResult);
    $this->callAPISuccessGetCount('ParticipantPayment', $paymentParticipant, 2);
    $this->callAPISuccess('Contribution', 'Delete', [
      'id' => $order['id'],
    ]);
  }

  /**
   * Test create order api with line items
   */
  public function testAddOrderWithLineItems() {
    $order = $this->addOrder(TRUE);
    $params = [
      'contribution_id' => $order['id'],
    ];
    $order = $this->callAPISuccess('order', 'get', $params);
    $expectedResult = [
      $order['id'] => [
        'total_amount' => 300,
        'contribution_id' => $order['id'],
        'contribution_status' => 'Completed',
        'net_amount' => 300,
      ],
    ];
    $items[] = [
      'entity_table' => 'civicrm_contribution',
      'entity_id' => $order['id'],
      'contribution_id' => $order['id'],
      'unit_price' => 100,
      'line_total' => 100,
    ];
    $items[] = [
      'entity_table' => 'civicrm_contribution',
      'entity_id' => $order['id'],
      'contribution_id' => $order['id'],
      'unit_price' => 200,
      'line_total' => 200,
    ];
    $this->checkPaymentResult($order, $expectedResult, $items);
    $params = [
      'entity_table' => 'civicrm_contribution',
      'entity_id' => $order['id'],
    ];
    $eft = $this->callAPISuccess('EntityFinancialTrxn', 'get', $params);
    $this->assertEquals($eft['values'][$eft['id']]['amount'], 300);
    $params = [
      'entity_table' => 'civicrm_financial_item',
      'financial_trxn_id' => $eft['values'][$eft['id']]['financial_trxn_id'],
    ];
    $eft = $this->callAPISuccess('EntityFinancialTrxn', 'get', $params);
    $amounts = [200, 100];
    foreach ($eft['values'] as $value) {
      $this->assertEquals($value['amount'], array_pop($amounts));
    }
    $this->callAPISuccess('Contribution', 'Delete', [
      'id' => $order['id'],
    ]);
  }

  /**
   * Test delete order api
   */
  public function testDeleteOrder() {
    $order = $this->addOrder(FALSE, 100);
    $params = [
      'contribution_id' => $order['id'],
    ];
    try {
      $this->callAPISuccess('order', 'delete', $params);
      $this->fail("Missed expected exception");
    }
    catch (Exception $expected) {
      $this->callAPISuccess('Contribution', 'create', [
        'contribution_id' => $order['id'],
        'is_test' => TRUE,
      ]);
      $this->callAPIAndDocument('order', 'delete', $params, __FUNCTION__, __FILE__);
      $order = $this->callAPISuccess('order', 'get', $params);
      $this->assertEquals(0, $order['count']);
    }
  }

  /**
   * Test cancel order api
   */
  public function testCancelOrder() {
    $contribution = $this->addOrder(FALSE, 100);
    $params = [
      'contribution_id' => $contribution['id'],
    ];
    $this->callAPIAndDocument('order', 'cancel', $params, __FUNCTION__, __FILE__);
    $order = $this->callAPISuccess('Order', 'get', $params);
    $expectedResult = [
      $contribution['id'] => [
        'total_amount' => 100,
        'contribution_id' => $contribution['id'],
        'contribution_status' => 'Cancelled',
        'net_amount' => 100,
      ],
    ];
    $this->checkPaymentResult($order, $expectedResult);
    $this->callAPISuccess('Contribution', 'Delete', [
      'id' => $contribution['id'],
    ]);
  }

  /**
   * Test cancel order api
   */
  public function testCancelWithParticipant() {
    $event = $this->eventCreate();
    $this->_eventId = $event['id'];
    $eventParams = [
      'id' => $this->_eventId,
      'financial_type_id' => 4,
      'is_monetary' => 1,
    ];
    $this->callAPISuccess('event', 'create', $eventParams);
    $participantParams = [
      'financial_type_id' => 4,
      'event_id' => $this->_eventId,
      'role_id' => 1,
      'status_id' => 1,
      'fee_currency' => 'USD',
      'contact_id' => $this->_individualId,
    ];
    $participant = $this->callAPISuccess('Participant', 'create', $participantParams);
    $extraParams = [
      'contribution_mode' => 'participant',
      'participant_id' => $participant['id'],
    ];
    $contribution = $this->addOrder(TRUE, 100, $extraParams);
    $paymentParticipant = [
      'participant_id' => $participant['id'],
      'contribution_id' => $contribution['id'],
    ];
    $this->callAPISuccess('ParticipantPayment', 'create', $paymentParticipant);
    $params = [
      'contribution_id' => $contribution['id'],
    ];
    $this->callAPISuccess('order', 'cancel', $params);
    $order = $this->callAPISuccess('Order', 'get', $params);
    $expectedResult = [
      $contribution['id'] => [
        'total_amount' => 100,
        'contribution_id' => $contribution['id'],
        'contribution_status' => 'Cancelled',
        'net_amount' => 100,
      ],
    ];
    $this->checkPaymentResult($order, $expectedResult);
    $participantPayment = $this->callAPISuccess('ParticipantPayment', 'getsingle', $params);
    $participant = $this->callAPISuccess('participant', 'get', ['id' => $participantPayment['participant_id']]);
    $this->assertEquals($participant['values'][$participant['id']]['participant_status'], 'Cancelled');
    $this->callAPISuccess('Contribution', 'Delete', [
      'id' => $contribution['id'],
    ]);
  }

  /**
   * Test an exception is thrown if line items do not add up to total_amount, no tax.
   */
  public function testCreateOrderIfTotalAmountDoesNotMatchLineItemsAmountsIfNoTaxSupplied() {
    $params = [
      'contact_id' => $this->_individualId,
      'receive_date' => '2018-01-01',
      'total_amount' => 50,
      'financial_type_id' => $this->_financialTypeId,
      'contribution_status_id' => 1,
      'line_items' => [
        0 => [
          'line_item' => [
            '0' => [
              'price_field_id' => 1,
              'price_field_value_id' => 1,
              'label' => 'Test 1',
              'field_title' => 'Test 1',
              'qty' => 1,
              'unit_price' => 40,
              'line_total' => 40,
              'financial_type_id' => 1,
              'entity_table' => 'civicrm_contribution',
            ],
          ],
        ],
      ],
    ];

    $this->callAPIFailure('Order', 'create', $params, 'Line item total doesn\'t match with total amount');
  }

  /**
   * Test an exception is thrown if line items do not add up to total_amount, with tax.
   */
  public function testCreateOrderIfTotalAmountDoesNotMatchLineItemsAmountsIfTaxSupplied() {
    $params = [
      'contact_id' => $this->_individualId,
      'receive_date' => '2018-01-01',
      'total_amount' => 50,
      'financial_type_id' => $this->_financialTypeId,
      'contribution_status_id' => 1,
      'tax_amount' => 15,
      'line_items' => [
        0 => [
          'line_item' => [
            '0' => [
              'price_field_id' => 1,
              'price_field_value_id' => 1,
              'label' => 'Test 1',
              'field_title' => 'Test 1',
              'qty' => 1,
              'unit_price' => 30,
              'line_total' => 30,
              'financial_type_id' => 1,
              'entity_table' => 'civicrm_contribution',
              'tax_amount' => 15,
            ],
          ],
        ],
      ],
    ];

    $this->callAPIFailure('Order', 'create', $params, 'Line item total doesn\'t match with total amount.');
  }

  public function testCreateOrderIfTotalAmountDoesMatchLineItemsAmountsAndTaxSupplied() {
    $params = [
      'contact_id' => $this->_individualId,
      'receive_date' => '2018-01-01',
      'total_amount' => 50,
      'financial_type_id' => $this->_financialTypeId,
      'contribution_status_id' => 1,
      'tax_amount' => 15,
      'line_items' => [
        0 => [
          'line_item' => [
            '0' => [
              'price_field_id' => 1,
              'price_field_value_id' => 1,
              'label' => 'Test 1',
              'field_title' => 'Test 1',
              'qty' => 1,
              'unit_price' => 35,
              'line_total' => 35,
              'financial_type_id' => 1,
              'entity_table' => 'civicrm_contribution',
              'tax_amount' => 15,
            ],
          ],
        ],
      ],
    ];

    $order = $this->callAPISuccess('Order', 'create', $params);
    $this->assertEquals(1, $order['count']);
  }

}
