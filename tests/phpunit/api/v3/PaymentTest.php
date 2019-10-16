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
class api_v3_PaymentTest extends CiviUnitTestCase {

  protected $_individualId;

  protected $_financialTypeId = 1;

  protected $_apiversion;

  public $debug = 0;

  /**
   * Setup function.
   */
  public function setUp() {
    parent::setUp();

    $this->_apiversion = 3;
    $this->_individualId = $this->individualCreate();
    CRM_Core_Config::singleton()->userPermissionClass->permissions = [];
  }

  /**
   * Clean up after each test.
   *
   * @throws \Exception
   */
  public function tearDown() {
    $this->quickCleanUpFinancialEntities();
    $this->quickCleanup(['civicrm_uf_match']);
    unset(CRM_Core_Config::singleton()->userPermissionClass->permissions);
    parent::tearDown();
  }

  /**
   * Test Get Payment api.
   */
  public function testGetPayment() {
    $p = [
      'contact_id' => $this->_individualId,
      'receive_date' => '2010-01-20',
      'total_amount' => 100.00,
      'financial_type_id' => $this->_financialTypeId,
      'trxn_id' => 23456,
      'contribution_status_id' => 1,
    ];
    $contribution = $this->callAPISuccess('contribution', 'create', $p);

    $params = [
      'contribution_id' => $contribution['id'],
      'check_permissions' => TRUE,
    ];
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviCRM', 'administer CiviCRM'];
    $payment = $this->callAPIFailure('payment', 'get', $params, 'API permission check failed for Payment/get call; insufficient permission: require access CiviCRM and access CiviContribute');

    array_push(CRM_Core_Config::singleton()->userPermissionClass->permissions, 'access CiviContribute');
    $payment = $this->callAPISuccess('payment', 'get', $params);

    $payment = $this->callAPIAndDocument('payment', 'get', $params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $payment['count']);

    $expectedResult = [
      $contribution['id'] => [
        'total_amount' => 100,
        'trxn_id' => 23456,
        'trxn_date' => '2010-01-20 00:00:00',
        'contribution_id' => $contribution['id'],
        'is_payment' => 1,
      ],
    ];
    $this->checkPaymentResult($payment, $expectedResult);
    $this->callAPISuccess('Contribution', 'Delete', [
      'id' => $contribution['id'],
    ]);
  }

  /**
   * Retrieve Payment using trxn_id.
   */
  public function testGetPaymentWithTrxnID() {
    $this->_individualId2 = $this->individualCreate();
    $params1 = [
      'contact_id' => $this->_individualId,
      'trxn_id' => 111111,
      'total_amount' => 10,
    ];
    $contributionID1 = $this->contributionCreate($params1);

    $params2 = [
      'contact_id' => $this->_individualId2,
      'trxn_id' => 222222,
      'total_amount' => 20,
    ];
    $contributionID2 = $this->contributionCreate($params2);

    $paymentParams = ['trxn_id' => 111111];
    $payment = $this->callAPISuccess('payment', 'get', $paymentParams);
    $expectedResult = [
      $payment['id'] => [
        'total_amount' => 10,
        'trxn_id' => 111111,
        'status_id' => 1,
        'is_payment' => 1,
        'contribution_id' => $contributionID1,
      ],
    ];
    $this->checkPaymentResult($payment, $expectedResult);

    $paymentParams = ['trxn_id' => 222222];
    $payment = $this->callAPISuccess('payment', 'get', $paymentParams);
    $expectedResult = [
      $payment['id'] => [
        'total_amount' => 20,
        'trxn_id' => 222222,
        'status_id' => 1,
        'is_payment' => 1,
        'contribution_id' => $contributionID2,
      ],
    ];
    $this->checkPaymentResult($payment, $expectedResult);
  }

  /**
   * Test email receipt for partial payment.
   */
  public function testPaymentEmailReceipt() {
    $mut = new CiviMailUtils($this);
    list($lineItems, $contribution) = $this->createParticipantWithContribution();
    $event = $this->callAPISuccess('Event', 'get', []);
    $this->addLocationToEvent($event['id']);
    $params = [
      'contribution_id' => $contribution['id'],
      'total_amount' => 50,
      'check_number' => '345',
      'trxn_date' => '2018-08-13 17:57:56',
    ];
    $payment = $this->callAPISuccess('payment', 'create', $params);
    $this->checkPaymentResult($payment, [
      $payment['id'] => [
        'from_financial_account_id' => 7,
        'to_financial_account_id' => 6,
        'total_amount' => 50,
        'status_id' => 1,
        'is_payment' => 1,
      ],
    ]);

    $this->callAPISuccess('Payment', 'sendconfirmation', ['id' => $payment['id']]);
    $mut->assertSubjects(['Payment Receipt - Annual CiviCRM meet']);
    $mut->checkMailLog([
      'Dear Anthony,',
      'Total Fees: $ 300.00',
      'This Payment Amount: $ 50.00',
      //150 was paid in the 1st payment.
      'Balance Owed: $ 100.00',
      'Event Information and Location',
      'Paid By: Check',
      'Check Number: 345',
      'Transaction Date: August 13th, 2018  5:57 PM',
      'event place',
      'streety street',
    ]);
    $mut->stop();
    $mut->clearMessages();
  }

  /**
   * Test email receipt for partial payment.
   *
   * @throws \CRM_Core_Exception
   */
  public function testPaymentEmailReceiptFullyPaid() {
    $mut = new CiviMailUtils($this);
    list($lineItems, $contribution) = $this->createParticipantWithContribution();

    $params = [
      'contribution_id' => $contribution['id'],
      'total_amount' => 150,
    ];
    $payment = $this->callAPISuccess('payment', 'create', $params);

    $this->callAPISuccess('Payment', 'sendconfirmation', ['id' => $payment['id']]);
    $mut->assertSubjects(['Payment Receipt - Annual CiviCRM meet', 'Registration Confirmation - Annual CiviCRM meet']);
    $mut->checkMailLog([
      'Dear Anthony,',
      'A payment has been received.',
      'Total Fees: $ 300.00',
      'This Payment Amount: $ 150.00',
      'Balance Owed: $ 0.00',
      'Thank you for completing payment.',
    ]);
    $mut->stop();
    $mut->clearMessages();
  }

  /**
   * Test email receipt for partial payment.
   *
   * @dataProvider getThousandSeparators
   *
   * @param string $thousandSeparator
   */
  public function testRefundEmailReceipt($thousandSeparator) {
    $this->setCurrencySeparators($thousandSeparator);
    $decimalSeparator = ($thousandSeparator === ',' ? '.' : ',');
    $mut = new CiviMailUtils($this);
    list($lineItems, $contribution) = $this->createParticipantWithContribution();
    $this->callAPISuccess('payment', 'create', [
      'contribution_id' => $contribution['id'],
      'total_amount' => 50,
      'check_number' => '345',
      'trxn_date' => '2018-08-13 17:57:56',
    ]);

    $payment = $this->callAPISuccess('payment', 'create', [
      'contribution_id' => $contribution['id'],
      'total_amount' => -30,
      'trxn_date' => '2018-11-13 12:01:56',
      'sequential' => TRUE,
    ])['values'][0];

    $expected = [
      'from_financial_account_id' => 7,
      'to_financial_account_id' => 6,
      'total_amount' => -30,
      'status_id' => CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_FinancialTrxn', 'status_id', 'Refunded'),
      'is_payment' => 1,
    ];
    foreach ($expected as $key => $value) {
      $this->assertEquals($expected[$key], $payment[$key], 'mismatch on key ' . $key);
    }

    $this->callAPISuccess('Payment', 'sendconfirmation', ['id' => $payment['id']]);
    $mut->assertSubjects(['Refund Notification - Annual CiviCRM meet']);
    $mut->checkMailLog([
      'Dear Anthony,',
      'A refund has been issued based on changes in your registration selections.',
      'Total Fees: $ 300' . $decimalSeparator . '00',
      'Refund Amount: $ -30' . $decimalSeparator . '00',
      'Event Information and Location',
      'Paid By: Check',
      'Transaction Date: November 13th, 2018 12:01 PM',
      'You Paid: $ 170' . $decimalSeparator . '00',
    ]);
    $mut->stop();
    $mut->clearMessages();
  }

  /**
   * Test create payment api with no line item in params
   */
  public function testCreatePaymentNoLineItems() {
    list($lineItems, $contribution) = $this->createParticipantWithContribution();

    //Create partial payment
    $params = [
      'contribution_id' => $contribution['id'],
      'total_amount' => 50,
    ];
    $payment = $this->callAPIAndDocument('payment', 'create', $params, __FUNCTION__, __FILE__);
    $expectedResult = [
      $payment['id'] => [
        'from_financial_account_id' => 7,
        'to_financial_account_id' => 6,
        'total_amount' => 50,
        'status_id' => 1,
        'is_payment' => 1,
      ],
    ];
    $this->checkPaymentResult($payment, $expectedResult);

    // Check entity financial trxn created properly
    $params = [
      'entity_id' => $contribution['id'],
      'entity_table' => 'civicrm_contribution',
      'financial_trxn_id' => $payment['id'],
    ];

    $eft = $this->callAPISuccess('EntityFinancialTrxn', 'get', $params);

    $this->assertEquals($eft['values'][$eft['id']]['amount'], 50);

    $params = [
      'entity_table' => 'civicrm_financial_item',
      'financial_trxn_id' => $payment['id'],
    ];
    $eft = $this->callAPISuccess('EntityFinancialTrxn', 'get', $params);
    $amounts = [33.33, 16.67];
    foreach ($eft['values'] as $value) {
      $this->assertEquals($value['amount'], array_pop($amounts));
    }

    // Now create payment to complete total amount of contribution
    $params = [
      'contribution_id' => $contribution['id'],
      'total_amount' => 100,
    ];
    $payment = $this->callAPISuccess('payment', 'create', $params);
    $expectedResult = [
      $payment['id'] => [
        'from_financial_account_id' => 7,
        'to_financial_account_id' => 6,
        'total_amount' => 100,
        'status_id' => 1,
        'is_payment' => 1,
      ],
    ];
    $this->checkPaymentResult($payment, $expectedResult);
    $params = [
      'entity_table' => 'civicrm_financial_item',
      'financial_trxn_id' => $payment['id'],
    ];
    $eft = $this->callAPISuccess('EntityFinancialTrxn', 'get', $params);
    $amounts = [66.67, 33.33];
    foreach ($eft['values'] as $value) {
      $this->assertEquals($value['amount'], array_pop($amounts));
    }
    // Check contribution for completed status
    $contribution = $this->callAPISuccess('contribution', 'get', ['id' => $contribution['id']]);

    $this->assertEquals($contribution['values'][$contribution['id']]['contribution_status'], 'Completed');
    $this->assertEquals($contribution['values'][$contribution['id']]['total_amount'], 300.00);
    $paymentParticipant = [
      'contribution_id' => $contribution['id'],
    ];
    $participantPayment = $this->callAPISuccess('ParticipantPayment', 'getsingle', $paymentParticipant);
    $participant = $this->callAPISuccess('participant', 'get', ['id' => $participantPayment['participant_id']]);
    $this->assertEquals($participant['values'][$participant['id']]['participant_status'], 'Registered');
    $this->callAPISuccess('Contribution', 'Delete', [
      'id' => $contribution['id'],
    ]);
  }

  /**
   * Function to assert db values
   */
  public function checkPaymentResult($payment, $expectedResult) {
    foreach ($expectedResult[$payment['id']] as $key => $value) {
      $this->assertEquals($payment['values'][$payment['id']][$key], $value, 'mismatch on ' . $key);
    }
  }

  /**
   * Test create payment api with line item in params
   */
  public function testCreatePaymentLineItems() {
    list($lineItems, $contribution) = $this->createParticipantWithContribution();
    $lineItems = $this->callAPISuccess('LineItem', 'get', ['contribution_id' => $contribution['id']]);

    //Create partial payment by passing line item array is params
    $params = [
      'contribution_id' => $contribution['id'],
      'total_amount' => 50,
    ];
    $amounts = [40, 10];
    foreach ($lineItems['values'] as $id => $ignore) {
      $params['line_item'][] = [$id => array_pop($amounts)];
    }
    $payment = $this->callAPIAndDocument('payment', 'create', $params, __FUNCTION__, __FILE__, 'Payment with line item', 'CreatePaymentWithLineItems');
    $expectedResult = [
      $payment['id'] => [
        'from_financial_account_id' => 7,
        'to_financial_account_id' => 6,
        'total_amount' => 50,
        'status_id' => 1,
        'is_payment' => 1,
      ],
    ];
    $this->checkPaymentResult($payment, $expectedResult);

    // Check entity financial trxn created properly
    $params = [
      'entity_id' => $contribution['id'],
      'entity_table' => 'civicrm_contribution',
      'financial_trxn_id' => $payment['id'],
    ];

    $eft = $this->callAPISuccess('EntityFinancialTrxn', 'get', $params);

    $this->assertEquals($eft['values'][$eft['id']]['amount'], 50);

    $params = [
      'entity_table' => 'civicrm_financial_item',
      'financial_trxn_id' => $payment['id'],
    ];
    $eft = $this->callAPISuccess('EntityFinancialTrxn', 'get', $params);
    $amounts = [40, 10];
    foreach ($eft['values'] as $value) {
      $this->assertEquals($value['amount'], array_pop($amounts));
    }

    // Now create payment to complete total amount of contribution
    $params = [
      'contribution_id' => $contribution['id'],
      'total_amount' => 100,
    ];
    $amounts = [80, 20];
    foreach ($lineItems['values'] as $id => $ignore) {
      $params['line_item'][] = [$id => array_pop($amounts)];
    }
    $payment = $this->callAPISuccess('payment', 'create', $params);
    $expectedResult = [
      $payment['id'] => [
        'from_financial_account_id' => 7,
        'to_financial_account_id' => 6,
        'total_amount' => 100,
        'status_id' => 1,
        'is_payment' => 1,
      ],
    ];
    $this->checkPaymentResult($payment, $expectedResult);
    $params = [
      'entity_table' => 'civicrm_financial_item',
      'financial_trxn_id' => $payment['id'],
    ];
    $eft = $this->callAPISuccess('EntityFinancialTrxn', 'get', $params);
    $amounts = [80, 20];
    foreach ($eft['values'] as $value) {
      $this->assertEquals($value['amount'], array_pop($amounts));
    }
    // Check contribution for completed status
    $contribution = $this->callAPISuccess('contribution', 'get', ['id' => $contribution['id']]);

    $this->assertEquals($contribution['values'][$contribution['id']]['contribution_status'], 'Completed');
    $this->assertEquals($contribution['values'][$contribution['id']]['total_amount'], 300.00);
    $paymentParticipant = [
      'contribution_id' => $contribution['id'],
    ];
    $participantPayment = $this->callAPISuccess('ParticipantPayment', 'getsingle', $paymentParticipant);
    $participant = $this->callAPISuccess('participant', 'get', ['id' => $participantPayment['participant_id']]);
    $this->assertEquals($participant['values'][$participant['id']]['participant_status'], 'Registered');
    $this->callAPISuccess('Contribution', 'Delete', [
      'id' => $contribution['id'],
    ]);
  }

  /**
   * Test cancel payment api
   */
  public function testCancelPayment() {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['administer CiviCRM', 'access CiviContribute'];
    list($lineItems, $contribution) = $this->createParticipantWithContribution();

    $params = [
      'contribution_id' => $contribution['id'],
    ];

    $payment = $this->callAPISuccess('payment', 'get', $params);
    $this->assertEquals(1, $payment['count']);

    $cancelParams = [
      'id' => $payment['id'],
      'check_permissions' => TRUE,
    ];
    $payment = $this->callAPIFailure('payment', 'cancel', $cancelParams, 'API permission check failed for Payment/cancel call; insufficient permission: require access CiviCRM and access CiviContribute and edit contributions');

    array_push(CRM_Core_Config::singleton()->userPermissionClass->permissions, 'access CiviCRM', 'edit contributions');

    $this->callAPIAndDocument('payment', 'cancel', $cancelParams, __FUNCTION__, __FILE__);

    $payment = $this->callAPISuccess('payment', 'get', $params);
    $this->assertEquals(2, $payment['count']);
    $amounts = [-150.00, 150.00];
    foreach ($payment['values'] as $value) {
      $this->assertEquals($value['total_amount'], array_pop($amounts), 'Mismatch total amount');
    }

    $this->callAPISuccess('Contribution', 'Delete', [
      'id' => $contribution['id'],
    ]);
  }

  /**
   * Test delete payment api
   */
  public function testDeletePayment() {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['administer CiviCRM', 'access CiviContribute'];
    list($lineItems, $contribution) = $this->createParticipantWithContribution();

    $params = [
      'contribution_id' => $contribution['id'],
    ];

    $payment = $this->callAPISuccess('payment', 'get', $params);
    $this->assertEquals(1, $payment['count']);

    $deleteParams = [
      'id' => $payment['id'],
      'check_permissions' => TRUE,
    ];
    $payment = $this->callAPIFailure('payment', 'delete', $deleteParams, 'API permission check failed for Payment/delete call; insufficient permission: require access CiviCRM and access CiviContribute and delete in CiviContribute');

    array_push(CRM_Core_Config::singleton()->userPermissionClass->permissions, 'access CiviCRM', 'delete in CiviContribute');
    $this->callAPIAndDocument('payment', 'delete', $deleteParams, __FUNCTION__, __FILE__);

    $payment = $this->callAPISuccess('payment', 'get', $params);
    $this->assertEquals(0, $payment['count']);

    $this->callAPISuccess('Contribution', 'Delete', [
      'id' => $contribution['id'],
    ]);
  }

  /**
   * Test update payment api.
   *
   * 1) create a contribution for $300 with a partial payment of $150
   * - this results in 2 financial transactions. The accounts receivable transaction is linked
   *   via entity_financial_trxns to the 2 line items. The $150 payment is not linked to the line items
   *   so the line items are fully allocated even though they are only half paid.
   *
   * 2) add a payment of $50 -
   *  This payment transaction IS linked to the line items so $350 of the $300 in line items is allocated
   *  but $200 is paid
   *
   * 3) update that payment to be $100
   *  This results in a negative and a positive payment ($50 & $100) - the negative payment results in
   *  financial_items but the positive payment does not.
   *
   * The final result is we have
   * - 1 partly paid contribution of $300
   * -  payment financial_trxns totalling $250
   * - 1 Accounts receivable financial_trxn totalling $300
   * - 2 financial items totalling $300 linked to the Accounts receivable financial_trxn
   * - 6 entries in the civicrm_entity_financial_trxn linked to line items - totalling $450.
   * - 5 entries in the civicrm_entity_financial_trxn linked to contributions - totalling $550.
   */
  public function testUpdatePayment() {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['administer CiviCRM', 'access CiviContribute', 'edit contributions'];
    list($lineItems, $contribution) = $this->createParticipantWithContribution();

    //Create partial payment by passing line item array is params
    $params = [
      'contribution_id' => $contribution['id'],
      'total_amount' => 50,
    ];

    $payment = $this->callAPISuccess('payment', 'create', $params);
    $expectedResult = [
      $payment['id'] => [
        'from_financial_account_id' => 7,
        'to_financial_account_id' => 6,
        'total_amount' => 50,
        'status_id' => 1,
        'is_payment' => 1,
      ],
    ];
    $this->checkPaymentResult($payment, $expectedResult);

    $params = [
      'entity_table' => 'civicrm_financial_item',
      'financial_trxn_id' => $payment['id'],
    ];
    $eft = $this->callAPISuccess('EntityFinancialTrxn', 'get', $params);
    $amounts = [33.33, 16.67];
    foreach ($eft['values'] as $value) {
      $this->assertEquals($value['amount'], array_pop($amounts));
    }

    // update the amount for payment
    $params = [
      'contribution_id' => $contribution['id'],
      'total_amount' => 100,
      'id' => $payment['id'],
      'check_permissions' => TRUE,
    ];
    // @todo - move this permissions test to it's own test - it just confuses here.
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['administer CiviCRM', 'access CiviContribute'];
    $this->callAPIFailure('payment', 'create', $params, 'API permission check failed for Payment/create call; insufficient permission: require access CiviCRM and access CiviContribute and edit contributions');

    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['administer CiviCRM', 'access CiviContribute', 'access CiviCRM', 'edit contributions'];
    $payment = $this->callAPIAndDocument('payment', 'create', $params, __FUNCTION__, __FILE__, 'Update Payment', 'UpdatePayment');

    // Check for proportional cancelled payment against lineitems.
    $minParams = [
      'entity_table' => 'civicrm_financial_item',
      'financial_trxn_id' => $payment['id'] - 1,
    ];

    $eft = $this->callAPISuccess('EntityFinancialTrxn', 'get', $minParams);
    $amounts = [-33.33, -16.67];

    foreach ($eft['values'] as $value) {
      $this->assertEquals($value['amount'], array_pop($amounts));
    }

    // Check for proportional updated payment against lineitems.
    $params = [
      'entity_table' => 'civicrm_financial_item',
      'financial_trxn_id' => $payment['id'],
    ];
    $eft = $this->callAPISuccess('EntityFinancialTrxn', 'get', $params);
    $amounts = [66.67, 33.33];
    foreach ($eft['values'] as $value) {
      $this->assertEquals($value['amount'], array_pop($amounts));
    }
    $items = $this->callAPISuccess('FinancialItem', 'get', [])['values'];
    $this->assertCount(2, $items);
    $itemSum = 0;
    foreach ($items as $item) {
      $this->assertEquals('civicrm_line_item', $item['entity_table']);
      $itemSum += $item['amount'];
    }
    $this->assertEquals(300, $itemSum);

    $params = [
      'contribution_id' => $contribution['id'],
    ];
    $payment = $this->callAPISuccess('payment', 'get', $params);
    $amounts = [100.00, -50.00, 50.00, 150.00];
    foreach ($payment['values'] as $value) {
      $amount = array_pop($amounts);
      $this->assertEquals($value['total_amount'], $amount, 'Mismatch total amount');

      // Check entity financial trxn created properly
      $params = [
        'entity_id' => $contribution['id'],
        'entity_table' => 'civicrm_contribution',
        'financial_trxn_id' => $value['id'],
      ];
      $eft = $this->callAPISuccess('EntityFinancialTrxn', 'get', $params);
      $this->assertEquals($eft['values'][$eft['id']]['amount'], $amount);
    }

    $this->callAPISuccess('Contribution', 'Delete', [
      'id' => $contribution['id'],
    ]);
  }

  /**
   * Test create payment api for paylater contribution
   */
  public function testCreatePaymentPayLater() {
    $this->createLoggedInUser();
    $contributionParams = [
      'total_amount' => 100,
      'currency' => 'USD',
      'contact_id' => $this->_individualId,
      'financial_type_id' => 1,
      'contribution_status_id' => 2,
      'is_pay_later' => 1,
    ];
    $contribution = $this->callAPISuccess('Contribution', 'create', $contributionParams);
    //add payment for pay later transaction
    $params = [
      'contribution_id' => $contribution['id'],
      'total_amount' => 100,
    ];
    $payment = $this->callAPISuccess('Payment', 'create', $params);
    $expectedResult = [
      $payment['id'] => [
        'from_financial_account_id' => 7,
        'to_financial_account_id' => 6,
        'total_amount' => 100,
        'status_id' => 1,
        'is_payment' => 1,
      ],
    ];
    $this->checkPaymentResult($payment, $expectedResult);
    // Check entity financial trxn created properly
    $params = [
      'entity_id' => $contribution['id'],
      'entity_table' => 'civicrm_contribution',
      'financial_trxn_id' => $payment['id'],
    ];
    $eft = $this->callAPISuccess('EntityFinancialTrxn', 'get', $params);
    $this->assertEquals($eft['values'][$eft['id']]['amount'], 100);
    $params = [
      'entity_table' => 'civicrm_financial_item',
      'financial_trxn_id' => $payment['id'],
    ];
    $eft = $this->callAPISuccess('EntityFinancialTrxn', 'get', $params);
    $this->assertEquals($eft['values'][$eft['id']]['amount'], 100);
    // Check contribution for completed status
    $contribution = $this->callAPISuccess('contribution', 'get', ['id' => $contribution['id']]);
    $this->assertEquals($contribution['values'][$contribution['id']]['contribution_status'], 'Completed');
    $this->assertEquals($contribution['values'][$contribution['id']]['total_amount'], 100.00);
    $this->callAPISuccess('Contribution', 'Delete', [
      'id' => $contribution['id'],
    ]);
  }

  /**
   * Test create payment api for pay later contribution with partial payment.
   *
   * https://lab.civicrm.org/dev/financial/issues/69
   */
  public function testCreatePaymentIncompletePaymentPartialPayment() {
    $contributionParams = [
      'total_amount' => 100,
      'currency' => 'USD',
      'contact_id' => $this->_individualId,
      'financial_type_id' => 1,
      'contribution_status_id' => 2,
    ];
    $contribution = $this->callAPISuccess('Contribution', 'create', $contributionParams);
    $this->callAPISuccess('Payment', 'create', [
      'contribution_id' => $contribution['id'],
      'total_amount' => 50,
      'payment_instrument_id' => 'Cash',
    ]);
    $payments = $this->callAPISuccess('Payment', 'get', ['contribution_id' => $contribution['id']])['values'];
    $this->assertCount(1, $payments);
  }

  /**
   * Test create payment api for pay later contribution with partial payment.
   */
  public function testCreatePaymentPayLaterPartialPayment() {
    $this->createLoggedInUser();
    $contributionParams = [
      'total_amount' => 100,
      'currency' => 'USD',
      'contact_id' => $this->_individualId,
      'financial_type_id' => 1,
      'contribution_status_id' => 2,
      'is_pay_later' => 1,
    ];
    $contribution = $this->callAPISuccess('Contribution', 'create', $contributionParams);
    //Create partial payment
    $params = [
      'contribution_id' => $contribution['id'],
      'total_amount' => 60,
    ];
    $payment = $this->callAPISuccess('Payment', 'create', $params);
    $expectedResult = [
      $payment['id'] => [
        'total_amount' => 60,
        'status_id' => 1,
        'is_payment' => 1,
      ],
    ];
    $this->checkPaymentResult($payment, $expectedResult);
    // Check entity financial trxn created properly
    $params = [
      'entity_id' => $contribution['id'],
      'entity_table' => 'civicrm_contribution',
      'financial_trxn_id' => $payment['id'],
    ];
    $eft = $this->callAPISuccess('EntityFinancialTrxn', 'get', $params);
    $this->assertEquals($eft['values'][$eft['id']]['amount'], 60);
    $params = [
      'entity_table' => 'civicrm_financial_item',
      'financial_trxn_id' => $payment['id'],
    ];
    $eft = $this->callAPISuccess('EntityFinancialTrxn', 'get', $params);
    $this->assertEquals($eft['values'][$eft['id']]['amount'], 60);
    $contribution = $this->callAPISuccess('contribution', 'get', ['id' => $contribution['id']]);
    $this->assertEquals($contribution['values'][$contribution['id']]['contribution_status'], 'Partially paid');
    $this->assertEquals($contribution['values'][$contribution['id']]['total_amount'], 100.00);
    //Create full payment
    $params = [
      'contribution_id' => $contribution['id'],
      'total_amount' => 40,
    ];
    // Rename the 'completed' status label first to check that we are not using the labels!
    $this->callAPISuccess('OptionValue', 'get', ['name' => 'Completed', 'option_group_id' => 'contribution_status', 'api.OptionValue.create' => ['label' => 'Unicorn']]);
    $payment = $this->callAPISuccess('Payment', 'create', $params);
    $expectedResult = [
      $payment['id'] => [
        'from_financial_account_id' => 7,
        'to_financial_account_id' => 6,
        'total_amount' => 40,
        'status_id' => 1,
        'is_payment' => 1,
      ],
    ];
    $this->checkPaymentResult($payment, $expectedResult);
    // Check entity financial trxn created properly
    $params = [
      'entity_id' => $contribution['id'],
      'entity_table' => 'civicrm_contribution',
      'financial_trxn_id' => $payment['id'],
    ];
    $eft = $this->callAPISuccess('EntityFinancialTrxn', 'get', $params);
    $this->assertEquals($eft['values'][$eft['id']]['amount'], 40);
    $params = [
      'entity_table' => 'civicrm_financial_item',
      'financial_trxn_id' => $payment['id'],
    ];
    $eft = $this->callAPISuccess('EntityFinancialTrxn', 'get', $params);
    $this->assertEquals($eft['values'][$eft['id']]['amount'], 40);
    // Check contribution for completed status
    $contribution = $this->callAPISuccess('contribution', 'get', ['id' => $contribution['id']]);
    $this->assertEquals($contribution['values'][$contribution['id']]['contribution_status'], 'Unicorn');
    $this->assertEquals($contribution['values'][$contribution['id']]['total_amount'], 100.00);
    $this->callAPISuccess('Contribution', 'Delete', [
      'id' => $contribution['id'],
    ]);
    $this->callAPISuccess('OptionValue', 'get', ['name' => 'Completed', 'option_group_id' => 'contribution_status', 'api.OptionValue.create' => ['label' => 'Completed']]);
    $this->callAPISuccessGetCount('Activity', ['target_contact_id' => $this->_individualId, 'activity_type_id' => 'Payment'], 2);
  }

  /**
   * Add a location to our event.
   *
   * @param int $eventID
   */
  protected function addLocationToEvent($eventID) {
    $addressParams = [
      'name' => 'event place',
      'street_address' => 'streety street',
      'location_type_id' => 1,
      'is_primary' => 1,
    ];
    // api requires contact_id - perhaps incorrectly but use add to get past that.
    $address = CRM_Core_BAO_Address::add($addressParams);

    $location = $this->callAPISuccess('LocBlock', 'create', ['address_id' => $address->id]);
    $this->callAPISuccess('Event', 'create', [
      'id' => $eventID,
      'loc_block_id' => $location['id'],
      'is_show_location' => TRUE,
    ]);
  }

}
