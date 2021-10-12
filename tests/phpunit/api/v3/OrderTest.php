<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

use Civi\Api4\Contribution;
use Civi\Api4\FinancialItem;

/**
 *  Test APIv3 civicrm_contribute_* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Contribution
 * @group headless
 */
class api_v3_OrderTest extends CiviUnitTestCase {

  use CRMTraits_Financial_TaxTrait;

  /**
   * Should financials be checked after the test but before tear down.
   *
   * Ideally all tests (or at least all that call any financial api calls ) should do this but there
   * are some test data issues and some real bugs currently blocking.
   *
   * @var bool
   */
  protected $isValidateFinancialsOnPostAssert = TRUE;

  protected $_individualId;

  protected $_financialTypeId = 1;

  public $debug = 0;

  /**
   * Setup function.
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function setUp(): void {
    parent::setUp();

    $this->_apiversion = 3;
    $this->_individualId = $this->individualCreate();
  }

  /**
   * Clean up after each test.
   *
   * @throws \CRM_Core_Exception
   */
  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    $this->quickCleanup(['civicrm_uf_match']);
  }

  /**
   * Test Get order api.
   *
   * @throws \CRM_Core_Exception
   */
  public function testGetOrder(): void {
    $contribution = $this->addOrder(FALSE, 100);

    $params = ['contribution_id' => $contribution['id']];

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
   *
   * @throws \CRM_Core_Exception
   */
  public function testGetOrderParticipant(): void {
    $this->addOrder(FALSE, 100);
    $contribution = $this->createPartiallyPaidParticipantOrder();

    $params = [
      'contribution_id' => $contribution['id'],
    ];

    $order = $this->callAPISuccess('Order', 'get', $params);

    $this->assertCount(2, $order['values'][$contribution['id']]['line_items']);
    $this->callAPISuccess('Contribution', 'Delete', [
      'id' => $contribution['id'],
    ]);
  }

  /**
   * Function to assert db values.
   */
  public function checkPaymentResult($results, $expectedResult, $lineItems = NULL): void {
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
   * @throws \CRM_Core_Exception
   */
  public function addOrder(bool $isPriceSet, float $amount = 300.00, array $extraParams = []): array {
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
   *
   * @throws \CRM_Core_Exception
   */
  public function testAddOrder(): void {
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
   * @throws \API_Exception
   */
  public function testAddOrderForMembership(): void {
    $membershipType = $this->membershipTypeCreate();
    $membershipType1 = $this->membershipTypeCreate();
    $membershipType = $membershipTypes = [$membershipType, $membershipType1];
    $p = [
      'contact_id' => $this->_individualId,
      'receive_date' => '2010-01-20',
      'financial_type_id' => 'Event Fee',
      'contribution_status_id' => 'Pending',
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
      ],
    ];
    $order = $this->callAPIAndDocument('Order', 'create', $p, __FUNCTION__, __FILE__);
    $params = [
      'contribution_id' => $order['id'],
    ];
    $order = $this->callAPISuccess('Order', 'get', $params);
    $expectedResult = [
      $order['id'] => [
        'total_amount' => 200,
        'contribution_id' => $order['id'],
        'contribution_status' => 'Pending Label**',
        'net_amount' => 200,
      ],
    ];
    $this->checkPaymentResult($order, $expectedResult);
    $membershipPayment = $this->callAPISuccessGetSingle('MembershipPayment', $params);

    $this->callAPISuccessGetSingle('Membership', ['id' => $membershipPayment['id']]);
    $this->callAPISuccess('Contribution', 'Delete', ['id' => $order['id']]);
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
        'status_id' => 'Pending',
      ],
    ];
    $p['total_amount'] = 300;
    $order = $this->callAPISuccess('Order', 'create', $p);
    $expectedResult = [
      $order['id'] => [
        'total_amount' => 300,
        'contribution_status' => 'Pending Label**',
        'net_amount' => 300,
      ],
    ];
    $paymentMembership = [
      'contribution_id' => $order['id'],
    ];
    $order = $this->callAPISuccess('Order', 'get', $paymentMembership);
    $this->checkPaymentResult($order, $expectedResult);
    $this->callAPISuccessGetCount('MembershipPayment', $paymentMembership, 2);
    $this->callAPISuccess('Payment', 'create', [
      'contribution_id' => $order['id'],
      'payment_instrument_id' => 'Check',
      'total_amount' => 300,
    ]);
    foreach (FinancialItem::get(FALSE)
      ->addJoin(
        'LineItem AS line_item',
        'INNER',
        NULL,
        ['entity_table', '=', '"civicrm_line_item"'],
        ['entity_id', '=', 'line_item.id'],
        ['line_item.contribution_id', '=', $order['id']]
      )
      ->addSelect('status_id')->execute() as $item) {
      $this->assertEquals('Paid', CRM_Core_PseudoConstant::getName('CRM_Financial_BAO_FinancialItem', 'status_id', $item['status_id']));
    }
    $this->callAPISuccess('Contribution', 'Delete', [
      'id' => $order['id'],
    ]);
  }

  /**
   * Test create order api for membership, allowing BAO to calculate dates.
   *
   * @throws \CRM_Core_Exception
   */
  public function testAddOrderForMembershipAutoDates(): void {
    $membershipType = $this->membershipTypeCreate();
    $p = [
      'contact_id' => $this->_individualId,
      'financial_type_id' => 'Member Dues',
      'contribution_status_id' => 'Pending',
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
        'membership_type_id' => $membershipType,
      ];
    }
    $p['line_items'][] = [
      'line_item' => [array_pop($lineItems)],
      'params' => [
        'contact_id' => $this->_individualId,
        'membership_type_id' => $membershipType,
        'source' => 'Payment',
      ],
    ];

    // Create expected dates immediately before order creation to minimise chance of day changing over.
    $expectedStart = date('Y-m-d');
    $expectedEnd = date('Y-m-d', strtotime('+ 1 year - 1 day'));

    $order = $this->callAPISuccess('Order', 'create', $p, __FUNCTION__, __FILE__);

    $params = [
      'id' => $order['id'],
    ];
    $order = $this->callAPISuccess('Order', 'get', $params);
    $expectedResult = [
      $order['id'] => [
        'total_amount' => 200,
        'contribution_id' => $order['id'],
        'contribution_status' => 'Pending Label**',
        'net_amount' => 200,
      ],
    ];
    $this->checkPaymentResult($order, $expectedResult);

    $membershipPayment = $this->callAPISuccessGetSingle('MembershipPayment', $params);
    $membership = $this->callAPISuccessGetSingle('Membership', ['id' => $membershipPayment['id']]);

    // Check membership dates automatically added for legacy skipStatusCal
    $this->assertEquals($expectedStart, $membership['join_date'] ?? NULL, 'join_date is calculated');
    $this->assertEquals($expectedStart, $membership['start_date'] ?? NULL, 'start_date is calculated');
    $this->assertEquals($expectedEnd, $membership['end_date'] ?? NULL, 'end_date is calculated');

    $this->callAPISuccess('Contribution', 'Delete', ['id' => $order['id']]);
  }

  /**
   * Test create order api for membership, allowing BAO to calculate dates; skipStatusCal is set but should be ignored.
   *
   * @throws \CRM_Core_Exception
   */
  public function testAddOrderForMembershipAutoDatesSkipStatusCal(): void {
    $membershipType = $this->membershipTypeCreate();
    $p = [
      'contact_id' => $this->_individualId,
      'financial_type_id' => 'Member Dues',
      'contribution_status_id' => 'Pending',
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
        'membership_type_id' => $membershipType,
      ];
    }
    $p['line_items'][] = [
      'line_item' => [array_pop($lineItems)],
      'params' => [
        'contact_id' => $this->_individualId,
        'membership_type_id' => $membershipType,
        'source' => 'Payment',
        'skipStatusCal' => 1,
      ],
    ];

    // Create expected dates immediately before order creation to minimise chance of day changing over.
    $expectedStart = date('Y-m-d');
    $expectedEnd = date('Y-m-d', strtotime('+ 1 year - 1 day'));

    $order = $this->callAPISuccess('Order', 'create', $p, __FUNCTION__, __FILE__);

    $params = [
      'id' => $order['id'],
    ];
    $order = $this->callAPISuccess('Order', 'get', $params);
    $expectedResult = [
      $order['id'] => [
        'total_amount' => 200,
        'contribution_id' => $order['id'],
        'contribution_status' => 'Pending Label**',
        'net_amount' => 200,
      ],
    ];
    $this->checkPaymentResult($order, $expectedResult);

    $membershipPayment = $this->callAPISuccessGetSingle('MembershipPayment', $params);
    $membership = $this->callAPISuccessGetSingle('Membership', ['id' => $membershipPayment['id']]);

    // Check membership dates automatically added for legacy skipStatusCal
    $this->assertEquals($expectedStart, $membership['join_date'] ?? NULL, 'join_date is calculated');
    $this->assertEquals($expectedStart, $membership['start_date'] ?? NULL, 'start_date is calculated');
    $this->assertEquals($expectedEnd, $membership['end_date'] ?? NULL, 'end_date is calculated');

    $this->callAPISuccess('Contribution', 'Delete', ['id' => $order['id']]);
  }

  /**
   * Test create order api for participant
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testAddOrderForParticipant(): void {
    $event = $this->eventCreate();
    $this->_eventId = $event['id'];
    $p = [
      'contact_id' => $this->_individualId,
      'receive_date' => '2010-01-20',
      'financial_type_id' => $this->_financialTypeId,
      'contribution_status_id' => 'Pending',
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
        'role_id' => 1,
        'register_date' => '2007-07-21 00:00:00',
        'source' => 'Online Event Registration: API Testing',
      ],
    ];

    $order = $this->callAPIAndDocument('order', 'create', $p, __FUNCTION__, __FILE__, 'Create order for participant', 'CreateOrderParticipant');
    $params = ['contribution_id' => $order['id']];
    $order = $this->callAPISuccess('order', 'get', $params);
    $expectedResult = [
      $order['id'] => [
        'total_amount' => 300,
        'contribution_id' => $order['id'],
        'contribution_status' => 'Pending Label**',
        'net_amount' => 300,
      ],
    ];
    $this->checkPaymentResult($order, $expectedResult);
    $paymentParticipant = $this->callAPISuccessGetSingle('ParticipantPayment', ['contribution_id' => $order['id']]);
    $participant = $this->callAPISuccessGetSingle('Participant', ['participant_id' => $paymentParticipant['participant_id']]);
    $this->assertEquals('Pending (incomplete transaction)', $participant['participant_status']);
    $this->callAPISuccess('Contribution', 'Delete', [
      'id' => $order['id'],
    ]);

    // Enable the "Pending from approval" status which is not enabled by default
    $pendingFromApprovalParticipantStatus = civicrm_api3('ParticipantStatusType', 'getsingle', [
      'name' => "Pending from approval",
    ]);
    civicrm_api3('ParticipantStatusType', 'create', [
      'id' => $pendingFromApprovalParticipantStatus['id'],
      'name' => "Pending from approval",
      'is_active' => 1,
    ]);

    $p['line_items'][] = [
      'line_item' => $lineItems,
      'params' => [
        'contact_id' => $this->individualCreate(),
        'event_id' => $this->_eventId,
        'role_id' => 1,
        'register_date' => '2007-07-21 00:00:00',
        'source' => 'Online Event Registration: API Testing',
        'participant_status_id' => 'Pending from approval',
      ],
    ];

    $order = $this->callAPISuccess('order', 'create', $p);
    $expectedResult = [
      $order['id'] => [
        'total_amount' => 600,
        'contribution_status' => 'Pending Label**',
        'net_amount' => 600,
      ],
    ];
    $orderParams = [
      'contribution_id' => $order['id'],
    ];
    $order = $this->callAPISuccess('order', 'get', $orderParams);
    $this->checkPaymentResult($order, $expectedResult);
    $paymentParticipant = $this->callAPISuccess('ParticipantPayment', 'get', $orderParams)['values'];
    $this->assertCount(2, $paymentParticipant, 'Expected two participant payments');
    $participant = $this->callAPISuccessGetSingle('Participant', ['participant_id' => end($paymentParticipant)['participant_id']]);
    $this->assertEquals('Pending from approval', $participant['participant_status']);
    $this->callAPISuccess('Contribution', 'Delete', [
      'id' => $order['id'],
    ]);
  }

  /**
   * Test create order api with line items
   *
   * @throws \CRM_Core_Exception
   */
  public function testAddOrderWithLineItems(): void {
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
    $this->assertEquals(300, $eft['values'][$eft['id']]['amount']);
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
   *
   * @throws \CRM_Core_Exception
   */
  public function testDeleteOrder(): void {
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
   * Test order api treats amount as inclusive when line items not set.
   *
   * @throws \CRM_Core_Exception
   */
  public function testAPIOrderTaxSpecified(): void {
    $this->enableTaxAndInvoicing();
    $this->createFinancialTypeWithSalesTax();
    $order = $this->callAPISuccess('Order', 'create', [
      'total_amount' => 105,
      'financial_type_id' => 'Test taxable financial Type',
      'contact_id' => $this->individualCreate(),
      'sequential' => 1,
      'tax_amount' => 5,
    ])['values'][0];
    $this->assertEquals(105, $order['total_amount']);
    $this->assertEquals(5, $order['tax_amount']);
  }

  /**
   * Test order api treats amount as inclusive when line items not set.
   *
   * @throws \CRM_Core_Exception
   */
  public function testAPIOrderTaxNotSpecified(): void {
    $this->enableTaxAndInvoicing();
    $this->createFinancialTypeWithSalesTax();
    $order = $this->callAPISuccess('Order', 'create', [
      'total_amount' => 105,
      'financial_type_id' => 'Test taxable financial Type',
      'contact_id' => $this->individualCreate(),
      'sequential' => 1,
    ])['values'][0];
    $this->assertEquals(105, $order['total_amount']);
    $this->assertEquals(5, $order['tax_amount']);
  }

  /**
   * Test order api treats amount as inclusive when line items not set.
   *
   * @throws \CRM_Core_Exception
   */
  public function testAPIContributionTaxSpecified(): void {
    $this->enableTaxAndInvoicing();
    $this->createFinancialTypeWithSalesTax();

    $order = $this->callAPISuccess('Contribution', 'create', [
      'total_amount' => 105,
      'financial_type_id' => 'Test taxable financial Type',
      'contact_id' => $this->individualCreate(),
      'sequential' => 1,
      'tax_amount' => 5,
    ])['values'][0];
    $this->assertEquals(105, $order['total_amount']);
    $this->assertEquals(5, $order['tax_amount']);
  }

  /**
   * Test cancel order api
   *
   * @throws \CRM_Core_Exception
   */
  public function testCancelOrder(): void {
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
   * Test an exception is thrown if line items do not add up to total_amount, no tax.
   */
  public function testCreateOrderIfTotalAmountDoesNotMatchLineItemsAmountsIfNoTaxSupplied(): void {
    $params = [
      'contact_id' => $this->_individualId,
      'receive_date' => '2018-01-01',
      'total_amount' => 50,
      'financial_type_id' => $this->_financialTypeId,
      'contribution_status_id' => 'Pending',
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

    $this->callAPIFailure('Order', 'create', $params, "Line item total doesn't match total amount");
  }

  /**
   * Test an exception is thrown if line items do not add up to total_amount, with tax.
   */
  public function testCreateOrderIfTotalAmountDoesNotMatchLineItemsAmountsIfTaxSupplied(): void {
    $params = [
      'contact_id' => $this->_individualId,
      'receive_date' => '2018-01-01',
      'total_amount' => 50,
      'financial_type_id' => $this->_financialTypeId,
      'contribution_status_id' => 'Pending',
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

    $this->callAPIFailure('Order', 'create', $params, "Line item total doesn't match total amount.");
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function testCreateOrderIfTotalAmountDoesMatchLineItemsAmountsAndTaxSupplied(): void {
    $this->enableTaxAndInvoicing();
    $this->createFinancialTypeWithSalesTax();
    $params = [
      'contact_id' => $this->_individualId,
      'receive_date' => '2018-01-01',
      'total_amount' => 36.75,
      'financial_type_id' => $this->_financialTypeId,
      'contribution_status_id' => 'Pending',
      'tax_amount' => 1.75,
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
              'financial_type_id' => $this->ids['FinancialType']['taxable'],
              'entity_table' => 'civicrm_contribution',
              'tax_amount' => 1.75,
            ],
          ],
        ],
      ],
    ];

    $order = $this->callAPISuccess('Order', 'create', $params);
    $this->assertEquals(1, $order['count']);
  }

  /**
   * Test that a contribution can be added in pending mode with a chained
   * payment.
   *
   * We have just deprecated creating an order with a status other than
   * pending. It makes sense to support adding a payment straight away by
   * chaining.
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreateWithChainedPayment(): void {
    $contributionID = $this->callAPISuccess('Order', 'create', ['contact_id' => $this->_individualId, 'total_amount' => 5, 'financial_type_id' => 2, 'contribution_status_id' => 'Pending', 'api.Payment.create' => ['total_amount' => 5]])['id'];
    $this->assertEquals('Completed', $this->callAPISuccessGetValue('Contribution', ['id' => $contributionID, 'return' => 'contribution_status']));
  }

  /**
   * Test creating an order with a mixture of taxable & non-taxable.
   *
   * @throws \CiviCRM_API3_Exception
   * @throws \CRM_Core_Exception
   * @throws \API_Exception
   */
  public function testOrderWithMixedTax(): void {
    $this->enableTaxAndInvoicing();
    $this->createFinancialTypeWithSalesTax('woo', [], ['tax_rate' => 19.3791]);
    $membershipTypeID = $this->membershipTypeCreate();
    $contactID = $this->individualCreate();
    $this->callAPISuccess('Order', 'create', [
      'contact_id' => $contactID,
      'financial_type_id' => $this->ids['FinancialType']['woo'],
      'payment_instrument_id' => 4,
      'trxn_id' => 'WooCommerce Order - 1859',
      'invoice_id' => '1859_woocommerce',
      'receive_date' => '2021-05-05 23:24:02',
      'contribution_status_id' => 'Pending',
      'source' => 'Shop',
      'note' => 'Fundraiser Dinner Ticket x 1, Student Membership x 1',
      'line_items' => [
        [
          'line_item' => [
            [
              'price_field_id' => 1,
              'unit_price' => 50.00,
              'qty' => 1,
              'line_total' => 50.00,
              'tax_amount' => 9.69,
              'label' => 'Fundraiser Dinner Ticket',
              'financial_type_id' => $this->ids['FinancialType']['woo'],
            ],
          ],
        ],
        [
          'line_item' => [
            [
              'price_field_id' => 1,
              'unit_price' => 50.00,
              'qty' => 1,
              'line_total' => 50.00,
              'tax_amount' => 0.00,
              'label' => 'Student Membership',
              'financial_type_id' => 2,
              'entity_table' => 'civicrm_membership',
              'membership_type_id' => $membershipTypeID,
            ],
          ],
          'params' => [
            'membership_type_id' => $membershipTypeID,
            'source' => 'Shop',
            'contact_id' => $contactID,
            'skipStatusCal' => 1,
            'status_id' => 'Pending',
          ],
        ],
      ],
      'tax_amount' => 9.69,
    ]);

    $contribution = Contribution::get(FALSE)
      ->addWhere('trxn_id', '=', 'WooCommerce Order - 1859')
      ->setSelect(['tax_amount', 'total_amount'])->execute()->first();

    $this->assertEquals('9.69', $contribution['tax_amount']);
    $this->assertEquals('109.69', $contribution['total_amount']);
    Contribution::update()->setValues([
      'source' => 'new one',
      'financial_type_id' => $this->ids['FinancialType']['woo'],
    ])->addWhere('id', '=', $contribution['id'])->execute();

    $contribution = Contribution::get(FALSE)
      ->addWhere('trxn_id', '=', 'WooCommerce Order - 1859')
      ->setSelect(['tax_amount', 'total_amount'])->execute()->first();

    $this->assertEquals('9.69', $contribution['tax_amount']);
    $this->assertEquals('109.69', $contribution['total_amount']);
  }

}
