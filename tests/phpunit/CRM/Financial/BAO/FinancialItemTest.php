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

use Civi\Api4\EntityFinancialTrxn;
use Civi\Api4\FinancialItem;

/**
 * Class CRM_Financial_BAO_FinancialItemTest
 * @group headless
 */
class CRM_Financial_BAO_FinancialItemTest extends CiviUnitTestCase {

  /**
   * Should financials be checked after the test but before tear down.
   *
   * This test class can't utilise the post check as the test deliberately
   * creates invalid financial items.
   *
   * @var bool
   */
  protected $isValidateFinancialsOnPostAssert = FALSE;

  /**
   * Clean up after each test.
   */
  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    parent::tearDown();
  }

  /**
   * Check method add()
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function testAdd(): void {
    $price = 100;

    $contribution = $this->callAPISuccess('Contribution', 'create', [
      'contact_id' => $this->individualCreate(),
      'total_amount' => $price,
      'financial_type_id' => 1,
      'is_active' => 1,
      'skipLineItem' => 1,
    ]);
    $lParams = [
      'entity_id' => $contribution['id'],
      'entity_table' => 'civicrm_contribution',
      'price_field_id' => 1,
      'qty' => 1,
      'label' => 'Contribution Amount',
      'unit_price' => $price,
      'line_total' => $price,
      'price_field_value_id' => 1,
      'financial_type_id' => 1,
    ];

    $lineItem = CRM_Price_BAO_LineItem::create($lParams);
    $contributionObj = $this->getContributionObject($contribution['id']);

    CRM_Financial_BAO_FinancialItem::add($lineItem, $contributionObj);
    $result = $this->assertDBNotNull(
      'CRM_Financial_DAO_FinancialItem',
      $lineItem->id,
      'amount',
      'entity_id',
      'Database check on added financial item record.'
    );
    $this->assertEquals($result, $price, 'Verify Amount for Financial Item');
  }

  /**
   * Check method retrieve()
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function testRetrieve(): void {
    $price = 100.00;

    $contribution = $this->callAPISuccess('Contribution', 'create', [
      'contact_id' => $this->individualCreate(),
      'total_amount' => $price,
      'financial_type_id' => 1,
      'is_active' => 1,
      'skipLineItem' => 1,
    ]);
    $lParams = [
      'entity_id' => $contribution['id'],
      'entity_table' => 'civicrm_contribution',
      'price_field_id' => 1,
      'qty' => 1,
      'label' => 'Contribution Amount',
      'unit_price' => $price,
      'line_total' => $price,
      'price_field_value_id' => 1,
      'financial_type_id' => 1,
    ];

    $contributionObj = $this->getContributionObject($contribution['id']);
    $lineItem = CRM_Price_BAO_LineItem::create($lParams);
    CRM_Financial_BAO_FinancialItem::add($lineItem, $contributionObj);
    $values = [];
    $fParams = [
      'entity_id' => $lineItem->id,
      'entity_table' => 'civicrm_line_item',
    ];
    $financialItem = CRM_Financial_BAO_FinancialItem::retrieve($fParams, $values);
    $this->assertEquals($financialItem->amount, $price, 'Verify financial item amount.');
  }

  /**
   * Check method create()
   *
   * @throws \API_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testCreate(): void {
    $contactID = $this->individualCreate();
    $price = 100.00;
    $cParams = [
      'contact_id' => $contactID,
      'total_amount' => $price,
      'financial_type_id' => 1,
      'is_active' => 1,
      'skipLineItem' => 1,
    ];

    $contribution = $this->callAPISuccess('Contribution', 'create', $cParams);
    $lParams = [
      'entity_id' => $contribution['id'],
      'entity_table' => 'civicrm_contribution',
      'price_field_id' => 1,
      'qty' => 1,
      'label' => 'Contribution Amount',
      'unit_price' => $price,
      'line_total' => $price,
      'price_field_value_id' => 1,
      'financial_type_id' => 1,
    ];

    $lineItem = CRM_Price_BAO_LineItem::create($lParams);
    $fParams = [
      'contact_id' => $contactID,
      'description' => 'Contribution Amount',
      'amount' => $price,
      'financial_account_id' => 1,
      'status_id' => 1,
      'transaction_date' => date('YmdHis'),
      'entity_id' => $lineItem->id,
      'entity_table' => 'civicrm_line_item',
    ];
    CRM_Financial_BAO_FinancialItem::create($fParams);

    $entityTrxn = EntityFinancialTrxn::get()
      ->addWhere('amount', '=', $price)
      ->addWhere('entity_id', '=', $contribution['id'])
      ->addWhere('entity_table', '=', 'civicrm_contribution')
      ->execute();
    $this->assertCount(1, $entityTrxn);

    $result = FinancialItem::get()
      ->addWhere('entity_id', '=', $lineItem->id)
      ->addWhere('amount', '=', $price)
      ->execute();
    $this->assertCount(1, $result);
  }

  /**
   * Check method del()
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreateEntityTrxn(): CRM_Financial_DAO_EntityFinancialTrxn {
    $fParams = [
      'name' => 'Donations',
      'is_deductible' => 0,
      'is_active' => 1,
    ];

    $amount = 200;
    $financialAccount = CRM_Financial_BAO_FinancialAccount::add($fParams);
    $financialTrxn = new CRM_Financial_DAO_FinancialTrxn();
    $financialTrxn->to_financial_account_id = $financialAccount->id;
    $financialTrxn->total_amount = $amount;
    $financialTrxn->save();
    $params = [
      'entity_table' => 'civicrm_contribution',
      'entity_id' => 1,
      'financial_trxn_id' => $financialTrxn->id,
      'amount' => $amount,
    ];

    $entityTrxn = CRM_Financial_BAO_FinancialItem::createEntityTrxn($params);
    $entityResult = $this->assertDBNotNull(
      'CRM_Financial_DAO_EntityFinancialTrxn',
      $financialTrxn->id,
      'amount',
      'financial_trxn_id',
      'Database check on added entity financial trxn record.'
    );
    $this->assertEquals($entityResult, $amount, 'Verify Amount for Financial Item');
    return $entityTrxn;
  }

  /**
   * Check method getPreviousFinancialItem().
   */
  public function testGetPreviousFinancialItem(): void {
    $contactId = $this->individualCreate();

    $params = [
      'contact_id' => $contactId,
      'currency' => 'USD',
      'financial_type_id' => 1,
      'contribution_status_id' => 1,
      'payment_instrument_id' => 1,
      'source' => 'STUDENT',
      'receive_date' => '20160522000000',
      'receipt_date' => '20160522000000',
      'non_deductible_amount' => 0.00,
      'total_amount' => 100.00,
      'trxn_id' => '22333444444',
      'invoice_id' => 'abc',
    ];

    $contribution = $this->callAPISuccess('Contribution', 'create', $params);

    $params = [
      'id' => $contribution['id'],
      'total_amount' => 300.00,
    ];

    $contribution = $this->callAPISuccess('Contribution', 'create', $params);
    $financialItem = CRM_Financial_BAO_FinancialItem::getPreviousFinancialItem($contribution['id']);
    $params = ['id' => $financialItem['id']];
    $financialItem = $this->callAPISuccess('FinancialItem', 'get', $params);
    $this->assertEquals(200.00, $financialItem['values'][$financialItem['id']]['amount'], 'The amounts do not match.');
  }

  /**
   * Check method getPreviousFinancialItem() with tax entry.
   *
   * @param string $thousandSeparator
   *   punctuation used to refer to thousands.
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\Payment\Exception\PaymentProcessorException
   *
   * @dataProvider getThousandSeparators
   */
  public function testGetPreviousFinancialItemHavingTax(string $thousandSeparator): void {
    $this->setCurrencySeparators($thousandSeparator);
    $contactId = $this->individualCreate();
    $this->enableTaxAndInvoicing();
    $this->addTaxAccountToFinancialType(1);
    $form = $this->getFormObject('CRM_Contribute_Form_Contribution', [
      'total_amount' => 100,
      'financial_type_id' => 1,
      'contact_id' => $contactId,
      'contribution_status_id' => 1,
      'price_set_id' => 0,
    ]);
    $form->postProcess();
    $contribution = $this->callAPISuccessGetSingle('Contribution',
      [
        'contact_id' => $contactId,
        'return' => ['id'],
      ]
    );
    $financialItem = CRM_Financial_BAO_FinancialItem::getPreviousFinancialItem($contribution['id']);
    $params = [
      'id' => $financialItem['id'],
      'return' => [
        'description',
        'status_id',
        'amount',
        'financial_account_id',
      ],
    ];
    $checkAgainst = [
      'id' => $financialItem['id'],
      'description' => 'Contribution Amount',
      'status_id' => '1',
      'amount' => '100.00',
      'financial_account_id' => '1',
    ];
    $this->callAPISuccessGetSingle('FinancialItem', $params, $checkAgainst);
  }

}
