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
 * Class CRM_Core_BAO_FinancialTrxnTest
 * @group headless
 */
class CRM_Core_BAO_FinancialTrxnTest extends CiviUnitTestCase {

  /**
   * Check method create().
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreate() {
    $contactId = $this->individualCreate();
    $financialTypeId = 1;
    $this->contributionCreate([
      'contact_id' => $contactId,
      'financial_type_id' => $financialTypeId,
    ]);
    $params = [
      'contribution_id' => $financialTypeId,
      'to_financial_account_id' => 1,
      'trxn_date' => 20091021184930,
      'trxn_type' => 'Debit',
      'total_amount' => 10,
      'net_amount' => 90.00,
      'currency' => 'USD',
      'payment_processor' => 'Dummy',
      'trxn_id' => 'test_01014000',
    ];
    $FinancialTrxn = CRM_Core_BAO_FinancialTrxn::create($params);

    $result = $this->assertDBNotNull('CRM_Core_BAO_FinancialTrxn', $FinancialTrxn->id,
      'total_amount', 'id',
      'Database check on updated financial trxn record.'
    );

    $this->assertEquals($result, 10, 'Verify financial trxn total_amount.');
  }

  /**
   * Test getTotalPayments function.
   *
   * @throws \CRM_Core_Exception
   */
  public function testGetTotalPayments() {
    $contactId = $this->individualCreate();

    $params = [
      'contact_id' => $contactId,
      'currency' => 'USD',
      'financial_type_id' => 1,
      'contribution_status_id' => 2,
      'payment_instrument_id' => 1,
      'source' => 'STUDENT',
      'is_pay_later' => 1,
      'receive_date' => '20080522000000',
      'receipt_date' => '20080522000000',
      'non_deductible_amount' => 0.00,
      'total_amount' => 200.00,
      'fee_amount' => 5,
      'net_amount' => 195,
      'trxn_id' => '22ereerwwe4444yy',
      'invoice_id' => '86ed39e9e9yy6ef6541621ce0eafe7eb81',
      'thankyou_date' => '20080522',
    ];

    $contribution = $this->callAPISuccess('Contribution', 'create', $params);
    $contribution = $contribution['values'][$contribution['id']];

    $totalPaymentAmount = CRM_Core_BAO_FinancialTrxn::getTotalPayments($contribution['id']);
    $this->assertEquals(0, $totalPaymentAmount, 'Amount not matching.');

    $params['id'] = $contribution['id'];
    $params['contribution_status_id'] = 1;

    $contribution = $this->callAPISuccess('Contribution', 'create', $params);

    $totalPaymentAmount = CRM_Core_BAO_FinancialTrxn::getTotalPayments($contribution['id']);
    $this->assertEquals('200.00', $totalPaymentAmount, 'Amount not matching.');
  }

  /**
   * Tests the lines of code that used to be in the getPartialPaymentTrxn fn.
   *
   * @throws \CRM_Core_Exception
   */
  public function testGetTotalPaymentsParticipantOrder() {
    $orderID = $this->createPartiallyPaidParticipantOrder()['id'];
    $params = [
      'contribution_id' => $orderID,
      'total_amount' => 100.00,
    ];
    $this->callAPISuccess('Payment', 'create', $params);
    $totalPaymentAmount = CRM_Core_BAO_FinancialTrxn::getTotalPayments($orderID);
    $this->assertEquals('250.00', $totalPaymentAmount, 'Amount does not match.');
  }

  /**
   * Test for createDeferredTrxn().
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreateDeferredTrxn() {
    Civi::settings()->set('deferred_revenue_enabled', TRUE);
    $cid = $this->individualCreate();
    $params = [
      'contact_id' => $cid,
      'receive_date' => '2016-01-20',
      'total_amount' => 622,
      'financial_type_id' => 4,
      'contribution_status_id' => 'Pending',
      'api.Payment.create' => ['total_amount' => 622, 'trxn_date' => '2016-01-20'],
    ];
    $contribution = $this->callAPISuccess('Order', 'create', $params);
    $lineItems[1] = CRM_Price_BAO_LineItem::getLineItemsByContributionID($contribution['id']);
    $lineItemId = key($lineItems[1]);
    $lineItems[1][$lineItemId]['financial_item_id'] = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_financial_item WHERE entity_table = 'civicrm_line_item' AND entity_id = {$lineItemId}");

    $trxn = $this->callAPISuccess('FinancialTrxn', 'get', ['total_amount' => 622]);
    $this->assertEquals(date('Ymd', strtotime('2016-01-20')), date('Ymd', strtotime($trxn['values'][$trxn['id']]['trxn_date'])));

    $contributionObj = $this->getContributionObject($contribution['id']);
    $contributionObj->revenue_recognition_date = date('Ymd', strtotime('+1 month'));
    CRM_Core_BAO_FinancialTrxn::createDeferredTrxn($lineItems, $contributionObj);
    $trxn = $this->callAPISuccess('FinancialTrxn', 'get', ['total_amount' => 622, 'id' => ['NOT IN' => [$trxn['id']]]]);

    $this->assertEquals(date('Ymd', strtotime($trxn['values'][$trxn['id']]['trxn_date'])), date('Ymd', strtotime('+1 month')));
  }

  /**
   * Test for updateCreditCardDetails().
   *
   * @throws \CRM_Core_Exception
   */
  public function testUpdateCreditCardDetailsUsingContributionAPI() {
    $cid = $this->individualCreate();
    $params = [
      'contact_id' => $cid,
      'receive_date' => '2016-01-20',
      'total_amount' => 100,
      'financial_type_id' => 1,
    ];
    $contribution = $this->callAPISuccess('Contribution', 'create', $params);
    $lastFinancialTrxnId = CRM_Core_BAO_FinancialTrxn::getFinancialTrxnId($contribution['id'], 'DESC');
    $financialTrxn = $this->callAPISuccessGetSingle(
      'FinancialTrxn',
      [
        'id' => $lastFinancialTrxnId['financialTrxnId'],
        'return' => ['card_type_id', 'pan_truncation'],
      ]
    );
    $this->assertEquals(CRM_Utils_Array::value('card_type_id', $financialTrxn), NULL);
    $this->assertEquals(CRM_Utils_Array::value('pan_truncation', $financialTrxn), NULL);
    $params = [
      'card_type_id' => 2,
      'pan_truncation' => 4567,
      'id' => $contribution['id'],
    ];
    $this->callAPISuccess('Contribution', 'create', $params);
    $financialTrxn = $this->callAPISuccessGetSingle(
      'FinancialTrxn',
      [
        'id' => $lastFinancialTrxnId['financialTrxnId'],
        'return' => ['card_type_id', 'pan_truncation'],
      ]
    );
    $this->assertEquals($financialTrxn['card_type_id'], 2);
    $this->assertEquals($financialTrxn['pan_truncation'], 4567);
  }

  /**
   * Test for updateCreditCardDetails().
   *
   * @throws \CRM_Core_Exception
   */
  public function testUpdateCreditCardDetails() {
    $cid = $this->individualCreate();
    $params = [
      'contact_id' => $cid,
      'receive_date' => '2016-01-20',
      'total_amount' => 100,
      'financial_type_id' => 1,
    ];
    $contribution = $this->callAPISuccess('Contribution', 'create', $params);
    $lastFinancialTrxnId = CRM_Core_BAO_FinancialTrxn::getFinancialTrxnId($contribution['id'], 'DESC');
    $financialTrxn = $this->callAPISuccessGetSingle(
      'FinancialTrxn',
      [
        'id' => $lastFinancialTrxnId['financialTrxnId'],
        'return' => ['card_type_id', 'pan_truncation'],
      ]
    );
    $this->assertEquals(CRM_Utils_Array::value('card_type_id', $financialTrxn), NULL);
    $this->assertEquals(CRM_Utils_Array::value('pan_truncation', $financialTrxn), NULL);
    CRM_Core_BAO_FinancialTrxn::updateCreditCardDetails($contribution['id'], 4567, 2);
    $financialTrxn = $this->callAPISuccessGetSingle(
      'FinancialTrxn',
      [
        'id' => $lastFinancialTrxnId['financialTrxnId'],
        'return' => ['card_type_id', 'pan_truncation'],
      ]
    );
    $this->assertEquals($financialTrxn['card_type_id'], 2);
    $this->assertEquals($financialTrxn['pan_truncation'], 4567);
  }

  /**
   * Test testGetContributionBalance function.
   *
   * @throws \CRM_Core_Exception
   */
  public function testGetContributionBalance() {
    //create the contribution that isn't paid yet
    $contactId = $this->individualCreate();
    $params = [
      'contact_id' => $contactId,
      'currency' => 'USD',
      'financial_type_id' => 1,
      'contribution_status_id' => 'Pending',
      'payment_instrument_id' => 4,
      'total_amount' => 300.00,
      'fee_amount' => 0.00,
      'net_amount' => 300.00,
    ];
    $contribution = $this->callAPISuccess('Contribution', 'create', $params);
    //make a payment one cent short
    $params = [
      'contribution_id' => $contribution['id'],
      'total_amount' => 299.99,
    ];
    $this->callAPISuccess('Payment', 'create', $params);
    //amount owed should be one cent
    $amountOwed = CRM_Contribute_BAO_Contribution::getContributionBalance($contribution['id']);
    $this->assertEquals(0.01, $amountOwed, 'Amount does not match');
  }

  /**
   * Test that financial trxns for fee amounts have the correct financial account.
   *
   * @throws \CRM_Core_Exception
   */
  public function testFeeAmountTrxns() {
    $contactId = $this->individualCreate();

    // Get the expected financial account of a payment made with a credit card.
    // Also et the financial account of a payment with the default payment method.
    // I wish we could join here - maybe when API4 EntityBridge is complete.
    $creditCardOvId = $this->callAPISuccess('OptionValue', 'getvalue', [
      'return' => "id",
      'option_group_id' => "payment_instrument",
      'name' => "Credit Card",
    ]);
    $defaultPaymentMethodOvId = $this->callAPISuccess('OptionValue', 'getvalue', [
      'return' => "id",
      'option_group_id' => "payment_instrument",
      'is_default' => 1,
    ]);
    $expectedFinancialAccountId = $this->callAPISuccess('EntityFinancialAccount', 'getvalue', [
      'return' => "financial_account_id",
      'entity_id' => $creditCardOvId,
    ]);
    $wrongFinancialAccountId = $this->callAPISuccess('EntityFinancialAccount', 'getvalue', [
      'return' => "financial_account_id",
      'entity_id' => $defaultPaymentMethodOvId,
    ]);
    // If these two are the same, there's no bug but this test is no longer valid and needs rewriting.
    $this->assertNotEquals($expectedFinancialAccountId, $wrongFinancialAccountId, 'invalid test: Financial Account ID of credit card matches default payment method Financial Account ID');

    // Create a credit card contribution with a fee amount.
    $price = 100;
    $cParams = [
      'contact_id' => $contactId,
      'total_amount' => $price,
      'financial_type_id' => 1,
      'is_active' => 1,
      'payment_instrument_id' => 'Credit Card',
      'fee_amount' => 3,
    ];
    $contribution = $this->callAPISuccess('Contribution', 'create', $cParams);
    // Confirm that the from_financial_account_id amount on the fee trxn matches the expected value.
    $trxnParams = [
      'sequential' => 1,
      'return' => ["financial_trxn_id.from_financial_account_id"],
      'entity_table' => "civicrm_contribution",
      'entity_id' => $contribution['id'],
      'financial_trxn_id.total_amount' => 3,
    ];
    $firstFeeTrxnFromAccountId = $this->callAPISuccess('EntityFinancialTrxn', 'get', $trxnParams)['values'][0]['financial_trxn_id.from_financial_account_id'];
    $this->assertEquals($expectedFinancialAccountId, $firstFeeTrxnFromAccountId);

    // dev/financial#160 - ensure the from_financial_account_id is correct on a trxn generated by a contribution edit.
    $updatedContributionParams = [
      'id' => $contribution['id'],
      'fee_amount' => 5,
    ];
    $this->callAPISuccess('Contribution', 'create', $updatedContributionParams);

    // 2 = 5 - 3.
    $trxnParams['financial_trxn_id.total_amount'] = 2;
    $secondFeeTrxnFromAccountId = $this->callAPISuccess('EntityFinancialTrxn', 'get', $trxnParams)['values'][0]['financial_trxn_id.from_financial_account_id'];
    $this->assertEquals($expectedFinancialAccountId, $secondFeeTrxnFromAccountId);
  }

}
