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

/**
 * Class api_v3_TaxContributionPageTest
 * @group headless
 */
class api_v3_TaxContributionPageTest extends CiviUnitTestCase {
  protected $params;
  protected $financialTypeID;
  protected $financialAccountId;
  protected $_entity = 'contribution_page';
  protected $_priceSetParams = [];
  protected $_paymentProcessorType;
  protected $payParams = [];
  protected $settingValue = [];
  protected $setInvoiceSettings;
  protected $_individualId;
  protected $financialAccHalftax;
  protected $financialtypeHalftax;
  protected $financialRelationHalftax;
  protected $halfFinancialAccId;
  protected $halfFinancialTypeId;

  protected $isValidateFinancialsOnPostAssert = FALSE;

  /**
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function setUp(): void {
    parent::setUp();
    $this->_individualId = $this->individualCreate();
    $this->_orgId = $this->organizationCreate(NULL);

    $this->ids['PaymentProcessor'] = $this->paymentProcessorCreate();
    $this->params = [
      'title' => 'Test Contribution Page',
      'financial_type_id' => 1,
      'payment_processor' => $this->ids['PaymentProcessor'],
      'currency' => 'NZD',
      'goal_amount' => 350,
      'is_pay_later' => 1,
      'pay_later_text' => 'I will pay later',
      'pay_later_receipt' => 'I will pay later',
      'is_monetary' => TRUE,
      'is_billing_required' => TRUE,
    ];

    $this->_priceSetParams = [
      'name' => 'tax_contribution',
      'title' => 'contribution tax',
      'is_active' => 1,
      'help_pre' => 'Where does your goat sleep',
      'help_post' => 'thank you for your time',
      'extends' => 2,
      'financial_type_id' => 3,
      'is_quick_config' => 0,
      'is_reserved' => 0,
    ];
    // Financial Account with 20% tax rate
    $financialAccountSetparams = [
      'name' => 'vat full tax rate account',
      'contact_id' => $this->_orgId,
      'financial_account_type_id' => 2,
      'is_tax' => 1,
      'tax_rate' => 20.00,
      'is_reserved' => 0,
      'is_active' => 1,
      'is_default' => 0,
    ];

    $financialAccount = $this->callAPISuccess('financial_account', 'create', $financialAccountSetparams);
    $this->financialAccountId = $financialAccount['id'];

    // Financial type having 'Sales Tax Account is' with liability financial account
    $this->financialTypeID = $this->callAPISuccess('FinancialType', 'create', [
      'name' => 'grass_variety_1',
      'is_reserved' => 0,
      'is_active' => 1,
    ])['id'];
    $financialRelationParams = [
      'entity_table' => 'civicrm_financial_type',
      'entity_id' => $this->financialTypeID,
      'account_relationship' => 10,
      'financial_account_id' => $this->financialAccountId,
    ];
    CRM_Financial_BAO_FinancialTypeAccount::add($financialRelationParams);

    // Financial type with 5% tax rate
    $financialAccountHalfTax = [
      'name' => 'vat half tax_rate account',
      'contact_id' => $this->_orgId,
      'financial_account_type_id' => 2,
      'is_tax' => 1,
      'tax_rate' => 5.00,
      'is_reserved' => 0,
      'is_active' => 1,
      'is_default' => 0,
    ];
    $halfFinancialAccount = CRM_Financial_BAO_FinancialAccount::add($financialAccountHalfTax);
    $this->halfFinancialAccId = $halfFinancialAccount->id;
    $halfFinancialTypeHalfTax = [
      'name' => 'grass_variety_2',
      'is_reserved' => 0,
      'is_active' => 1,
    ];

    $halfFinancialType = CRM_Financial_BAO_FinancialType::add($halfFinancialTypeHalfTax);
    $this->halfFinancialTypeId = $halfFinancialType->id;
    $financialRelationHalftax = [
      'entity_table' => 'civicrm_financial_type',
      'entity_id' => $this->halfFinancialTypeId,
      'account_relationship' => 10,
      'financial_account_id' => $this->halfFinancialAccId,
    ];

    CRM_Financial_BAO_FinancialTypeAccount::add($financialRelationHalftax);

    // Enable component contribute setting
    $this->enableTaxAndInvoicing();
  }

  /**
   * Cleanup after function.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    parent::tearDown();
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function setUpContributionPage(): void {
    $contributionPageResult = $this->callAPISuccess($this->_entity, 'create', $this->params);
    if (empty($this->ids['price_set'])) {
      $priceSet = $this->callAPISuccess('price_set', 'create', $this->_priceSetParams);
      $this->ids['price_set'][] = $priceSet['id'];
    }
    $priceSetID = $this->_price = reset($this->ids['price_set']);
    CRM_Price_BAO_PriceSet::addTo('civicrm_contribution_page', $contributionPageResult['id'], $priceSetID);

    if (empty($this->ids['price_field'])) {
      $priceField = $this->callAPISuccess('price_field', 'create', [
        'price_set_id' => $priceSetID,
        'label' => 'Goat Breed',
        'html_type' => 'Radio',
      ]);
      $this->ids['price_field'] = [$priceField['id']];
      if (empty($this->ids['price_field_value'])) {
        $this->callAPISuccess('price_field_value', 'create', [
          'price_set_id' => $priceSetID,
          'price_field_id' => $priceField['id'],
          'label' => 'Long Haired Goat',
          'amount' => 100,
          'financial_type_id' => $this->financialTypeID,
        ]);
        $priceFieldValue = $this->callAPISuccess('price_field_value', 'create', [
          'price_set_id' => $priceSetID,
          'price_field_id' => $priceField['id'],
          'label' => 'Shoe-eating Goat',
          'amount' => 300,
          'financial_type_id' => $this->halfFinancialTypeId,
        ]);
        $this->ids['price_field_value'] = [$priceFieldValue['id']];
      }
    }
    $this->ids['contribution_page'] = $contributionPageResult['id'];
  }

  /**
   * Online and offline contribution from above created contribution page.
   *
   * @param string $thousandSeparator
   *   punctuation used to refer to thousands.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @dataProvider getThousandSeparators
   *
   */
  public function testCreateContributionOnline(string $thousandSeparator): void {
    $this->setCurrencySeparators($thousandSeparator);
    $this->setUpContributionPage();
    $params = [
      'contact_id' => $this->_individualId,
      'receive_date' => '20120511',
      'total_amount' => $this->formatMoneyInput(100.00),
      'financial_type_id' => $this->financialTypeID,
      'contribution_page_id' => $this->ids['contribution_page'],
      'payment_processor' => $this->ids['PaymentProcessor'],
      'trxn_id' => 12345,
      'invoice_id' => 67890,
      'source' => 'SSF',
      'contribution_status_id' => 1,
      'sequential' => 1,
    ];

    $contribution = $this->callAPISuccess('Contribution', 'create', $params)['values'][0];
    $this->assertEquals($this->_individualId, $contribution['contact_id']);
    $this->assertEquals(120.00, $contribution['total_amount']);
    $this->assertEquals($this->financialTypeID, $contribution['financial_type_id']);
    $this->assertEquals(12345, $contribution['trxn_id']);
    $this->assertEquals(67890, $contribution['invoice_id']);
    $this->assertEquals('SSF', $contribution['source']);
    $this->assertEquals(20, $contribution['tax_amount']);
    $this->assertEquals(1, $contribution['contribution_status_id']);
    $this->_checkFinancialRecords($contribution, 'online');
  }

  /**
   * Create contribution with chained line items.
   *
   * @param string $thousandSeparator
   *   punctuation used to refer to thousands.
   *
   * @dataProvider getThousandSeparators
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreateContributionChainedLineItems(string $thousandSeparator): void {
    $this->setCurrencySeparators($thousandSeparator);
    $this->setUpContributionPage();
    $params = [
      'contact_id' => $this->_individualId,
      'receive_date' => '20120511',
      'total_amount' => 400.00,
      'financial_type_id' => $this->financialTypeID,
      'trxn_id' => 12345,
      'invoice_id' => 67890,
      'source' => 'SSF',
      'contribution_status_id' => 1,
      'skipLineItem' => 1,
      'api.line_item.create' => [
        [
          'price_field_id' => $this->ids['price_field'],
          'qty' => 1,
          'line_total' => '100',
          'unit_price' => '100',
          'financial_type_id' => $this->financialTypeID,
        ],
        [
          'price_field_id' => $this->ids['price_field'],
          'qty' => 1,
          'line_total' => '300',
          'unit_price' => '300',
          'financial_type_id' => $this->halfFinancialTypeId,
        ],
      ],
    ];

    $contribution = $this->callAPISuccess('contribution', 'create', $params);

    $lineItems = $this->callAPISuccess('line_item', 'get', [
      'entity_id' => $contribution['id'],
      'contribution_id' => $contribution['id'],
      'entity_table' => 'civicrm_contribution',
      'sequential' => 1,
    ]);
    $this->assertEquals(2, $lineItems['count']);
  }

  /**
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   */
  public function testCreateContributionPayLaterOnline(): void {
    $this->setUpContributionPage();
    $params = [
      'contact_id' => $this->_individualId,
      'receive_date' => '20120511',
      'total_amount' => 100.00,
      'financial_type_id' => $this->financialTypeID,
      'contribution_page_id' => $this->ids['contribution_page'],
      'trxn_id' => 12345,
      'is_pay_later' => 1,
      'invoice_id' => 67890,
      'source' => 'SSF',
      'contribution_status_id' => 2,
      'sequential' => 1,
    ];
    $contribution = $this->callAPISuccess('Contribution', 'create', $params)['values'][0];
    $this->assertEquals($contribution['contact_id'], $this->_individualId);
    $this->assertEquals(120.00, $contribution['total_amount']);
    $this->assertEquals($this->financialTypeID, $contribution['financial_type_id']);
    $this->assertEquals(12345, $contribution['trxn_id']);
    $this->assertEquals(67890, $contribution['invoice_id']);
    $this->assertEquals('SSF', $contribution['source']);
    $this->assertEquals(20, $contribution['tax_amount']);
    $this->assertEquals(2, $contribution['contribution_status_id']);
    $this->_checkFinancialRecords($contribution, 'payLater');
  }

  /**
   * Test online pending contributions.
   *
   * @param string $thousandSeparator
   *   punctuation used to refer to thousands.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   * @dataProvider getThousandSeparators
   *
   */
  public function testCreateContributionPendingOnline(string $thousandSeparator): void {
    $this->setCurrencySeparators($thousandSeparator);
    $this->setUpContributionPage();
    $params = [
      'contact_id' => $this->_individualId,
      'receive_date' => '20120511',
      'total_amount' => $this->formatMoneyInput(100.00),
      'financial_type_id' => $this->financialTypeID,
      'contribution_page_id' => $this->ids['contribution_page'],
      'trxn_id' => 12345,
      'invoice_id' => 67890,
      'source' => 'SSF',
      'contribution_status_id' => 2,
      'sequential' => 1,
    ];

    $contribution = $this->callAPISuccess('Contribution', 'create', $params)['values'][0];
    $this->assertEquals($this->_individualId, $contribution['contact_id']);
    $this->assertEquals(120.00, $contribution['total_amount']);
    $this->assertEquals($this->financialTypeID, $contribution['financial_type_id']);
    $this->assertEquals(12345, $contribution['trxn_id']);
    $this->assertEquals(67890, $contribution['invoice_id']);
    $this->assertEquals('SSF', $contribution['source']);
    $this->assertEquals(20, $contribution['tax_amount']);
    $this->assertEquals(2, $contribution['contribution_status_id']);
    $trxn = $this->getFinancialTransactionsForContribution($contribution['id']);
    $this->assertCount(0, $trxn, 'No Trxn to be created until IPN callback');

    $this->setCurrencySeparators($thousandSeparator);
  }

  /**
   * Update a contribution.
   *
   * Function tests that line items, financial records are updated when
   * contribution amount is changed
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreateUpdateContributionChangeTotal(): void {
    $this->setUpContributionPage();
    $contributionParams = [
      'contact_id' => $this->_individualId,
      'receive_date' => '20120511',
      'total_amount' => 100.00,
      'financial_type_id' => $this->financialTypeID,
      'source' => 'SSF',
      'contribution_status_id' => 1,
    ];
    $contribution = $this->callAPISuccess('contribution', 'create', $contributionParams);
    $lineItems = $this->callAPISuccess('line_item', 'getvalue', [
      'entity_id' => $contribution['id'],
      'entity_table' => 'civicrm_contribution',
      'sequential' => 1,
      'return' => 'line_total',
    ]);
    $this->assertEquals('100.00', $lineItems);
    $trxnAmount = $this->_getFinancialTrxnAmount($contribution['id']);
    $this->assertEquals('120.00', $trxnAmount);
    $newParams = [
      'id' => $contribution['id'],
      // without tax rate i.e Donation
      'financial_type_id' => 1,
      'total_amount' => '300',
    ];
    $contribution = $this->callAPISuccess('contribution', 'create', $newParams);

    $lineItems = $this->callAPISuccess('line_item', 'getvalue', [
      'entity_id' => $contribution['id'],
      'entity_table' => 'civicrm_contribution',
      'sequential' => 1,
      'return' => 'line_total',
    ]);

    $this->assertEquals('300.00', $lineItems);
    $this->assertEquals('300.00', $this->_getFinancialTrxnAmount($contribution['id']));
    $this->assertEquals('320.00', $this->_getFinancialItemAmount($contribution['id']));
  }

  /**
   * @param int $contId
   *
   * @return null|string
   */
  public function _getFinancialTrxnAmount(int $contId): ?string {
    $query = "SELECT
     SUM( ft.total_amount ) AS total
     FROM civicrm_financial_trxn AS ft
     LEFT JOIN civicrm_entity_financial_trxn AS ceft ON ft.id = ceft.financial_trxn_id
     WHERE ceft.entity_table = 'civicrm_contribution'
     AND ceft.entity_id = {$contId}";
    return CRM_Core_DAO::singleValueQuery($query);
  }

  /**
   * @param int $contId
   *
   * @return null|string
   */
  public function _getFinancialItemAmount(int $contId): ?string {
    $lineItem = key(CRM_Price_BAO_LineItem::getLineItems($contId, 'contribution'));
    $query = "SELECT
     SUM(amount)
     FROM civicrm_financial_item
     WHERE entity_table = 'civicrm_line_item'
     AND entity_id = {$lineItem}";
    return CRM_Core_DAO::singleValueQuery($query);
  }

  /**
   * @param array $params
   * @param string $context
   *
   * @throws \API_Exception
   */
  public function _checkFinancialRecords($params, $context): void {
    $contributionID = $params['id'];
    $trxn = $this->getFinancialTransactionsForContribution($contributionID);

    $trxnParams = [
      'id' => $trxn->first()['financial_trxn_id'],
    ];
    if ($context !== 'online' && $context !== 'payLater') {
      $compareParams = [
        'to_financial_account_id' => 6,
        'total_amount' => 120,
        'status_id' => 1,
      ];
    }
    if ($context === 'online') {
      $compareParams = [
        'to_financial_account_id' => 12,
        'total_amount' => 120,
        'status_id' => 1,
      ];
    }
    elseif ($context === 'payLater') {
      $compareParams = [
        'to_financial_account_id' => 7,
        'total_amount' => 120,
        'status_id' => 2,
      ];
    }
    $this->assertDBCompareValues('CRM_Financial_DAO_FinancialTrxn', $trxnParams, $compareParams);
    $entityParams = [
      'financial_trxn_id' => $trxn->first()['financial_trxn_id'],
      'entity_table' => 'civicrm_financial_item',
    ];
    $entityTrxn = current(CRM_Financial_BAO_FinancialItem::retrieveEntityFinancialTrxn($entityParams));
    $compareParams = [
      'amount' => 100,
      'status_id' => 1,
      'financial_account_id' => $this->_getFinancialAccountId($this->financialTypeID),
    ];
    if ($context === 'payLater') {
      $compareParams = [
        'amount' => 100,
        'status_id' => 3,
        'financial_account_id' => $this->_getFinancialAccountId($this->financialTypeID),
      ];
    }
    $this->assertDBCompareValues('CRM_Financial_DAO_FinancialItem', [
      'id' => $entityTrxn['entity_id'],
    ], $compareParams);
  }

  /**
   * @param int $financialTypeId
   *
   * @return int
   */
  public function _getFinancialAccountId(int $financialTypeId): ?int {
    $accountRel = key(CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Income Account is' "));

    $searchParams = [
      'entity_table' => 'civicrm_financial_type',
      'entity_id' => $financialTypeId,
      'account_relationship' => $accountRel,
    ];

    $result = [];
    CRM_Financial_BAO_FinancialTypeAccount::retrieve($searchParams, $result);
    return $result['financial_account_id'] ?? NULL;
  }

  /**
   * Test deleting a contribution.
   *
   * (It is unclear why this is in this class - it seems like maybe it doesn't
   * test anything not on the contribution test class & might be copy and
   * paste....).
   *
   * @throws \CRM_Core_Exception
   */
  public function testDeleteContribution(): void {
    $contributionID = $this->contributionCreate([
      'contact_id' => $this->_individualId,
      'trxn_id' => 12389,
      'financial_type_id' => $this->financialTypeID,
      'invoice_id' => 'abc',
    ]);
    $this->callAPISuccess('contribution', 'delete', ['id' => $contributionID]);
  }

  /**
   * @param $contributionID
   *
   * @return \Civi\Api4\Generic\Result
   * @throws \API_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  protected function getFinancialTransactionsForContribution($contributionID): \Civi\Api4\Generic\Result {
    return EntityFinancialTrxn::get()
      ->addWhere('id', '=', $contributionID)
      ->addWhere('entity_table', '=', 'civicrm_contribution')
      ->addSelect('*')->execute();
  }

}
