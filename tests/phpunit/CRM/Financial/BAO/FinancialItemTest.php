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
 * Class CRM_Financial_BAO_FinancialItemTest
 * @group headless
 */
class CRM_Financial_BAO_FinancialItemTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
  }

  /**
   * Clean up after each test.
   */
  public function tearDown() {
    $this->quickCleanUpFinancialEntities();
    parent::tearDown();
  }

  /**
   * Check method add()
   */
  public function testAdd() {
    $params = [
      'first_name' => 'Shane',
      'last_name' => 'Whatson',
      'contact_type' => 'Individual',
    ];

    $contact = $this->callAPISuccess('Contact', 'create', $params);

    $price = 100;
    $cParams = [
      'contact_id' => $contact['id'],
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
   * Check method retrive()
   */
  public function testRetrieve() {
    $params = [
      'first_name' => 'Shane',
      'last_name' => 'Whatson',
      'contact_type' => 'Individual',
    ];

    $contact = $this->callAPISuccess('Contact', 'create', $params);
    $price = 100.00;
    $cParams = [
      'contact_id' => $contact['id'],
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
   */
  public function testCreate() {
    $params = [
      'first_name' => 'Shane',
      'last_name' => 'Whatson',
      'contact_type' => 'Individual',
    ];

    $contact = $this->callAPISuccess('Contact', 'create', $params);
    $price = 100.00;
    $cParams = [
      'contact_id' => $contact['id'],
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
      'contact_id' => $contact['id'],
      'description' => 'Contribution Amount',
      'amount' => $price,
      'financial_account_id' => 1,
      'status_id' => 1,
      'transaction_date' => date('YmdHis'),
      'entity_id' => $lineItem->id,
      'entity_table' => 'civicrm_line_item',
    ];

    CRM_Financial_BAO_FinancialItem::create($fParams);
    $entityTrxn = new CRM_Financial_DAO_EntityFinancialTrxn();
    $entityTrxn->entity_table = 'civicrm_contribution';
    $entityTrxn->entity_id = $contribution['id'];
    $entityTrxn->amount = $price;
    if ($entityTrxn->find(TRUE)) {
      $entityId = $entityTrxn->entity_id;
    }

    $result = $this->assertDBNotNull(
      'CRM_Financial_DAO_FinancialItem',
      $lineItem->id,
      'amount',
      'entity_id',
      'Database check on added financial item record.'
    );

    $this->assertEquals($result, $price, 'Verify Amount for Financial Item');
    $entityResult = $this->assertDBNotNull(
      'CRM_Financial_DAO_EntityFinancialTrxn',
      $entityId,
      'amount',
      'entity_id',
      'Database check on added entity financial trxn record.'
    );
    $this->assertEquals($entityResult, $price, 'Verify Amount for Financial Item');
  }

  /**
   * Check method del()
   */
  public function testCreateEntityTrxn() {
    $fParams = [
      'name' => 'Donations' . substr(sha1(rand()), 0, 7),
      'is_deductible' => 0,
      'is_active' => 1,
    ];

    $amount = 200;
    $ids = [];
    $financialAccount = CRM_Financial_BAO_FinancialAccount::add($fParams, $ids);
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
   * Check method retrieveEntityFinancialTrxn()
   */
  public function testRetrieveEntityFinancialTrxn() {
    $entityTrxn = self::testCreateEntityTrxn();
    $params = [
      'entity_table' => 'civicrm_contribution',
      'entity_id' => 1,
      'financial_trxn_id' => $entityTrxn->financial_trxn_id,
      'amount' => $entityTrxn->amount,
    ];

    CRM_Financial_BAO_FinancialItem::retrieveEntityFinancialTrxn($params);
    $entityResult = $this->assertDBNotNull(
      'CRM_Financial_DAO_EntityFinancialTrxn',
      $entityTrxn->financial_trxn_id,
      'amount',
      'financial_trxn_id',
      'Database check on added entity financial trxn record.'
    );
    $this->assertEquals($entityResult, $entityTrxn->amount, 'Verify Amount for Financial Item');
  }

  /**
   * Check method getPreviousFinancialItem().
   */
  public function testGetPreviousFinancialItem() {
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
      'trxn_id' => '22ereerwww444444',
      'invoice_id' => '86ed39c9e9ee6ef6031621ce0eafe7eb81',
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
    $this->assertEquals(200.00, $financialItem['values'][$financialItem['id']]['amount'], "The amounts do not match.");
  }

  /**
   * Check method getPreviousFinancialItem() with tax entry.
   *
   * @param string $thousandSeparator
   *   punctuation used to refer to thousands.
   *
   * @dataProvider getThousandSeparators
   */
  public function testGetPreviousFinancialItemHavingTax($thousandSeparator) {
    $this->setCurrencySeparators($thousandSeparator);
    $contactId = $this->individualCreate();
    $this->enableTaxAndInvoicing();
    $this->relationForFinancialTypeWithFinancialAccount(1);
    $form = new CRM_Contribute_Form_Contribution();
    $form->testSubmit([
      'total_amount' => 100,
      'financial_type_id' => 1,
      'contact_id' => $contactId,
      'contribution_status_id' => 1,
      'price_set_id' => 0,
    ], CRM_Core_Action::ADD);
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
