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

  /**
   * Setup function.
   *
   * @throws \CRM_Core_Exception
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
   *
   * @throws \CRM_Core_Exception
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
    $this->callAPIFailure('payment', 'get', $params, 'API permission check failed for Payment/get call; insufficient permission: require access CiviCRM and access CiviContribute');

    array_push(CRM_Core_Config::singleton()->userPermissionClass->permissions, 'access CiviContribute');
    $this->callAPISuccess('payment', 'get', $params);

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
    $this->validateAllPayments();
  }

  /**
   * Test multiple payments for contribution and assert if option
   * and is_payment returns the correct list of payments.
   *
   * @throws \CRM_Core_Exception
   */
  public function testMultiplePaymentsForContribution() {
    $params = [
      'contact_id' => $this->_individualId,
      'total_amount' => 100,
      'contribution_status_id' => 'Pending',
    ];
    $contributionID = $this->contributionCreate($params);
    $paymentParams = [
      'contribution_id' => $contributionID,
      'total_amount' => 20,
      'trxn_date' => date('Y-m-d'),
    ];
    $this->callAPISuccess('payment', 'create', $paymentParams);
    $paymentParams['total_amount'] = 30;
    $this->callAPISuccess('payment', 'create', $paymentParams);

    $paymentParams['total_amount'] = 50;
    $this->callAPISuccess('payment', 'create', $paymentParams);

    //check if contribution status is set to "Completed".
    $contribution = $this->callAPISuccessGetSingle('Contribution', [
      'id' => $contributionID,
    ]);
    $this->assertEquals('Completed', $contribution['contribution_status']);

    //Get Payment using options
    $getParams = [
      'sequential' => 1,
      'contribution_id' => $contributionID,
      'is_payment' => 1,
      'options' => ['limit' => 0, 'sort' => 'total_amount DESC'],
    ];
    $payments = $this->callAPISuccess('Payment', 'get', $getParams);
    $this->assertEquals(3, $payments['count']);
    foreach ([50, 30, 20] as $key => $total_amount) {
      $this->assertEquals($total_amount, $payments['values'][$key]['total_amount']);
    }
  }

  /**
   * Retrieve Payment using trxn_id.
   *
   * @throws \CRM_Core_Exception
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
    $this->callAPISuccess('Payment', 'create', ['total_amount' => '-20', 'contribution_id' => $contributionID2]);
    $this->validateAllPayments();
  }

  /**
   * Test contribution receipts triggered by Payment.create with is_send_contribution_notification = TRUE.
   *
   * @throws \CRM_Core_Exception
   */
  public function testPaymentSendContributionReceipt() {
    $mut = new CiviMailUtils($this);
    $contribution = $this->createPartiallyPaidParticipantOrder();
    $event = $this->callAPISuccess('Event', 'get', []);
    $this->addLocationToEvent($event['id']);
    $params = [
      'contribution_id' => $contribution['id'],
      'total_amount' => 150,
      'check_number' => '345',
      'trxn_date' => '2018-08-13 17:57:56',
      'is_send_contribution_notification' => TRUE,
    ];
    $this->callAPISuccess('Payment', 'create', $params);
    $contribution = $this->callAPISuccessGetSingle('Contribution', ['id' => $contribution['id']]);
    $this->assertNotEmpty($contribution['receipt_date']);
    $mut->checkMailLog([
      'Price Field - Price Field 1        1   $ 100.00    $ 100.00',
      'event place',
      'streety street',
    ]);
  }

  /**
   * Test full refund when no payment has actually been record.
   */
  public function testFullRefundWithPaymentAlreadyRefunded() {
    $params1 = [
      'contact_id' => $this->_individualId,
      'trxn_id' => 111111,
      'total_amount' => 10,
    ];
    $contributionID1 = $this->contributionCreate($params1);
    $paymentParams = ['contribution_id' => $contributionID1];
    $this->callAPISuccess('Payment', 'create', ['total_amount' => '-10', 'contribution_id' => $contributionID1]);
    $payment = $this->callAPISuccess('payment', 'get', $paymentParams);
    $this->callAPISuccess('Payment', 'create', ['total_amount' => '-10', 'contribution_id' => $contributionID1]);
    $payment = $this->callAPISuccess('payment', 'get', $paymentParams);
    $this->validateAllPayments();
  }

  public function testNegativePaymentWithNegativeContribution() {
    $params1 = [
      'contact_id' => $this->_individualId,
      'trxn_id' => 111111,
      'total_amount' => -10,
    ];
    $contributionID1 = $this->contributionCreate($params1);
    $this->callAPISuccess('Payment', 'create', ['total_amount' => '-20', 'contribution_id' => $contributionID1]);
    $paymentParams = ['contribution_id' => $contributionID1];
    $payment = $this->callAPISuccess('payment', 'get', $paymentParams);
    $this->validateAllPayments();
  }

  /**
   * Test email receipt for partial payment.
   *
   * @throws \CRM_Core_Exception
   */
  public function testPaymentEmailReceipt() {
    $mut = new CiviMailUtils($this);
    $contribution = $this->createPartiallyPaidParticipantOrder();
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
    $mut->assertSubjects(['Payment Receipt - Annual CiviCRM meet - Mr. Anthony Anderson II']);
    $mut->checkMailLog([
      'From: "FIXME" <info@EXAMPLE.ORG>',
      'Dear Anthony,',
      'Total Fee: $ 300.00',
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
    $this->validateAllPayments();
  }

  /**
   * Test email receipt for partial payment.
   *
   * @throws \CRM_Core_Exception
   */
  public function testPaymentEmailReceiptFullyPaid() {
    $mut = new CiviMailUtils($this);
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['access CiviContribute', 'edit contributions', 'access CiviCRM'];
    $contribution = $this->createPartiallyPaidParticipantOrder();

    $params = [
      'contribution_id' => $contribution['id'],
      'total_amount' => 150,
    ];
    $payment = $this->callAPISuccess('payment', 'create', $params);

    // Here we set the email to an  invalid email & use check_permissions, domain email should be used.
    $email = $this->callAPISuccess('Email', 'create', ['contact_id' => 1, 'email' => 'bob@example.com']);
    $this->callAPISuccess('Payment', 'sendconfirmation', ['id' => $payment['id'], 'from' => $email['id'], 'check_permissions' => 1]);
    $mut->assertSubjects(['Payment Receipt - Annual CiviCRM meet - Mr. Anthony Anderson II', 'Registration Confirmation - Annual CiviCRM meet - Mr. Anthony Anderson II']);
    $mut->checkMailLog([
      'From: "FIXME" <info@EXAMPLE.ORG>',
      'Dear Anthony,',
      'Below you will find a receipt for this payment.',
      'Total Fee: $ 300.00',
      'This Payment Amount: $ 150.00',
      'Balance Owed: $ 0.00',
      'Thank you for completing this payment.',
    ]);
    $this->validateAllPayments();
  }

  /**
   * Test email receipt for partial payment.
   *
   * @dataProvider getThousandSeparators
   *
   * @param string $thousandSeparator
   *
   * @throws \CRM_Core_Exception
   */
  public function testRefundEmailReceipt($thousandSeparator) {
    $this->setCurrencySeparators($thousandSeparator);
    $decimalSeparator = ($thousandSeparator === ',' ? '.' : ',');
    $mut = new CiviMailUtils($this);
    $contribution = $this->createPartiallyPaidParticipantOrder();
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
    $mut->assertSubjects(['Refund Notification - Annual CiviCRM meet - Mr. Anthony Anderson II']);
    $mut->checkMailLog([
      'Dear Anthony,',
      'A refund has been issued based on changes in your registration selections.',
      'Total Fee: $ 300' . $decimalSeparator . '00',
      'Refund Amount: $ -30' . $decimalSeparator . '00',
      'Event Information and Location',
      'Paid By: Check',
      'Transaction Date: November 13th, 2018 12:01 PM',
      'Total Paid: $ 170' . $decimalSeparator . '00',
    ]);
    $this->validateAllPayments();
  }

  /**
   * Test adding a payment to a pending multi-line order.
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreatePaymentPendingOrderNoLineItems() {
    $order = $this->createPendingParticipantOrder();
    $this->callAPISuccess('Payment', 'create', [
      'order_id' => $order['id'],
      'total_amount' => 50,
    ]);
    $this->validateAllPayments();
  }

  /**
   * Test that Payment.create does not fail if the line items are missing.
   *
   * In the original spec it was anticipated that financial items would not be created
   * for pending contributions in some circumstances. We've backed away from this and
   * I mostly could not find a way to do it through the UI. But I did seem to once &
   * I want to be sure that if they ARE missing no fatal occurs so this tests
   * that in an artificial way.
   *
   * @throws \CRM_Core_Exception
   */
  public function testAddPaymentMissingFinancialItems() {
    $contribution = $this->callAPISuccess('Contribution', 'create', [
      'total_amount' => 50,
      'financial_type_id' => 'Donation',
      'contact_id' => $this->individualCreate(),
      'contribution_status_id' => 'Pending',
    ]);
    CRM_Core_DAO::executeQuery('DELETE FROM civicrm_financial_item');
    $this->callAPISuccess('Payment', 'create', ['contribution_id' => $contribution['id'], 'payment_instrument_id' => 'Check', 'total_amount' => 5]);
    $this->validateAllPayments();
  }

  /**
   * Add participant with contribution
   *
   * @return array
   *
   * @throws \CRM_Core_Exception
   */
  protected function createPendingParticipantOrder() {
    return $this->callAPISuccess('Order', 'create', $this->getParticipantOrderParams());
  }

  /**
   * Test create payment api with no line item in params
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreatePaymentNoLineItems() {
    $contribution = $this->createPartiallyPaidParticipantOrder();

    //Create partial payment
    $params = [
      'contribution_id' => $contribution['id'],
      'total_amount' => 50,
    ];
    $payment = $this->callAPIAndDocument('payment', 'create', $params, __FUNCTION__, __FILE__);
    $this->checkPaymentIsValid($payment['id'], $contribution['id']);

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
    $this->validateAllPayments();
  }

  /**
   * Function to assert db values
   *
   * @throws \CRM_Core_Exception
   */
  public function checkPaymentResult($payment, $expectedResult) {
    $refreshedPayment = $this->callAPISuccessGetSingle('Payment', ['financial_trxn_id' => $payment['id']]);
    foreach ($expectedResult[$payment['id']] as $key => $value) {
      $this->assertEquals($refreshedPayment[$key], $value, 'mismatch on ' . $key);      $this->assertEquals($refreshedPayment[$key], $value, 'mismatch on ' . $key);
    }
  }

  /**
   * Test create payment api with line item in params
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreatePaymentLineItems() {
    $contribution = $this->createPartiallyPaidParticipantOrder();
    $lineItems = $this->callAPISuccess('LineItem', 'get', ['contribution_id' => $contribution['id']])['values'];

    // Create partial payment by passing line item array is params.
    $params = [
      'contribution_id' => $contribution['id'],
      'total_amount' => 50,
    ];
    $amounts = [40, 10];
    foreach ($lineItems as $id => $ignore) {
      $params['line_item'][] = [$id => array_pop($amounts)];
    }
    $payment = $this->callAPIAndDocument('Payment', 'create', $params, __FUNCTION__, __FILE__, 'Payment with line item', 'CreatePaymentWithLineItems');
    $this->checkPaymentIsValid($payment['id'], $contribution['id']);

    $params = [
      'entity_table' => 'civicrm_financial_item',
      'financial_trxn_id' => $payment['id'],
      'return' => ['entity_id.entity_id', 'amount'],
    ];
    $eft = $this->callAPISuccess('EntityFinancialTrxn', 'get', $params)['values'];
    $this->assertCount(2, $eft);
    $amounts = [40, 10];
    foreach ($eft as $value) {
      $this->assertEquals($value['amount'], array_pop($amounts));
    }

    // Now create payment to complete total amount of contribution
    $params = [
      'contribution_id' => $contribution['id'],
      'total_amount' => 100,
    ];
    $amounts = [80, 20];
    foreach ($lineItems as $id => $ignore) {
      $params['line_item'][] = [$id => array_pop($amounts)];
    }
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
    $params = [
      'entity_table' => 'civicrm_financial_item',
      'financial_trxn_id' => $payment['id'],
    ];
    $eft = $this->callAPISuccess('EntityFinancialTrxn', 'get', $params)['values'];
    $this->assertCount(2, $eft);
    $amounts = [80, 20];
    foreach ($eft as $value) {
      $this->assertEquals($value['amount'], array_pop($amounts));
    }
    // Check contribution for completed status
    $contribution = $this->callAPISuccess('Contribution', 'get', ['id' => $contribution['id']]);

    $this->assertEquals($contribution['values'][$contribution['id']]['contribution_status'], 'Completed');
    $this->assertEquals($contribution['values'][$contribution['id']]['total_amount'], 300.00);
    $paymentParticipant = [
      'contribution_id' => $contribution['id'],
    ];
    $participantPayment = $this->callAPISuccess('ParticipantPayment', 'getsingle', $paymentParticipant);
    $participant = $this->callAPISuccess('participant', 'get', ['id' => $participantPayment['participant_id']]);
    $this->assertEquals($participant['values'][$participant['id']]['participant_status'], 'Registered');
    $this->validateAllPayments();
  }

  /**
   * Test negative payment using create API.
   *
   * @throws \CRM_Core_Exception
   */
  public function testRefundPayment() {
    $result = $this->callAPISuccess('Contribution', 'create', [
      'financial_type_id' => "Donation",
      'total_amount' => 100,
      'contact_id' => $this->_individualId,
    ]);
    $contributionID = $result['id'];

    //Refund a part of the main amount.
    $this->callAPISuccess('Payment', 'create', [
      'contribution_id' => $contributionID,
      'total_amount' => -10,
    ]);

    $contribution = $this->callAPISuccessGetSingle('Contribution', [
      'return' => ["contribution_status_id"],
      'id' => $contributionID,
    ]);
    //Still we've a status of Completed after refunding a partial amount.
    $this->assertEquals($contribution['contribution_status'], 'Completed');

    //Refund the complete amount.
    $this->callAPISuccess('Payment', 'create', [
      'contribution_id' => $contributionID,
      'total_amount' => -90,
    ]);
    $contribution = $this->callAPISuccessGetSingle('Contribution', [
      'return' => ['contribution_status_id'],
      'id' => $contributionID,
    ]);
    //Assert if main contribution status is updated to "Refunded".
    $this->assertEquals($contribution['contribution_status'], 'Refunded Label**');
  }

  /**
   * Test cancel payment api
   *
   * @throws \CRM_Core_Exception
   */
  public function testCancelPayment() {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['administer CiviCRM', 'access CiviContribute'];
    $contribution = $this->createPartiallyPaidParticipantOrder();

    $params = [
      'contribution_id' => $contribution['id'],
    ];

    $payment = $this->callAPISuccess('payment', 'get', $params);
    $this->assertEquals(1, $payment['count']);

    $cancelParams = [
      'id' => $payment['id'],
      'check_permissions' => TRUE,
    ];
    $this->callAPIFailure('payment', 'cancel', $cancelParams, 'API permission check failed for Payment/cancel call; insufficient permission: require access CiviCRM and access CiviContribute and edit contributions');

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
    $this->validateAllPayments();
  }

  /**
   * Test delete payment api
   *
   * @throws \CRM_Core_Exception
   */
  public function testDeletePayment() {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['administer CiviCRM', 'access CiviContribute'];
    $contribution = $this->createPartiallyPaidParticipantOrder();

    $params = [
      'contribution_id' => $contribution['id'],
    ];

    $payment = $this->callAPISuccessGetSingle('payment', $params);

    $deleteParams = [
      'id' => $payment['id'],
      'check_permissions' => TRUE,
    ];
    $this->callAPIFailure('payment', 'delete', $deleteParams, 'API permission check failed for Payment/delete call; insufficient permission: require access CiviCRM and access CiviContribute and delete in CiviContribute');

    array_push(CRM_Core_Config::singleton()->userPermissionClass->permissions, 'access CiviCRM', 'delete in CiviContribute');
    $this->callAPIAndDocument('payment', 'delete', $deleteParams, __FUNCTION__, __FILE__);
    $this->callAPISuccessGetCount('payment', $params, 0);

    $this->callAPISuccess('Contribution', 'Delete', ['id' => $contribution['id']]);
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
   *
   * @throws \CRM_Core_Exception
   */
  public function testUpdatePayment() {
    CRM_Core_Config::singleton()->userPermissionClass->permissions = ['administer CiviCRM', 'access CiviContribute', 'edit contributions'];
    $contribution = $this->createPartiallyPaidParticipantOrder();

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

    $this->validateAllPayments();
    // Check for proportional cancelled payment against lineitems.
    $minParams = [
      'entity_table' => 'civicrm_financial_item',
      'financial_trxn_id' => $payment['id'] - 1,
    ];

    $eft = $this->callAPISuccess('EntityFinancialTrxn', 'get', $minParams)['values'];
    $this->assertCount(2, $eft);
    $amounts = [-33.33, -16.67];

    foreach ($eft as $value) {
      $this->assertEquals($value['amount'], array_pop($amounts));
    }

    // Check for proportional updated payment against lineitems.
    $params = [
      'entity_table' => 'civicrm_financial_item',
      'financial_trxn_id' => $payment['id'],
    ];
    $eft = $this->callAPISuccess('EntityFinancialTrxn', 'get', $params)['values'];
    $amounts = [66.67, 33.33];
    foreach ($eft as $value) {
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
    $this->validateAllPayments();
  }

  /**
   * Test that a contribution can be overpaid with the payment api.
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreatePaymentOverPay() {
    $contributionID = $this->contributionCreate(['contact_id' => $this->individualCreate()]);
    $payment = $this->callAPISuccess('Payment', 'create', ['total_amount' => 5, 'order_id' => $contributionID]);
    $contribution = $this->callAPISuccessGetSingle('Contribution', ['id' => $contributionID]);
    $this->assertEquals('Completed', $contribution['contribution_status']);
    $this->callAPISuccessGetCount('EntityFinancialTrxn', ['financial_trxn_id' => $payment['id'], 'entity_table' => 'civicrm_financial_item'], 0);
    $this->validateAllPayments();
    $this->validateAllContributions();
  }

  /**
   * Test create payment api for paylater contribution
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreatePaymentPayLater() {
    $this->createLoggedInUser();
    $processorID  = $this->paymentProcessorCreate();
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
      'card_type_id' => 'Visa',
      'pan_truncation' => '1234',
      'trxn_result_code' => 'Startling success',
      'payment_instrument_id' => $processorID,
      'trxn_id' => 1234,
    ];
    $payment = $this->callAPISuccess('Payment', 'create', $params);
    $expectedResult = [
      $payment['id'] => [
        'from_financial_account_id' => 7,
        'to_financial_account_id' => 6,
        'total_amount' => 100,
        'status_id' => 1,
        'is_payment' => 1,
        'card_type_id' => 1,
        'pan_truncation' => '1234',
        'trxn_result_code' => 'Startling success',
        'trxn_id' => 1234,
        'payment_instrument_id' => 1,
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
    $this->validateAllPayments();
  }

  /**
   * Test net amount is set when fee amount is passed in.
   *
   * @throws \CRM_Core_Exception
   */
  public function testNetAmount() {
    $order = $this->createPendingParticipantOrder();
    $payment = $this->callAPISuccess('Payment', 'create', ['order_id' => $order['id'], 'total_amount' => 10, 'fee_amount' => .25]);
    $this->assertEquals('9.75', $this->callAPISuccessGetValue('Payment', ['id' => $payment['id'], 'return' => 'net_amount']));
  }

  /**
   * Test create payment api for pay later contribution with partial payment.
   *
   * https://lab.civicrm.org/dev/financial/issues/69
   * @throws \CRM_Core_Exception
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
    $checkNumber1 = 'C111';
    $this->callAPISuccess('Payment', 'create', [
      'contribution_id' => $contribution['id'],
      'total_amount' => 50,
      'payment_instrument_id' => 'Check',
      'check_number' => $checkNumber1,
    ]);
    $payments = $this->callAPISuccess('Payment', 'get', ['contribution_id' => $contribution['id']])['values'];
    $this->assertCount(1, $payments);
    $this->validateAllPayments();

    $checkNumber2 = 'C222';
    $this->callAPISuccess('Payment', 'create', [
      'contribution_id' => $contribution['id'],
      'total_amount' => 20,
      'payment_instrument_id' => 'Check',
      'check_number' => $checkNumber2,
    ]);
    $expectedConcatanatedCheckNumbers = implode(',', [$checkNumber1, $checkNumber2]);
    //Assert check number is concatenated on the main contribution.
    $contributionValues = $this->callAPISuccess('Contribution', 'getsingle', ['id' => $contribution['id']]);
    $this->assertEquals($expectedConcatanatedCheckNumbers, $contributionValues['check_number']);
  }

  /**
   * Test create payment api for failed contribution.
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreatePaymentOnFailedContribution() {
    $this->createLoggedInUser();
    //Create a direct Failed Contribution (no ft record inserted).
    $contributionParams = [
      'total_amount' => 50,
      'currency' => 'USD',
      'contact_id' => $this->_individualId,
      'financial_type_id' => 1,
      'contribution_status_id' => 'Failed',
    ];
    $contribution = $this->callAPISuccess('Contribution', 'create', $contributionParams);

    //Complete the payment in a single call.
    $params = [
      'contribution_id' => $contribution['id'],
      'total_amount' => 50,
    ];
    $payment = $this->callAPISuccess('Payment', 'create', $params);

    //Verify 2 rows are added to the financial trxn as payment is moved from
    //Failed -> Pending -> Completed, i.e, 0 -> 7(Account receivable) -> 6 (Deposit Bank).
    $params = [
      'entity_id' => $contribution['id'],
      'entity_table' => 'civicrm_contribution',
    ];
    $eft = $this->callAPISuccess('EntityFinancialTrxn', 'get', $params);
    $this->assertEquals($eft['count'], 2);

    //Test 2
    //Create a Pending Contribution so an FT record is inserted.
    $contributionParams = [
      'total_amount' => 100,
      'currency' => 'USD',
      'contact_id' => $this->_individualId,
      'financial_type_id' => 1,
      'contribution_status_id' => 'Pending',
      'is_pay_later' => 1,
    ];
    $contribution = $this->callAPISuccess('Order', 'create', $contributionParams);

    //Mark it as failed. No FT record inserted on this update
    //so the payment is still in the account receivable account id 7.
    $this->callAPISuccess('Contribution', 'create', [
      'id' => $contribution['id'],
      'contribution_status_id' => 'Failed',
    ]);
    $this->createPartialPaymentOnContribution($contribution['id'], 60, 100.00);

    //Call payment create on the failed contribution.
    $params = [
      'contribution_id' => $contribution['id'],
      'total_amount' => 40,
    ];
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

    //Check total ft rows are 4: 2 from initial pending + partial payment
    //+ 2 for failed -> completed transition.
    $params = [
      'entity_id' => $contribution['id'],
      'entity_table' => 'civicrm_contribution',
    ];
    $eft = $this->callAPISuccess('EntityFinancialTrxn', 'get', $params);
    $this->assertEquals($eft['count'], 4);

    $this->validateAllPayments();
  }

  /**
   * Create partial payment for contribution
   *
   * @param $contributionID
   * @param $partialAmount
   * @param $totalAmount
   */
  public function createPartialPaymentOnContribution($contributionID, $partialAmount, $totalAmount) {
    //Create partial payment
    $params = [
      'contribution_id' => $contributionID,
      'total_amount' => $partialAmount,
    ];
    $payment = $this->callAPISuccess('Payment', 'create', $params);
    $expectedResult = [
      $payment['id'] => [
        'total_amount' => $partialAmount,
        'status_id' => 1,
        'is_payment' => 1,
      ],
    ];
    $this->checkPaymentResult($payment, $expectedResult);
    // Check entity financial trxn created properly
    $params = [
      'entity_id' => $contributionID,
      'entity_table' => 'civicrm_contribution',
      'financial_trxn_id' => $payment['id'],
    ];
    $eft = $this->callAPISuccess('EntityFinancialTrxn', 'get', $params);
    $this->assertEquals($eft['values'][$eft['id']]['amount'], $partialAmount);
    $params = [
      'entity_table' => 'civicrm_financial_item',
      'financial_trxn_id' => $payment['id'],
    ];
    $eft = $this->callAPISuccess('EntityFinancialTrxn', 'get', $params);
    $this->assertEquals($eft['values'][$eft['id']]['amount'], $partialAmount);
    $contribution = $this->callAPISuccess('contribution', 'get', ['id' => $contributionID]);
    $this->assertEquals($contribution['values'][$contribution['id']]['contribution_status'], 'Partially paid');
    $this->assertEquals($contribution['values'][$contribution['id']]['total_amount'], $totalAmount);
  }

  /**
   * Test create payment api for pay later contribution with partial payment.
   *
   * @throws \CRM_Core_Exception
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
    $contribution = $this->callAPISuccess('Order', 'create', $contributionParams);
    $this->createPartialPaymentOnContribution($contribution['id'], 60, 100.00);

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
    $this->validateAllPayments();
  }

  /**
   * Test that Payment.create uses the to_account of the payment processor.
   *
   * @throws \CiviCRM_API3_Exception
   * @throws \CRM_Core_Exception
   */
  public function testPaymentWithProcessorWithOddFinancialAccount() {
    $processor = $this->dummyProcessorCreate(['financial_account_id' => 'Deposit Bank Account', 'payment_instrument_id' => 'Cash']);
    $processor2 = $this->dummyProcessorCreate(['financial_account_id' => 'Payment Processor Account', 'name' => 'p2', 'payment_instrument_id' => 'EFT']);
    $contributionParams = [
      'total_amount' => 100,
      'currency' => 'USD',
      'contact_id' => $this->_individualId,
      'financial_type_id' => 1,
      'contribution_status_id' => 'Pending',
    ];
    $order = $this->callAPISuccess('Order', 'create', $contributionParams);
    $this->callAPISuccess('Payment', 'create', ['payment_processor_id' => $processor->getID(), 'total_amount' => 6, 'contribution_id' => $order['id']]);
    $this->callAPISuccess('Payment', 'create', ['payment_processor_id' => $processor2->getID(), 'total_amount' => 15, 'contribution_id' => $order['id']]);
    $payments = $this->callAPISuccess('Payment', 'get', ['sequential' => 1, 'contribution_id' => $order['id']])['values'];
    $this->assertEquals('Deposit Bank Account', CRM_Core_PseudoConstant::getName('CRM_Core_BAO_FinancialTrxn', 'to_financial_account_id', $payments[0]['to_financial_account_id']));
    $this->assertEquals('Payment Processor Account', CRM_Core_PseudoConstant::getName('CRM_Core_BAO_FinancialTrxn', 'to_financial_account_id', $payments[1]['to_financial_account_id']));
    $this->assertEquals('Accounts Receivable', CRM_Core_PseudoConstant::getName('CRM_Core_BAO_FinancialTrxn', 'from_financial_account_id', $payments[0]['from_financial_account_id']));
    $this->assertEquals('Accounts Receivable', CRM_Core_PseudoConstant::getName('CRM_Core_BAO_FinancialTrxn', 'from_financial_account_id', $payments[1]['from_financial_account_id']));
    $this->assertEquals('Cash', CRM_Core_PseudoConstant::getName('CRM_Core_BAO_FinancialTrxn', 'payment_instrument_id', $payments[0]['payment_instrument_id']));
    $this->assertEquals('EFT', CRM_Core_PseudoConstant::getName('CRM_Core_BAO_FinancialTrxn', 'payment_instrument_id', $payments[1]['payment_instrument_id']));
    // $order = $this->callAPISuccessGetSingle('Order', ['id' => $processor->getID()]);
    // $this->assertEquals('Cash', CRM_Core_PseudoConstant::getName('CRM_Core_BAO_FinancialTrxn', 'payment_instrument_id', $order['payment_instrument_id']));
  }

  /**
   * Add a location to our event.
   *
   * @param int $eventID
   *
   * @throws \CRM_Core_Exception
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
    $this->validateAllPayments();
  }

  /**
   * Check the created payment is valid.
   *
   * This is probably over-testing really since we are repetitively checking a basic function...
   *
   * @param int $paymentID
   * @param int $contributionID
   * @param int $amount
   *
   * @throws \CRM_Core_Exception
   */
  protected function checkPaymentIsValid($paymentID, $contributionID, $amount = 50) {
    $payment = $this->callAPISuccess('Payment', 'getsingle', ['financial_trxn_id' => $paymentID]);
    $this->assertEquals(7, $payment['from_financial_account_id']);
    $this->assertEquals(6, $payment['to_financial_account_id']);
    $this->assertEquals(1, $payment['status_id']);
    $this->assertEquals(1, $payment['is_payment']);
    $this->assertEquals($amount, $payment['total_amount']);

    $eft = $this->callAPISuccess('EntityFinancialTrxn', 'get', [
      'entity_id' => $contributionID,
      'entity_table' => 'civicrm_contribution',
      'financial_trxn_id' => $payment['id'],
    ]);

    $this->assertEquals($eft['values'][$eft['id']]['amount'], $amount);
    $this->validateAllPayments();
  }

  /**
   * This test was introduced in
   * https://github.com/civicrm/civicrm-core/pull/17688 to ensure that a
   * contribution's date is not set to today's date when a payment is received,
   * and that the contribution's trxn_id is set to that of the payment.
   *
   * This tests the current behaviour, but there are questions about whether
   * that's right.
   *
   * The current behaviour is that when a payment is received that completes a
   * contribution: the contribution's receive_date is set to that of the
   * payment (passed to Payment.create as trxn_date).
   *
   * But why *should* we update the receive_date at all?
   *
   * If we decide that receive_date should not be touched, just change
   * $trxnDate for $trxnID as detailed in the code comment below, which will
   * still make sure we're not setting today's date, as well as confirming
   * that the original date is not changed.
   *
   * @see https://github.com/civicrm/civicrm-core/pull/17688
   * @see https://lab.civicrm.org/dev/financial/-/issues/139
   *
   */
  public function testPaymentCreateTrxnIdAndDates() {

    $trxnDate = '2010-01-01 09:00:00';
    $trxnID = 'aabbccddeeffggh';
    $originalReceiveDate = '2010-02-02 22:22:22';

    $contributionID = $this->contributionCreate([
      'contact_id'             => $this->individualCreate(),
      'total_amount'           => 100,
      'contribution_status_id' => 'Pending',
      'receive_date'           => $originalReceiveDate,
    ]);

    $payment = $this->callAPISuccess('Payment', 'create', [
      'total_amount' => 100,
      'order_id'     => $contributionID,
      'trxn_date'    => $trxnDate,
      'trxn_id'      => $trxnID,
    ]);

    $contribution = $this->callAPISuccessGetSingle('Contribution', ['id' => $contributionID]);
    $this->assertEquals('Completed', $contribution['contribution_status']);

    $this->assertEquals($trxnID, $contribution['trxn_id'],
      "Contribution trxn_id should have been set to that of the payment.");

    $this->assertEquals($originalReceiveDate, $contribution['receive_date'],
      "Contribution receive date was changed, but should not have been.");

    $this->validateAllPayments();
    $this->validateAllContributions();

  }

}
