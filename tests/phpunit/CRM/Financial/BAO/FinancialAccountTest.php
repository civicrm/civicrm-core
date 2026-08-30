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
use Civi\Api4\EntityFinancialAccount;
use Civi\Api4\FinancialAccount;
use Civi\Api4\FinancialItem;
use Civi\Api4\FinancialTrxn;
use Civi\Api4\FinancialType;
use Civi\Api4\LineItem;
use Civi\Core\HookInterface;

/**
 * Class CRM_Financial_BAO_FinancialAccountTest
 * @group headless
 */
class CRM_Financial_BAO_FinancialAccountTest extends CiviUnitTestCase implements HookInterface {

  public function setUp(): void {
    parent::setUp();
    $this->useTransaction(TRUE);
    $this->organizationCreate();
  }

  /**
   * Check method add()
   */
  public function testAdd(): void {
    $params = [
      'name' => 'Donations',
      'is_deductible' => 0,
      'is_active' => 1,
    ];
    $financialAccount = CRM_Financial_BAO_FinancialAccount::writeRecord($params);

    $result = $this->assertDBNotNull(
      'CRM_Financial_BAO_FinancialAccount',
      $financialAccount->id,
      'name',
      'id',
      'Database check on updated financial type record.'
    );

    $this->assertEquals($result, 'Donations', 'Verify financial type name.');
  }

  /**
   * Check method retrive()
   */
  public function testRetrieve(): void {
    $params = [
      'name' => 'Donations',
      'is_deductible' => 0,
      'is_active' => 1,
    ];
    $defaults = [];
    CRM_Financial_BAO_FinancialAccount::writeRecord($params);

    $result = CRM_Financial_BAO_FinancialAccount::retrieve($params, $defaults);

    $this->assertEquals($result->name, 'Donations', 'Verify financial account name.');
  }

  /**
   * Check method del()
   *
   * @throws \CRM_Core_Exception
   */
  public function testDel(): void {
    $params = [
      'name' => 'Donations',
      'is_deductible' => 0,
      'is_active' => 1,
    ];
    $financialAccount = CRM_Financial_BAO_FinancialAccount::writeRecord($params);

    CRM_Financial_BAO_FinancialAccount::del($financialAccount->id);
    $params = ['id' => $financialAccount->id];
    $result = CRM_Financial_BAO_FinancialAccount::retrieve($params);
    $this->assertEmpty($result, 'Verify financial account record deletion.');
  }

  /**
   * Check delete fails if a related contribution exists.
   *
   * @throws \CRM_Core_Exception
   */
  public function testDeleteIfHasContribution(): void {
    $financialType = FinancialType::create(FALSE)->setValues([
      'name' => 'Donation Test',
      'is_reserved' => 1,
    ])->execute()->first();

    $financialAccount = FinancialAccount::get(FALSE)->setWhere([
      ['name', '=', 'Donation Test'],
      ['is_active', '=', TRUE],
    ])->setSelect(['id'])->execute()->first();

    $contactId = $this->individualCreate();
    $contributionParams = [
      'total_amount' => 300,
      'currency' => 'USD',
      'contact_id' => $contactId,
      'financial_type_id' => $financialType['id'],
      'contribution_status_id' => 1,
    ];
    $this->callAPISuccess('Contribution', 'create', $contributionParams);
    CRM_Financial_BAO_FinancialAccount::del($financialAccount['id']);

    $this->assertCount(1, FinancialAccount::get(FALSE)->setWhere([
      ['id', '=', $financialAccount['id']],
    ])->selectRowCount()->execute(), 'Financial account should not be deleted as it is in use.');
  }

  /**
   * Check method getAccountingCode()
   */
  public function testGetAccountingCode(): void {
    $params = [
      'name' => 'Donations',
      'is_active' => 1,
      'is_reserved' => 0,
    ];

    $financialType = CRM_Financial_BAO_FinancialType::writeRecord($params);
    $financialAccountid = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialAccount', 'Donations', 'id', 'name');
    CRM_Core_DAO::setFieldValue('CRM_Financial_DAO_FinancialAccount', $financialAccountid, 'accounting_code', '4800');
    $accountingCode = CRM_Financial_BAO_FinancialAccount::getAccountingCode($financialType->id);
    $this->assertEquals($accountingCode, 4800, 'Verify accounting code.');
  }

  /**
   * Test getting financial account for a given financial Type with a particular relationship.
   */
  public function testGetFinancialAccountByFinancialTypeAndRelationshipBuiltIn(): void {
    $this->assertEquals(2, CRM_Financial_BAO_FinancialAccount::getFinancialAccountForFinancialTypeByRelationship(2, 'Income Account is'));
  }

  /**
   * Test getting financial account for a given financial Type with a particular relationship with label changed.
   */
  public function testGetFinancialAccountByFinancialTypeAndRelationshipBuiltInLabel(): void {
    // change the label
    $optionValue = $this->callAPISuccess('OptionValue', 'get', [
      'option_group_id' => 'account_relationship',
      'name' => 'Income Account is',
    ]);
    $this->callAPISuccess('OptionValue', 'create', [
      'id' => $optionValue['id'],
      'label' => 'Changed label',
    ]);
    // run test
    $this->assertEquals(2, CRM_Financial_BAO_FinancialAccount::getFinancialAccountForFinancialTypeByRelationship(2, 'Income Account is'));
    // restore label
    $this->callAPISuccess('OptionValue', 'create', [
      'id' => $optionValue['id'],
      'label' => 'Income Account is',
    ]);
  }

  /**
   * Test getting financial account for a given financial Type with a particular relationship.
   */
  public function testGetFinancialAccountByFinancialTypeAndRelationshipBuiltInRefunded(): void {
    $this->assertEquals(2, CRM_Financial_BAO_FinancialAccount::getFinancialAccountForFinancialTypeByRelationship(2, 'Credit/Contra Revenue Account is'));
  }

  /**
   * Test getting financial account for a given financial Type with a particular relationship with label changed.
   */
  public function testGetFinancialAccountByFinancialTypeAndRelationshipBuiltInRefundedLabel(): void {
    // change the label
    $optionValue = $this->callAPISuccess('OptionValue', 'get', [
      'option_group_id' => 'account_relationship',
      'name' => 'Credit/Contra Revenue Account is',
    ]);
    $this->callAPISuccess('OptionValue', 'create', [
      'id' => $optionValue['id'],
      'label' => 'Changed label',
    ]);
    // run test
    $this->assertEquals(2, CRM_Financial_BAO_FinancialAccount::getFinancialAccountForFinancialTypeByRelationship(2, 'Credit/Contra Revenue Account is'));
    // restore label
    $this->callAPISuccess('OptionValue', 'create', [
      'id' => $optionValue['id'],
      'label' => 'Credit/Contra Revenue Account is',
    ]);
  }

  /**
   * Test getting financial account for a given financial Type with a particular relationship.
   */
  public function testGetFinancialAccountByFinancialTypeAndRelationshipBuiltInChargeBack(): void {
    $this->assertEquals(2, CRM_Financial_BAO_FinancialAccount::getFinancialAccountForFinancialTypeByRelationship(2, 'Chargeback Account is'));
  }

  /**
   * Test getting financial account for a given financial Type with a particular relationship with label changed.
   */
  public function testGetFinancialAccountByFinancialTypeAndRelationshipBuiltInChargeBackLabel(): void {
    // change the label
    $optionValue = $this->callAPISuccess('OptionValue', 'get', [
      'option_group_id' => 'account_relationship',
      'name' => 'Chargeback Account is',
    ]);
    $this->callAPISuccess('OptionValue', 'create', [
      'id' => $optionValue['id'],
      'label' => 'Changed label',
    ]);
    // run test
    $this->assertEquals(2, CRM_Financial_BAO_FinancialAccount::getFinancialAccountForFinancialTypeByRelationship(2, 'Chargeback Account is'));
    // restore label
    $this->callAPISuccess('OptionValue', 'create', [
      'id' => $optionValue['id'],
      'label' => 'Chargeback Account is',
    ]);
  }

  /**
   * Test getting financial account for a given financial Type with a particular relationship.
   */
  public function testGetFinancialAccountByFinancialTypeAndRelationshipCustomAddedRefunded(): void {
    $financialAccount = $this->callAPISuccess('FinancialAccount', 'create', [
      'name' => 'Refund Account',
      'is_active' => TRUE,
    ]);

    $this->callAPISuccess('EntityFinancialAccount', 'create', [
      'entity_id' => 2,
      'entity_table' => 'civicrm_financial_type',
      'account_relationship' => 'Credit/Contra Revenue Account is',
      'financial_account_id' => 'Refund Account',
    ]);
    $this->assertEquals($financialAccount['id'],
      CRM_Financial_BAO_FinancialAccount::getFinancialAccountForFinancialTypeByRelationship(2, 'Credit/Contra Revenue Account is'));
  }

  /**
   * Test getting financial account relations for a given financial type.
   */
  public function testGetFinancialAccountRelations(): void {
    $fAccounts = $rAccounts = [];
    $relations = CRM_Financial_BAO_FinancialAccount::getfinancialAccountRelations();
    $links = [
      'Expense Account is' => 'Expenses',
      'Accounts Receivable Account is' => 'Asset',
      'Income Account is' => 'Revenue',
      'Asset Account is' => 'Asset',
      'Cost of Sales Account is' => 'Cost of Sales',
      'Premiums Inventory Account is' => 'Asset',
      'Discounts Account is' => 'Revenue',
      'Sales Tax Account is' => 'Liability',
      'Deferred Revenue Account is' => 'Liability',
      'Accounts Payable Account is' => 'Liability',
    ];
    $dao = CRM_Core_DAO::executeQuery("SELECT ov.value, ov.name
      FROM civicrm_option_value ov
      INNER JOIN civicrm_option_group og ON og.id = ov.option_group_id
      AND og.name = 'financial_account_type'");
    while ($dao->fetch()) {
      $fAccounts[$dao->value] = $dao->name;
    }
    $dao = CRM_Core_DAO::executeQuery("SELECT ov.value, ov.name
      FROM civicrm_option_value ov
      INNER JOIN civicrm_option_group og ON og.id = ov.option_group_id
      AND og.name = 'account_relationship'");
    while ($dao->fetch()) {
      $rAccounts[$dao->value] = $dao->name;
    }
    foreach ($links as $accountRelation => $accountType) {
      $financialAccountLinks[array_search($accountRelation, $rAccounts)] = array_search($accountType, $fAccounts);
    }
    $this->assertTrue(($relations == $financialAccountLinks), "The two arrays are not the same");
  }

  /**
   * Test getting deferred financial type.
   */
  public function testGetDeferredFinancialType(): void {
    $result = $this->_createDeferredFinancialAccount();
    $financialTypes = CRM_Financial_BAO_FinancialAccount::getDeferredFinancialType();
    $this->assertTrue(array_key_exists($result, $financialTypes), "The financial type created does not have a deferred account relationship");
  }

  /**
   * Test getting financial account for a given financial Type with a particular relationship.
   */
  public function testValidateFinancialAccount(): void {
    // Create a record with financial item having financial account as Event Fee.
    $this->createPartiallyPaidParticipantOrder();
    $financialAccounts = CRM_Contribute_PseudoConstant::financialAccount();
    $financialAccountId = array_search('Event Fee', $financialAccounts);
    $message = CRM_Financial_BAO_FinancialAccount::validateFinancialAccount($financialAccountId);
    $this->assertTrue($message, "The financial account cannot be deleted. Failed asserting this was true.");
    $financialAccountId = array_search('Member Dues', $financialAccounts);
    $message = CRM_Financial_BAO_FinancialAccount::validateFinancialAccount($financialAccountId);
    $this->assertFalse($message, "The financial account can be deleted. Failed asserting this was true.");
  }

  /**
   * Test for validating financial type has deferred revenue account relationship.
   *
   * @throws \CRM_Core_Exception
   */
  public function testcheckFinancialTypeHasDeferred(): void {
    Civi::settings()->set('deferred_revenue_enabled', TRUE);
    $params = [];
    $valid = CRM_Financial_BAO_FinancialAccount::checkFinancialTypeHasDeferred($params);
    $this->assertFalse($valid, "This should have been false");
    $cid = $this->individualCreate();
    $params = [
      'contact_id' => $cid,
      'receive_date' => '2016-01-20',
      'total_amount' => 100,
      'financial_type_id' => 4,
      'revenue_recognition_date' => date('Ymd', strtotime("+1 month")),
      'line_items' => [
        [
          'line_item' => [
            [
              'entity_table' => 'civicrm_contribution',
              'price_field_id' => 8,
              'price_field_value_id' => 16,
              'label' => 'test 1',
              'qty' => 1,
              'unit_price' => 100,
              'line_total' => 100,
              'financial_type_id' => 4,
            ],
            [
              'entity_table' => 'civicrm_contribution',
              'price_field_id' => 8,
              'price_field_value_id' => 17,
              'label' => 'Test 2',
              'qty' => 1,
              'unit_price' => 200,
              'line_total' => 200,
              'financial_type_id' => 4,
            ],
          ],
        ],
      ],
    ];
    try {
      CRM_Financial_BAO_FinancialAccount::checkFinancialTypeHasDeferred($params);
    }
    catch (CRM_Core_Exception $e) {
      $this->fail("Missed expected exception");
    }
    $params = [
      'contact_id' => $cid,
      'receive_date' => '2016-01-20',
      'total_amount' => 100,
      'financial_type_id' => 1,
      'revenue_recognition_date' => date('Ymd', strtotime("+1 month")),
    ];
    try {
      CRM_Financial_BAO_FinancialAccount::checkFinancialTypeHasDeferred($params);
      $this->fail("Missed expected exception");
    }
    catch (CRM_Core_Exception $e) {
      $this->assertEquals('Revenue Recognition Date cannot be processed unless there is a Deferred Revenue account setup for the Financial Type. Please remove Revenue Recognition Date, select a different Financial Type with a Deferred Revenue account setup for it, or setup a Deferred Revenue account for this Financial Type.', $e->getMessage());
    }
  }

  /**
   * Test testGetAllDeferredFinancialAccount.
   */
  public function testGetAllDeferredFinancialAccount(): void {
    $financialAccount = CRM_Financial_BAO_FinancialAccount::getAllDeferredFinancialAccount();
    // The two deferred financial accounts which are created by default.
    $expected = [
      "Deferred Revenue - Member Dues (2740)",
      "Deferred Revenue - Event Fee (2730)",
    ];
    $this->assertEquals(array_count_values($expected), array_count_values($financialAccount), "The two arrays are not the same");
    $this->_createDeferredFinancialAccount();
    $financialAccount = CRM_Financial_BAO_FinancialAccount::getAllDeferredFinancialAccount();
    $expected[] = "TestFinancialAccount_1 (4800)";
    $this->assertEquals(array_count_values($expected), array_count_values($financialAccount), "The two arrays are not the same");
  }

  /**
   * CRM-20037: Test balance due amount, if contribution is done using deferred Financial Type
   */
  public function testBalanceDueIfDeferredRevenueEnabled(): void {
    Civi::settings()->set('deferred_revenue_enabled', TRUE);
    $deferredFinancialTypeID = $this->_createDeferredFinancialAccount();

    $totalAmount = 100.00;
    $contribution = $this->callAPISuccess('Contribution', 'create', [
      'contact_id' => $this->individualCreate(),
      'receive_date' => '20120511',
      'total_amount' => $totalAmount,
      'financial_type_id' => $deferredFinancialTypeID,
      'non_deductible_amount' => 10.00,
      'fee_amount' => 5.00,
      'net_amount' => 95.00,
      'source' => 'SSF',
      'contribution_status_id' => 1,
    ]);
    $balance = CRM_Contribute_BAO_Contribution::getContributionBalance($contribution['id'], $totalAmount);
    $this->assertEquals(0.0, $balance);
    Civi::settings()->set('deferred_revenue_enabled', FALSE);
  }

  /**
   * Helper function to create deferred financial account.
   */
  public function _createDeferredFinancialAccount() {
    $params = [
      'name' => 'TestFinancialAccount_1',
      'accounting_code' => 4800,
      'contact_id' => 1,
      'is_deductible' => 0,
      'is_active' => 1,
      'is_reserved' => 0,
    ];

    $financialAccount = $this->callAPISuccess('FinancialAccount', 'create', $params);
    $params['name'] = 'test_financialType1';
    $financialType = $this->callAPISuccess('FinancialType', 'create', $params);
    $relationTypeId = key(CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Deferred Revenue Account is' "));
    $financialParams = [
      'entity_table' => 'civicrm_financial_type',
      'entity_id' => $financialType['id'],
      'account_relationship' => $relationTypeId,
      'financial_account_id' => $financialAccount['id'],
    ];

    $this->callAPISuccess('EntityFinancialAccount', 'create', $financialParams);
    $result = $this->assertDBNotNull(
      'CRM_Financial_DAO_EntityFinancialAccount',
      $financialAccount['id'],
      'entity_id',
      'financial_account_id',
      'Database check on added financial type record.'
    );
    $this->assertEquals($result, $financialType['id'], 'Verify Account Type');
    return $result;
  }

  public function testAccountsPayableRecorded(): void {
    \Civi::dispatcher()->addListener('hook_civicrm_pre', [__CLASS__, 'preHook'], 100);
    $financialType = FinancialType::create(FALSE)->setValues([
      'name' => 'Donation Test',
      'is_reserved' => 1,
    ])->execute()->first();

    $financialAccount = FinancialAccount::get(FALSE)->setWhere([
      ['name', '=', 'Donation Test'],
      ['is_active', '=', TRUE],
    ])->setSelect(['id'])->execute()->first();

    $contactId = $this->individualCreate();
    $financialAccount = FinancialAccount::create(FALSE)
      ->addValue('name', 'dontion_test_accounts_payable')
      ->addValue('label', 'Donation Test Accounts Pyable')
      ->addValue('contact_id', $contactId)
      ->addValue('financial_account_type_id:name', 'Liability')
      ->execute();

    EntityFinancialAccount::create(FALSE)
      ->addValue('entity_id', $financialType['id'])
      ->addValue('entity_table', 'civicrm_financial_type')
      ->addValue('financial_account_id', $financialAccount[0]['id'])
      ->addValue('account_relationship:name', 'Accounts Payable Account is')
      ->execute();

    $contributionParams = [
      'total_amount' => 300,
      'currency' => 'USD',
      'contact_id' => $contactId,
      'financial_type_id' => $financialType['id'],
      'contribution_status_id' => 1,
    ];
    $contribution = $this->callAPISuccess('Contribution', 'create', $contributionParams);
    $this->callAPISuccess('Contribution', 'create', [
      'id' => $contribution['id'],
      'contribution_status_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Pending refund'),
    ]);
    $financialTrxns = FinancialTrxn::get(FALSE)
      ->addWhere('to_financial_account_id', '=', $financialAccount[0]['id'])
      ->execute();
    $this->assertCount(1, $financialTrxns);
  }

  /**
   * Set up a reserved financial type together with a Liability account
   * configured as its 'Accounts Payable Account is' relationship.
   *
   * @return array{financialTypeID: int, incomeAccountID: int, payableAccountID: int, contactID: int}
   */
  private function createFinancialTypeWithAccountsPayable(string $name): array {
    $financialType = FinancialType::create(FALSE)->setValues([
      'name' => $name,
      'is_reserved' => 1,
    ])->execute()->first();

    $incomeAccount = FinancialAccount::get(FALSE)
      ->addWhere('name', '=', $name)
      ->addWhere('is_active', '=', TRUE)
      ->execute()->single();

    $contactId = $this->individualCreate();
    $payableAccount = FinancialAccount::create(FALSE)
      ->addValue('name', $name . '_payable')
      ->addValue('label', $name . ' Payable')
      ->addValue('contact_id', $contactId)
      ->addValue('financial_account_type_id:name', 'Liability')
      ->execute()->single();

    EntityFinancialAccount::create(FALSE)
      ->addValue('entity_id', $financialType['id'])
      ->addValue('entity_table', 'civicrm_financial_type')
      ->addValue('financial_account_id', $payableAccount['id'])
      ->addValue('account_relationship:name', 'Accounts Payable Account is')
      ->execute();

    return [
      'financialTypeID' => $financialType['id'],
      'incomeAccountID' => $incomeAccount['id'],
      'payableAccountID' => $payableAccount['id'],
      'contactID' => $contactId,
    ];
  }

  /**
   * Where a financial type has an Accounts Payable account configured, a line
   * item using it is money being held to pay back out, not income - so it
   * should be recorded against Accounts Payable from the moment it's created,
   * not just once a refund is under way.
   */
  public function testLineItemUsesAccountsPayableAccountWhenConfigured(): void {
    $setup = $this->createFinancialTypeWithAccountsPayable('Line Item AP Test');

    $contribution = $this->callAPISuccess('Contribution', 'create', [
      'total_amount' => 300,
      'currency' => 'USD',
      'contact_id' => $setup['contactID'],
      'financial_type_id' => $setup['financialTypeID'],
      'contribution_status_id' => 'Completed',
    ]);
    $lineItem = LineItem::get(FALSE)
      ->addWhere('contribution_id', '=', $contribution['id'])
      ->execute()->single();

    $this->assertEquals($setup['payableAccountID'], $this->getLatestFinancialItemAccountID($lineItem['id']), 'A line item on a financial type with Accounts Payable configured should never be recorded as income.');
  }

  /**
   * Once money held in Accounts Payable is paid back out - a refund, in
   * CiviCRM's terms - the financial item should stay against Accounts
   * Payable. There is no income to reverse, so it should not be routed
   * through the Credit/Contra Revenue account the way a normal refund is.
   */
  public function testAccountsPayableLineItemStaysInAccountsPayableWhenRefunded(): void {
    $setup = $this->createFinancialTypeWithAccountsPayable('Refund AP Test');

    $contributionParams = [
      'total_amount' => 300,
      'currency' => 'USD',
      'contact_id' => $setup['contactID'],
      'financial_type_id' => $setup['financialTypeID'],
      'contribution_status_id' => 'Completed',
      'trxn_id' => 'original_payment_ap_test',
    ];
    $contribution = $this->callAPISuccess('Contribution', 'create', $contributionParams);
    $lineItem = LineItem::get(FALSE)
      ->addWhere('contribution_id', '=', $contribution['id'])
      ->execute()->single();

    $this->assertEquals($setup['payableAccountID'], $this->getLatestFinancialItemAccountID($lineItem['id']));

    $this->callAPISuccess('Contribution', 'create', array_merge($contributionParams, [
      'id' => $contribution['id'],
      'contribution_status_id' => 'Refunded',
      'cancel_date' => date('Y-m-d'),
      'refund_trxn_id' => 'the_refund_ap_test',
    ]));

    $this->assertEquals($setup['payableAccountID'], $this->getLatestFinancialItemAccountID($lineItem['id']), 'A refund on an Accounts Payable line item should stay in Accounts Payable, not move to a revenue account.');
  }

  /**
   * CiviCRM's "Record Refund" screen, and most payment-processor refund
   * webhooks, record a refund via Payment.create rather than by directly
   * changing the contribution's status. That code path never re-classifies
   * an existing financial item - it just links the refund transaction to it
   * - so an Accounts Payable line item should simply stay there.
   */
  public function testAccountsPayableLineItemStaysInAccountsPayableWhenRefundedViaPaymentApi(): void {
    $setup = $this->createFinancialTypeWithAccountsPayable('Payment API Refund AP Test');

    $contribution = $this->callAPISuccess('Contribution', 'create', [
      'total_amount' => 300,
      'currency' => 'USD',
      'contact_id' => $setup['contactID'],
      'financial_type_id' => $setup['financialTypeID'],
      'contribution_status_id' => 'Completed',
      'trxn_id' => 'original_payment_api_ap_test',
    ]);
    $lineItem = LineItem::get(FALSE)
      ->addWhere('contribution_id', '=', $contribution['id'])
      ->execute()->single();

    $this->assertEquals($setup['payableAccountID'], $this->getLatestFinancialItemAccountID($lineItem['id']));

    $this->callAPISuccess('Payment', 'create', [
      'contribution_id' => $contribution['id'],
      'total_amount' => -300,
      'trxn_date' => date('YmdHis'),
    ]);

    $this->assertEquals('Refunded', CRM_Core_PseudoConstant::getName(
      'CRM_Contribute_BAO_Contribution',
      'contribution_status_id',
      $this->callAPISuccessGetValue('Contribution', ['id' => $contribution['id'], 'return' => 'contribution_status_id'])
    ), 'A full refund via Payment.create should move the contribution to Refunded.');
    $this->assertEquals($setup['payableAccountID'], $this->getLatestFinancialItemAccountID($lineItem['id']), 'A refund recorded via Payment.create should leave the Accounts Payable line item exactly where it was.');
  }

  /**
   * Where a financial type has no Accounts Payable account configured,
   * contributions using it should behave exactly as before: recorded to the
   * income account, and moved to the Credit/Contra Revenue account (which
   * falls back to income when not separately configured) on refund.
   */
  public function testNormalLineItemUnaffectedWithoutAccountsPayableConfigured(): void {
    $financialType = FinancialType::create(FALSE)->setValues([
      'name' => 'No AP Test',
      'is_reserved' => 1,
    ])->execute()->first();

    $incomeAccount = FinancialAccount::get(FALSE)
      ->addWhere('name', '=', 'No AP Test')
      ->addWhere('is_active', '=', TRUE)
      ->execute()->single();

    $contactId = $this->individualCreate();
    $contributionParams = [
      'total_amount' => 300,
      'currency' => 'USD',
      'contact_id' => $contactId,
      'financial_type_id' => $financialType['id'],
      'contribution_status_id' => 'Completed',
      'trxn_id' => 'original_payment_no_ap_test',
    ];
    $contribution = $this->callAPISuccess('Contribution', 'create', $contributionParams);
    $lineItem = LineItem::get(FALSE)
      ->addWhere('contribution_id', '=', $contribution['id'])
      ->execute()->single();

    $this->assertEquals($incomeAccount['id'], $this->getLatestFinancialItemAccountID($lineItem['id']), 'Completed contribution should be recorded to the income account.');

    $this->callAPISuccess('Contribution', 'create', array_merge($contributionParams, [
      'id' => $contribution['id'],
      'contribution_status_id' => 'Refunded',
      'cancel_date' => date('Y-m-d'),
      'refund_trxn_id' => 'the_refund_no_ap_test',
    ]));

    $this->assertEquals($incomeAccount['id'], $this->getLatestFinancialItemAccountID($lineItem['id']), 'Without an Accounts Payable account configured, a refund should behave as before and stay on the income account.');
  }

  /**
   * Changing a paid contribution's financial type to one with Accounts
   * Payable configured reverses the original (income) item and creates a new
   * one. The reversal should net out against the account the item was
   * actually on, and the replacement item should land on Accounts Payable,
   * not income.
   */
  public function testChangeFinancialTypeToAccountsPayableOnPaidContribution(): void {
    $setup = $this->createFinancialTypeWithAccountsPayable('Change To AP Test');
    $plainFinancialType = FinancialType::create(FALSE)->setValues([
      'name' => 'Change From Plain Test',
      'is_reserved' => 1,
    ])->execute()->first();
    $plainIncomeAccount = FinancialAccount::get(FALSE)
      ->addWhere('name', '=', 'Change From Plain Test')
      ->addWhere('is_active', '=', TRUE)
      ->execute()->single();

    $contribution = $this->callAPISuccess('Contribution', 'create', [
      'total_amount' => 300,
      'currency' => 'USD',
      'contact_id' => $setup['contactID'],
      'financial_type_id' => $plainFinancialType['id'],
      'contribution_status_id' => 'Completed',
      'trxn_id' => 'change_type_paid_test',
    ]);
    $lineItem = LineItem::get(FALSE)
      ->addWhere('contribution_id', '=', $contribution['id'])
      ->execute()->single();

    $this->callAPISuccess('Contribution', 'create', [
      'id' => $contribution['id'],
      'financial_type_id' => $setup['financialTypeID'],
    ]);

    $items = FinancialItem::get(FALSE)
      ->addWhere('entity_table', '=', 'civicrm_line_item')
      ->addWhere('entity_id', '=', $lineItem['id'])
      ->addOrderBy('id')
      ->addSelect('amount', 'financial_account_id')
      ->execute();
    $this->assertCount(3, $items, 'A financial type change should reverse the original item and create a new one.');
    $items = $items->getArrayCopy();
    $this->assertEquals([$plainIncomeAccount['id'], 300.0], [$items[1]['financial_account_id'], -$items[1]['amount']], 'The reversal should net out against the account the item was actually recorded on.');
    $this->assertEquals($setup['payableAccountID'], $items[2]['financial_account_id'], 'The replacement item should be recorded to Accounts Payable, not income.');
  }

  /**
   * The same financial type change, but before any payment has been applied
   * (the contribution is still Pending). This should not affect the Accounts
   * Payable classification - it's independent of the receivable/cash-side
   * handling that differs between paid and unpaid contributions.
   */
  public function testChangeFinancialTypeToAccountsPayableOnPendingContribution(): void {
    $setup = $this->createFinancialTypeWithAccountsPayable('Change To AP Pending Test');
    $plainFinancialType = FinancialType::create(FALSE)->setValues([
      'name' => 'Change From Plain Pending Test',
      'is_reserved' => 1,
    ])->execute()->first();

    $contribution = $this->callAPISuccess('Contribution', 'create', [
      'total_amount' => 300,
      'currency' => 'USD',
      'contact_id' => $setup['contactID'],
      'financial_type_id' => $plainFinancialType['id'],
      'contribution_status_id' => 'Pending',
      'is_pay_later' => 1,
    ]);
    $lineItem = LineItem::get(FALSE)
      ->addWhere('contribution_id', '=', $contribution['id'])
      ->execute()->single();

    $this->callAPISuccess('Contribution', 'create', [
      'id' => $contribution['id'],
      'financial_type_id' => $setup['financialTypeID'],
      // is_pay_later has to be repeated on every update to a pending pay-later
      // contribution, or CiviCRM treats it as an incomplete/pending update and
      // skips financial recording altogether.
      'is_pay_later' => 1,
    ]);

    $this->assertEquals($setup['payableAccountID'], $this->getLatestFinancialItemAccountID($lineItem['id']), 'A financial type change on an unpaid contribution should still land the item on Accounts Payable.');
  }

  /**
   * Get the financial_account_id of the most recently created financial item for a line item.
   */
  private function getLatestFinancialItemAccountID(int $lineItemID): int {
    return (int) FinancialItem::get(FALSE)
      ->addWhere('entity_table', '=', 'civicrm_line_item')
      ->addWhere('entity_id', '=', $lineItemID)
      ->addOrderBy('id', 'DESC')
      ->setLimit(1)
      ->execute()->single()['financial_account_id'];
  }

  public static function preHook(\Civi\Core\Event\PreEvent $event): void {
    // Record transaction to Accounts Payable account if we are planning to refund a contribution
    if ($event->entity === 'Contribution' && $event->action == 'edit') {
      $currentContributionRecord = Contribution::get(FALSE)
        ->addWhere('id', '=', $event->id)
        ->execute()->first();
      if ($event->params['contribution_status_id'] === CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Pending refund') &&
        !empty(\CRM_Financial_BAO_FinancialAccount::getFinancialAccountForFinancialTypeByRelationship($currentContributionRecord['financial_type_id'], 'Accounts Payable Account is'))) {
        $financialTrxnParams = [
          'is_payment' => 0,
          'entity_table' => 'civicrm_contribution',
          'entity_id' => $event->id,
          'total_amount' => $currentContributionRecord['total_amount'],
          'from_financial_account_id' => \CRM_Financial_BAO_FinancialAccount::getFinancialAccountForFinancialTypeByRelationship($currentContributionRecord['financial_type_id'], 'Income Account Is'),
          'to_financial_account_id' => \CRM_Financial_BAO_FinancialAccount::getFinancialAccountForFinancialTypeByRelationship($currentContributionRecord['financial_type_id'], 'Accounts Payable Account is'),
          'trxn_date' => date('YmdHis'),
        ];
        CRM_Financial_BAO_FinancialTrxn::create($financialTrxnParams);
      }
    }
  }

}
