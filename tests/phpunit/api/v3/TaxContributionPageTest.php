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
 * Class api_v3_TaxContributionPageTest
 * @group headless
 */
class api_v3_TaxContributionPageTest extends CiviUnitTestCase {
  protected $_apiversion = 3;
  protected $params;
  protected $financialtypeID;
  protected $financialAccountId;
  protected $_entity = 'contribution_page';
  protected $_priceSetParams = array();
  protected $_paymentProcessorType;
  protected $payParams = array();
  protected $paymentProceParams = array();
  protected $settingValue = array();
  protected $setInvoiceSettings;
  protected $_ids = array();
  protected $_individualId;
  protected $financialAccHalftax;
  protected $financialtypeHalftax;
  protected $financialRelationHalftax;
  protected $halfFinancialAccId;
  protected $halfFinancialTypeId;
  public $DBResetRequired = TRUE;

  public function setUp() {
    parent::setUp();
    $this->_individualId = $this->individualCreate();
    $this->_orgId = $this->organizationCreate(NULL);

    $this->params = array(
      'title' => "Test Contribution Page" . substr(sha1(rand()), 0, 7),
      'financial_type_id' => 1,
      'payment_processor' => 1,
      'currency' => 'NZD',
      'goal_amount' => 350,
      'is_pay_later' => 1,
      'pay_later_text' => 'I will pay later',
      'pay_later_receipt' => "I will pay later",
      'is_monetary' => TRUE,
      'is_billing_required' => TRUE,
    );

    $this->_priceSetParams = array(
      'name' => 'tax_contribution' . substr(sha1(rand()), 0, 7),
      'title' => 'contributiontax' . substr(sha1(rand()), 0, 7),
      'is_active' => 1,
      'help_pre' => "Where does your goat sleep",
      'help_post' => "thank you for your time",
      'extends' => 2,
      'financial_type_id' => 3,
      'is_quick_config' => 0,
      'is_reserved' => 0,
    );
    // Financial Account with 20% tax rate
    $financialAccountSetparams = array(
      #[domain_id] =>
      'name' => 'vat full taxrate account' . substr(sha1(rand()), 0, 7),
      'contact_id' => $this->_orgId,
      'financial_account_type_id' => 2,
      'is_tax' => 1,
      'tax_rate' => 20.00,
      'is_reserved' => 0,
      'is_active' => 1,
      'is_default' => 0,
    );

    $financialAccount = $this->callAPISuccess('financial_account', 'create', $financialAccountSetparams);
    $this->financialAccountId = $financialAccount['id'];

    // Financial type having 'Sales Tax Account is' with liability financail account
    $financialType = array(
      'name' => 'grassvariety1' . substr(sha1(rand()), 0, 7),
      'is_reserved' => 0,
      'is_active' => 1,
    );
    $priceField = $this->callAPISuccess('financial_type', 'create', $financialType);
    $this->financialtypeID = $priceField['id'];
    $financialRelationParams = array(
      'entity_table' => 'civicrm_financial_type',
      'entity_id' => $this->financialtypeID,
      'account_relationship' => 10,
      'financial_account_id' => $this->financialAccountId,
    );
    $financialRelation = CRM_Financial_BAO_FinancialTypeAccount::add($financialRelationParams);

    // Financial type with 5% tax rate
    $financialAccHalftax = array(
      'name' => 'vat half taxrate account' . substr(sha1(rand()), 0, 7),
      'contact_id' => $this->_orgId,
      'financial_account_type_id' => 2,
      'is_tax' => 1,
      'tax_rate' => 5.00,
      'is_reserved' => 0,
      'is_active' => 1,
      'is_default' => 0,
    );
    $halfFinancialAccount = CRM_Financial_BAO_FinancialAccount::add($financialAccHalftax);
    $this->halfFinancialAccId = $halfFinancialAccount->id;
    $halfFinancialtypeHalftax = array(
      'name' => 'grassvariety2' . substr(sha1(rand()), 0, 7),
      'is_reserved' => 0,
      'is_active' => 1,
    );

    $halfFinancialType = CRM_Financial_BAO_FinancialType::add($halfFinancialtypeHalftax);
    $this->halfFinancialTypeId = $halfFinancialType->id;
    $financialRelationHalftax = array(
      'entity_table' => 'civicrm_financial_type',
      'entity_id' => $this->halfFinancialTypeId,
      'account_relationship' => 10,
      'financial_account_id' => $this->halfFinancialAccId,
    );

    $halfFinancialRelation = CRM_Financial_BAO_FinancialTypeAccount::add($financialRelationHalftax);

    // Enable component contribute setting
    $setInvoiceSettings = $this->enableTaxAndInvoicing();

    // Payment Processor
    $paymentProceParams = array(
      'domain_id' => 1,
      'name' => 'dummy' . substr(sha1(rand()), 0, 7),
      'payment_processor_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Financial_BAO_PaymentProcessor', 'payment_processor_type_id', 'Dummy'),
      'financial_account_id' => 12,
      'is_active' => 1,
      'is_default' => 1,
      'user_name' => 'dummy',
      'url_site' => 'http://dummy.com',
      'url_recur' => 'http://dummyrecur.com',
      'class_name' => 'Payment_Dummy',
      'billing_mode' => 1,
      'is_recur' => 1,
      'payment_type' => 1,
    );
    $result = $this->callAPISuccess('payment_processor', 'create', $paymentProceParams);
    $this->_ids['paymentProcessID'] = $result['id'];
    require_once 'api/v3/examples/PaymentProcessor/Create.php';
    $this->assertAPISuccess($result);
  }

  public function tearDown() {
    $this->quickCleanUpFinancialEntities();
  }

  public function setUpContributionPage() {
    $contributionPageResult = $this->callAPISuccess($this->_entity, 'create', $this->params);
    if (empty($this->_ids['price_set'])) {
      $priceSet = $this->callAPISuccess('price_set', 'create', $this->_priceSetParams);
      $this->_ids['price_set'][] = $priceSet['id'];
    }
    $priceSetID = $this->_price = reset($this->_ids['price_set']);
    CRM_Price_BAO_PriceSet::addTo('civicrm_contribution_page', $contributionPageResult['id'], $priceSetID);

    if (empty($this->_ids['price_field'])) {
      $priceField = $this->callAPISuccess('price_field', 'create', array(
        'price_set_id' => $priceSetID,
        'label' => 'Goat Breed',
        'html_type' => 'Radio',
      ));
      $this->_ids['price_field'] = array($priceField['id']);
    }
    if (empty($this->_ids['price_field_value'])) {
      $this->callAPISuccess('price_field_value', 'create', array(
        'price_set_id' => $priceSetID,
        'price_field_id' => $priceField['id'],
        'label' => 'Long Haired Goat',
        'amount' => 100,
        'financial_type_id' => $this->financialtypeID,
      ));
      $priceFieldValue = $this->callAPISuccess('price_field_value', 'create', array(
        'price_set_id' => $priceSetID,
        'price_field_id' => $priceField['id'],
        'label' => 'Shoe-eating Goat',
        'amount' => 300,
        'financial_type_id' => $this->halfFinancialTypeId,
      ));
      $this->_ids['price_field_value'] = array($priceFieldValue['id']);
    }
    $this->_ids['contribution_page'] = $contributionPageResult['id'];
  }

  /**
   * Online and offline contrbution from above created contribution page.
   *
   * @param string $thousandSeparator
   *   punctuation used to refer to thousands.
   *
   * @dataProvider getThousandSeparators
   */
  public function testCreateContributionOnline($thousandSeparator) {
    $this->setCurrencySeparators($thousandSeparator);
    $this->setUpContributionPage();
    $params = array(
      'contact_id' => $this->_individualId,
      'receive_date' => '20120511',
      'total_amount' => $this->formatMoneyInput(100.00),
      'financial_type_id' => $this->financialtypeID,
      'contribution_page_id' => $this->_ids['contribution_page'],
      'payment_processor' => $this->_ids['paymentProcessID'],
      'trxn_id' => 12345,
      'invoice_id' => 67890,
      'source' => 'SSF',
      'contribution_status_id' => 1,
    );

    $contribution = $this->callAPISuccess('contribution', 'create', $params);
    $this->_ids['contributionId'] = $contribution['id'];
    $this->assertEquals($contribution['values'][$contribution['id']]['contact_id'], $this->_individualId);
    $this->assertEquals($contribution['values'][$contribution['id']]['total_amount'], 120.00);
    $this->assertEquals($contribution['values'][$contribution['id']]['financial_type_id'], $this->financialtypeID);
    $this->assertEquals($contribution['values'][$contribution['id']]['trxn_id'], 12345);
    $this->assertEquals($contribution['values'][$contribution['id']]['invoice_id'], 67890);
    $this->assertEquals($contribution['values'][$contribution['id']]['source'], 'SSF');
    $this->assertEquals($contribution['values'][$contribution['id']]['tax_amount'], 20);
    $this->assertEquals($contribution['values'][$contribution['id']]['contribution_status_id'], 1);
    $this->_checkFinancialRecords($contribution, 'online');
  }

  /**
   * Create contribution with chained line items.
   *
   * @param string $thousandSeparator
   *   punctuation used to refer to thousands.
   *
   * @dataProvider getThousandSeparators
   */
  public function testCreateContributionChainedLineItems($thousandSeparator) {
    $this->setCurrencySeparators($thousandSeparator);
    $this->setUpContributionPage();
    $params = array(
      'contact_id' => $this->_individualId,
      'receive_date' => '20120511',
      'total_amount' => 400.00,
      'financial_type_id' => $this->financialtypeID,
      'trxn_id' => 12345,
      'invoice_id' => 67890,
      'source' => 'SSF',
      'contribution_status_id' => 1,
      'skipLineItem' => 1,
      'api.line_item.create' => array(
        array(
          'price_field_id' => $this->_ids['price_field'],
          'qty' => 1,
          'line_total' => '100',
          'unit_price' => '100',
          'financial_type_id' => $this->financialtypeID,
        ),
        array(
          'price_field_id' => $this->_ids['price_field'],
          'qty' => 1,
          'line_total' => '300',
          'unit_price' => '300',
          'financial_type_id' => $this->halfFinancialTypeId,
        ),
      ),
    );

    $contribution = $this->callAPISuccess('contribution', 'create', $params);

    $lineItems = $this->callAPISuccess('line_item', 'get', array(
      'entity_id' => $contribution['id'],
      'contribution_id' => $contribution['id'],
      'entity_table' => 'civicrm_contribution',
      'sequential' => 1,
    ));
    $this->assertEquals(2, $lineItems['count']);
  }

  public function testCreateContributionPayLaterOnline() {
    $this->setUpContributionPage();
    $params = array(
      'contact_id' => $this->_individualId,
      'receive_date' => '20120511',
      'total_amount' => 100.00,
      'financial_type_id' => $this->financialtypeID,
      'contribution_page_id' => $this->_ids['contribution_page'],
      'trxn_id' => 12345,
      'is_pay_later' => 1,
      'invoice_id' => 67890,
      'source' => 'SSF',
      'contribution_status_id' => 2,
    );
    $contribution = $this->callAPISuccess('contribution', 'create', $params, __FUNCTION__, __FILE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['contact_id'], $this->_individualId);
    $this->assertEquals($contribution['values'][$contribution['id']]['total_amount'], 120.00);
    $this->assertEquals($contribution['values'][$contribution['id']]['financial_type_id'], $this->financialtypeID);
    $this->assertEquals($contribution['values'][$contribution['id']]['trxn_id'], 12345);
    $this->assertEquals($contribution['values'][$contribution['id']]['invoice_id'], 67890);
    $this->assertEquals($contribution['values'][$contribution['id']]['source'], 'SSF');
    $this->assertEquals($contribution['values'][$contribution['id']]['tax_amount'], 20);
    $this->assertEquals($contribution['values'][$contribution['id']]['contribution_status_id'], 2);
    $this->_checkFinancialRecords($contribution, 'payLater');
  }

  /**
   * Test online pending contributions.
   *
   * @param string $thousandSeparator
   *   punctuation used to refer to thousands.
   *
   * @dataProvider getThousandSeparators
   */
  public function testCreateContributionPendingOnline($thousandSeparator) {
    $this->setCurrencySeparators($thousandSeparator);
    $this->setUpContributionPage();
    $params = array(
      'contact_id' => $this->_individualId,
      'receive_date' => '20120511',
      'total_amount' => $this->formatMoneyInput(100.00),
      'financial_type_id' => $this->financialtypeID,
      'contribution_page_id' => $this->_ids['contribution_page'],
      'trxn_id' => 12345,
      'invoice_id' => 67890,
      'source' => 'SSF',
      'contribution_status_id' => 2,
    );

    $contribution = $this->callAPISuccess('contribution', 'create', $params, __FUNCTION__, __FILE__);
    $this->assertEquals($contribution['values'][$contribution['id']]['contact_id'], $this->_individualId);
    $this->assertEquals($contribution['values'][$contribution['id']]['total_amount'], 120.00);
    $this->assertEquals($contribution['values'][$contribution['id']]['financial_type_id'], $this->financialtypeID);
    $this->assertEquals($contribution['values'][$contribution['id']]['trxn_id'], 12345);
    $this->assertEquals($contribution['values'][$contribution['id']]['invoice_id'], 67890);
    $this->assertEquals($contribution['values'][$contribution['id']]['source'], 'SSF');
    $this->assertEquals($contribution['values'][$contribution['id']]['tax_amount'], 20);
    $this->assertEquals($contribution['values'][$contribution['id']]['contribution_status_id'], 2);
    $this->_checkFinancialRecords($contribution, 'pending');
    $this->setCurrencySeparators($thousandSeparator);
  }

  /**
   * Update a contribution.
   *
   * Function tests that line items, financial records are updated when contribution amount is changed
   */
  public function testCreateUpdateContributionChangeTotal() {
    $this->setUpContributionPage();
    $this->contributionParams = array(
      'contact_id' => $this->_individualId,
      'receive_date' => '20120511',
      'total_amount' => 100.00,
      'financial_type_id' => $this->financialtypeID,
      'source' => 'SSF',
      'contribution_status_id' => 1,
    );
    $contribution = $this->callAPISuccess('contribution', 'create', $this->contributionParams);
    $lineItems = $this->callAPISuccess('line_item', 'getvalue', array(
      'entity_id' => $contribution['id'],
      'entity_table' => 'civicrm_contribution',
      'sequential' => 1,
      'return' => 'line_total',
    ));
    $this->assertEquals('100.00', $lineItems);
    $trxnAmount = $this->_getFinancialTrxnAmount($contribution['id']);
    $this->assertEquals('120.00', $trxnAmount);
    $newParams = array(
      'id' => $contribution['id'],
      // without tax rate i.e Donation
      'financial_type_id' => 1,
      'total_amount' => '300',
    );
    $contribution = $this->callAPISuccess('contribution', 'create', $newParams);

    $lineItems = $this->callAPISuccess('line_item', 'getvalue', array(
      'entity_id' => $contribution['id'],
      'entity_table' => 'civicrm_contribution',
      'sequential' => 1,
      'return' => 'line_total',
    ));

    $this->assertEquals('300.00', $lineItems);
    $trxnAmount = $this->_getFinancialTrxnAmount($contribution['id']);
    $fitemAmount = $this->_getFinancialItemAmount($contribution['id']);
    $this->assertEquals('300.00', $trxnAmount);
    $this->assertEquals('300.00', $fitemAmount);
  }

  /**
   * @param int $contId
   *
   * @return null|string
   */
  public function _getFinancialTrxnAmount($contId) {
    $query = "SELECT
     SUM( ft.total_amount ) AS total
     FROM civicrm_financial_trxn AS ft
     LEFT JOIN civicrm_entity_financial_trxn AS ceft ON ft.id = ceft.financial_trxn_id
     WHERE ceft.entity_table = 'civicrm_contribution'
     AND ceft.entity_id = {$contId}";
    $result = CRM_Core_DAO::singleValueQuery($query);
    return $result;
  }

  /**
   * @param int $contId
   *
   * @return null|string
   */
  public function _getFinancialItemAmount($contId) {
    $lineItem = key(CRM_Price_BAO_LineItem::getLineItems($contId, 'contribution'));
    $query = "SELECT
     SUM(amount)
     FROM civicrm_financial_item
     WHERE entity_table = 'civicrm_line_item'
     AND entity_id = {$lineItem}";
    $result = CRM_Core_DAO::singleValueQuery($query);
    return $result;
  }

  /**
   * @param array $params
   * @param $context
   */
  public function _checkFinancialRecords($params, $context) {
    $entityParams = array(
      'entity_id' => $params['id'],
      'entity_table' => 'civicrm_contribution',
    );
    if ($context == 'pending') {
      $trxn = CRM_Financial_BAO_FinancialItem::retrieveEntityFinancialTrxn($entityParams);
      $this->assertNull($trxn, 'No Trxn to be created until IPN callback');
      return;
    }
    $trxn = current(CRM_Financial_BAO_FinancialItem::retrieveEntityFinancialTrxn($entityParams));
    $trxnParams = array(
      'id' => $trxn['financial_trxn_id'],
    );
    if ($context != 'online' && $context != 'payLater') {
      $compareParams = array(
        'to_financial_account_id' => 6,
        'total_amount' => 120,
        'status_id' => 1,
      );
    }
    if ($context == 'online') {
      $compareParams = array(
        'to_financial_account_id' => 12,
        'total_amount' => 120,
        'status_id' => 1,
      );
    }
    elseif ($context == 'payLater') {
      $compareParams = array(
        'to_financial_account_id' => 7,
        'total_amount' => 120,
        'status_id' => 2,
      );
    }
    $this->assertDBCompareValues('CRM_Financial_DAO_FinancialTrxn', $trxnParams, $compareParams);
    $entityParams = array(
      'financial_trxn_id' => $trxn['financial_trxn_id'],
      'entity_table' => 'civicrm_financial_item',
    );
    $entityTrxn = current(CRM_Financial_BAO_FinancialItem::retrieveEntityFinancialTrxn($entityParams));
    $fitemParams = array(
      'id' => $entityTrxn['entity_id'],
    );
    $compareParams = array(
      'amount' => 100,
      'status_id' => 1,
      'financial_account_id' => $this->_getFinancialAccountId($this->financialtypeID),
    );
    if ($context == 'payLater') {
      $compareParams = array(
        'amount' => 100,
        'status_id' => 3,
        'financial_account_id' => $this->_getFinancialAccountId($this->financialtypeID),
      );
    }
    $this->assertDBCompareValues('CRM_Financial_DAO_FinancialItem', $fitemParams, $compareParams);
  }

  /**
   * @param int $financialTypeId
   * @return int
   */
  public function _getFinancialAccountId($financialTypeId) {
    $accountRel = key(CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Income Account is' "));

    $searchParams = array(
      'entity_table' => 'civicrm_financial_type',
      'entity_id' => $financialTypeId,
      'account_relationship' => $accountRel,
    );

    $result = array();
    CRM_Financial_BAO_FinancialTypeAccount::retrieve($searchParams, $result);
    return CRM_Utils_Array::value('financial_account_id', $result);
  }

  /**
   * Test deleting a contribution.
   *
   * (It is unclear why this is in this class - it seems like maybe it doesn't test anything not
   * on the contribution test class & might be copy and paste....).
   */
  public function testDeleteContribution() {
    $contributionID = $this->contributionCreate(array(
      'contact_id' => $this->_individualId,
      'trxn_id' => 12389,
      'financial_type_id' => $this->financialtypeID,
      'invoice_id' => 'dfsdf',
    ));
    $this->callAPISuccess('contribution', 'delete', array('id' => $contributionID));
  }

}
