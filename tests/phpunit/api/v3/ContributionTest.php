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

use Civi\Api4\ActivityContact;
use Civi\Api4\Contribution;
use Civi\Api4\PriceField;
use Civi\Api4\PriceFieldValue;
use Civi\Api4\PriceSet;

/**
 *  Test APIv3 civicrm_contribute_* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Contribution
 * @group headless
 */
class api_v3_ContributionTest extends CiviUnitTestCase {

  use CRMTraits_Profile_ProfileTrait;
  use CRMTraits_Custom_CustomDataTrait;
  use CRMTraits_Financial_OrderTrait;
  use CRMTraits_Financial_TaxTrait;
  use CRMTraits_Financial_PriceSetTrait;

  protected $_individualId;
  protected $_contribution;
  protected $_financialTypeId = 1;
  protected $entity = 'Contribution';
  protected $_params;
  protected $_ids = [];
  protected $_pageParams = [];

  /**
   * Payment processor ID (dummy processor).
   *
   * @var int
   */
  protected $paymentProcessorID;

  /**
   * Parameters to create payment processor.
   *
   * @var array
   */
  protected $_processorParams = [];

  /**
   * ID of created event.
   *
   * @var int
   */
  protected $_eventID;

  /**
   * @var CiviMailUtils
   */
  protected $mut;

  /**
   * Should financials be checked after the test but before tear down.
   *
   * @var bool
   */
  protected $isValidateFinancialsOnPostAssert = TRUE;

  /**
   * Setup function.
   */
  public function setUp(): void {
    parent::setUp();

    $this->_apiversion = 3;
    $this->_individualId = $this->individualCreate();
    $this->_params = [
      'contact_id' => $this->_individualId,
      'receive_date' => '20120511',
      'total_amount' => 100.00,
      'financial_type_id' => $this->_financialTypeId,
      'non_deductible_amount' => 10.00,
      'fee_amount' => 5.00,
      'net_amount' => 95.00,
      'source' => 'SSF',
      'contribution_status_id' => 1,
    ];
    $this->_processorParams = [
      'domain_id' => 1,
      'name' => 'Dummy',
      'payment_processor_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Financial_BAO_PaymentProcessor', 'payment_processor_type_id', 'Dummy'),
      'financial_account_id' => 12,
      'is_active' => 1,
      'user_name' => '',
      'url_site' => 'http://dummy.com',
      'url_recur' => 'http://dummy.com',
      'billing_mode' => 1,
    ];
    $this->paymentProcessorID = $this->processorCreate();
    $this->_pageParams = [
      'title' => 'Test Contribution Page',
      'financial_type_id' => 1,
      'currency' => 'USD',
      'financial_account_id' => 1,
      'payment_processor' => $this->paymentProcessorID,
      'is_active' => 1,
      'is_allow_other_amount' => 1,
      'min_amount' => 10,
      'max_amount' => 1000,
    ];
  }

  /**
   * Clean up after each test.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   */
  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    $this->quickCleanup(['civicrm_uf_match'], TRUE);
    $financialAccounts = $this->callAPISuccess('FinancialAccount', 'get', ['return' => 'name']);
    foreach ($financialAccounts['values'] as $financialAccount) {
      if ($financialAccount['name'] === 'Test Tax financial account ' || $financialAccount['name'] === 'Test taxable financial Type') {
        $entityFinancialTypes = $this->callAPISuccess('EntityFinancialAccount', 'get', [
          'financial_account_id' => $financialAccount['id'],
        ]);
        foreach ($entityFinancialTypes['values'] as $entityFinancialType) {
          $this->callAPISuccess('EntityFinancialAccount', 'delete', ['id' => $entityFinancialType['id']]);
        }
        $this->callAPISuccess('FinancialAccount', 'delete', ['id' => $financialAccount['id']]);
      }
    }
    $this->restoreUFGroupOne();
    parent::tearDown();
  }

  /**
   * Test Get.
   */
  public function testGetContribution(): void {
    $this->enableTaxAndInvoicing();
    $p = [
      'contact_id' => $this->_individualId,
      'receive_date' => '2010-01-20',
      'total_amount' => 100.00,
      'financial_type_id' => $this->_financialTypeId,
      'non_deductible_amount' => 10.00,
      'fee_amount' => 5.00,
      'net_amount' => 95.00,
      'trxn_id' => 23456,
      'invoice_id' => 78910,
      'source' => 'SSF',
      'contribution_status_id' => 'Completed',
    ];
    $this->_contribution = $this->callAPISuccess('contribution', 'create', $p);

    $params = [
      'contribution_id' => $this->_contribution['id'],
      'return' => array_merge(['invoice_number', 'contribution_source'], array_keys($p)),
    ];

    $contributions = $this->callAPIAndDocument('Contribution', 'get', $params, __FUNCTION__, __FILE__);

    $this->assertEquals(1, $contributions['count']);
    $contribution = $contributions['values'][$contributions['id']];
    $this->assertEquals($this->_individualId, $contribution['contact_id']);
    $this->assertEquals(1, $contribution['financial_type_id']);
    $this->assertEquals(100.00, $contribution['total_amount']);
    $this->assertEquals(10.00, $contribution['non_deductible_amount']);
    $this->assertEquals(5.00, $contribution['fee_amount']);
    $this->assertEquals(95.00, $contribution['net_amount']);
    $this->assertEquals(23456, $contribution['trxn_id']);
    $this->assertEquals(78910, $contribution['invoice_id']);
    $this->assertRegExp('/INV_\d+/', $contribution['invoice_number']);
    $this->assertEquals('SSF', $contribution['contribution_source']);
    $this->assertEquals('Completed', $contribution['contribution_status']);
    // Create a second contribution - we are testing that 'id' gets the right contribution id (not the contact id).
    $p['trxn_id'] = '3847';
    $p['invoice_id'] = '3847';

    $contribution2 = $this->callAPISuccess('contribution', 'create', $p);

    // Now we have 2 - test getcount.
    $contribution = $this->callAPISuccess('contribution', 'getcount');
    $this->assertEquals(2, $contribution);
    // Test id only format.
    $contribution = $this->callAPISuccess('contribution', 'get', [
      'id' => $this->_contribution['id'],
      'format.only_id' => 1,
    ]);
    $this->assertEquals($this->_contribution['id'], $contribution, print_r($contribution, TRUE));
    // Test id only format.
    $contribution = $this->callAPISuccess('contribution', 'get', [
      'id' => $contribution2['id'],
      'format.only_id' => 1,
    ]);
    $this->assertEquals($contribution2['id'], $contribution);
    // Test id as field.
    $contribution = $this->callAPISuccess('contribution', 'get', [
      'id' => $this->_contribution['id'],
    ]);
    $this->assertEquals(1, $contribution['count']);

    // Test get by contact id works.
    $contribution = $this->callAPISuccess('contribution', 'get', ['contact_id' => $this->_individualId]);

    $this->assertEquals(2, $contribution['count']);
    $this->callAPISuccess('Contribution', 'Delete', [
      'id' => $this->_contribution['id'],
    ]);
    $this->callAPISuccess('Contribution', 'Delete', [
      'id' => $contribution2['id'],
    ]);
  }

  /**
   * Test that test contributions can be retrieved.
   *
   * @throws \CRM_Core_Exception
   */
  public function testGetTestContribution(): void {
    $this->callAPISuccess('Contribution', 'create', array_merge($this->_params, ['is_test' => 1]));
    $this->callAPISuccessGetSingle('Contribution', ['is_test' => 1]);
  }

  /**
   * Test Creating a check contribution with original check_number field
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreateCheckContribution(): void {
    $params = $this->_params;
    $params['contribution_check_number'] = 'bouncer';
    $params['payment_instrument_id'] = 'Check';
    $params['cancel_date'] = 'yesterday';
    $params['receipt_date'] = 'yesterday';
    $params['thankyou_date'] = 'yesterday';
    $params['revenue_recognition_date'] = 'yesterday';
    $params['amount_level'] = 'Unreasonable';
    $params['cancel_reason'] = 'You lose sucker';
    $params['creditnote_id'] = 'sudo rm -rf';
    $address = $this->callAPISuccess('Address', 'create', [
      'street_address' => 'Knockturn Alley',
      'contact_id' => $this->_individualId,
      'location_type_id' => 'Home',
    ]);
    $params['address_id'] = $address['id'];
    $contributionPage = $this->contributionPageCreate();
    $params['contribution_page_id'] = $contributionPage['id'];
    $params['campaign_id'] = $this->campaignCreate();
    $contributionID = $this->contributionCreate($params);
    $getResult = $this->callAPISuccess('Contribution', 'get', [
      'id' => $contributionID,
      'return' => 'check_number',
    ])['values'][$contributionID];
    $this->assertEquals('bouncer', $getResult['check_number']);
    $entityFinancialTrxn = $this->callAPISuccess('EntityFinancialTrxn', 'get', [
      'entity_id' => $contributionID,
      'entity_table' => 'civicrm_contribution',
      'return' => 'financial_trxn_id',
    ]);
    foreach ($entityFinancialTrxn['values'] as $eft) {
      $financialTrxn = $this->callAPISuccess('FinancialTrxn', 'get', [
        'id' => $eft['financial_trxn_id'],
        'return' => 'check_number',
      ]);
      $this->assertEquals('bouncer', $financialTrxn['values'][$financialTrxn['id']]['check_number']);
    }
  }

  /**
   * Test the 'return' param works for all fields.
   */
  public function testGetContributionReturnFunctionality(): void {
    $params = $this->_params;
    $params['contribution_check_number'] = 'bouncer';
    $params['payment_instrument_id'] = 'Check';
    $params['cancel_date'] = 'yesterday';
    $params['receipt_date'] = 'yesterday';
    $params['thankyou_date'] = 'yesterday';
    $params['revenue_recognition_date'] = 'yesterday';
    $params['amount_level'] = 'Unreasonable';
    $params['cancel_reason'] = 'You lose sucker';
    $params['creditnote_id'] = 'sudo rm -rf';
    $address = $this->callAPISuccess('Address', 'create', [
      'street_address' => 'Knockturn Alley',
      'contact_id' => $this->_individualId,
      'location_type_id' => 'Home',
    ]);
    $params['address_id'] = $address['id'];
    $contributionPage = $this->contributionPageCreate();
    $params['contribution_page_id'] = $contributionPage['id'];
    $contributionRecur = $this->callAPISuccess('ContributionRecur', 'create', [
      'contact_id' => $this->_individualId,
      'frequency_interval' => 1,
      'amount' => 5,
    ]);
    $params['contribution_recur_id'] = $contributionRecur['id'];

    $params['campaign_id'] = $this->campaignCreate();

    $contributionID = $this->contributionCreate($params);

    // update contribution with invoice number
    $params = array_merge($params, [
      'id' => $contributionID,
      'invoice_number' => Civi::settings()->get('invoice_prefix') . $contributionID,
      'trxn_id' => 12345,
      'invoice_id' => 6789,
    ]);
    $contributionID = $this->contributionCreate($params);

    $contribution = $this->callAPISuccessGetSingle('Contribution', ['id' => $contributionID]);
    $this->assertEquals('bouncer', $contribution['check_number']);
    $this->assertEquals('bouncer', $contribution['contribution_check_number']);

    $fields = CRM_Contribute_BAO_Contribution::fields();
    // Do not check for tax_amount as this test has not enabled invoicing
    // & hence it is not reliable.
    unset($fields['tax_amount']);
    // Re-add these 2 to the fields to check. They were locked in but the metadata changed so we
    // need to specify them.
    $fields['address_id'] = $fields['contribution_address_id'];
    $fields['check_number'] = $fields['contribution_check_number'];

    $fieldsLockedIn = [
      'contribution_id', 'contribution_contact_id', 'financial_type_id', 'contribution_page_id',
      'payment_instrument_id', 'receive_date', 'non_deductible_amount', 'total_amount',
      'fee_amount', 'net_amount', 'trxn_id', 'invoice_id', 'currency', 'contribution_cancel_date', 'cancel_reason',
      'receipt_date', 'thankyou_date', 'contribution_source', 'amount_level', 'contribution_recur_id',
      'is_test', 'is_pay_later', 'contribution_status_id', 'address_id', 'check_number', 'contribution_campaign_id',
      'creditnote_id', 'revenue_recognition_date', 'decoy',
    ];
    $missingFields = array_diff($fieldsLockedIn, array_keys($fields));
    // If any of the locked in fields disappear from the $fields array we need to make sure it is still
    // covered as the test contract now guarantees them in the return array.
    $this->assertEquals([28 => 'decoy'], $missingFields, 'A field which was covered by the test contract has changed.');
    foreach ($fields as $fieldName => $fieldSpec) {
      $contribution = $this->callAPISuccessGetSingle('Contribution', ['id' => $contributionID, 'return' => $fieldName]);
      $returnField = $fieldName;
      if ($returnField === 'contribution_contact_id') {
        $returnField = 'contact_id';
      }
      $this->assertTrue((!empty($contribution[$returnField]) || $contribution[$returnField] === "0"), $returnField);
    }
    $entityFinancialTrxn = $this->callAPISuccess('EntityFinancialTrxn', 'get', [
      'entity_id' => $contributionID,
      'entity_table' => 'civicrm_contribution',
      'return' => 'financial_trxn_id',
    ]);
    foreach ($entityFinancialTrxn['values'] as $eft) {
      $financialTrxn = $this->callAPISuccess('FinancialTrxn', 'get', ['id' => $eft['financial_trxn_id'], 'return' => 'check_number']);
      $this->assertEquals('bouncer', $financialTrxn['values'][$financialTrxn['id']]['check_number']);
    }
  }

  /**
   * Test cancel reason works as a filter.
   *
   * @throws \CRM_Core_Exception
   */
  public function testFilterCancelReason(): void {
    $params = $this->_params;
    $params['cancel_date'] = 'yesterday';
    $params['cancel_reason'] = 'You lose sucker';
    $this->callAPISuccess('Contribution', 'create', $params);
    $params = $this->_params;
    $params['cancel_date'] = 'yesterday';
    $params['cancel_reason'] = 'You are a winner';
    $this->callAPISuccess('Contribution', 'create', $params);
    $this->callAPISuccessGetCount('Contribution', ['cancel_reason' => 'You are a winner'], 1);
  }

  /**
   * We need to ensure previous tested api contract behaviour still works.
   *
   * @throws \CRM_Core_Exception
   */
  public function testGetContributionLegacyBehaviour(): void {
    $p = [
      'contact_id' => $this->_individualId,
      'receive_date' => '2010-01-20',
      'total_amount' => 100.00,
      'contribution_type_id' => $this->_financialTypeId,
      'non_deductible_amount' => 10.00,
      'fee_amount' => 5.00,
      'net_amount' => 95.00,
      'trxn_id' => 23456,
      'invoice_id' => 78910,
      'source' => 'SSF',
      'contribution_status_id' => 1,
    ];
    $this->_contribution = $this->callAPISuccess('Contribution', 'create', $p);

    $params = [
      'contribution_id' => $this->_contribution['id'],
      'return' => array_keys($p),
    ];
    $params['return'][] = 'financial_type_id';
    $params['return'][] = 'contribution_source';
    $contribution = $this->callAPISuccess('Contribution', 'get', $params);

    $this->assertEquals(1, $contribution['count']);
    $this->assertEquals($contribution['values'][$contribution['id']]['contact_id'], $this->_individualId);
    $this->assertEquals($contribution['values'][$contribution['id']]['financial_type_id'], $this->_financialTypeId);
    $this->assertEquals($contribution['values'][$contribution['id']]['contribution_type_id'], $this->_financialTypeId);
    $this->assertEquals($contribution['values'][$contribution['id']]['total_amount'], 100.00);
    $this->assertEquals($contribution['values'][$contribution['id']]['non_deductible_amount'], 10.00);
    $this->assertEquals($contribution['values'][$contribution['id']]['fee_amount'], 5.00);
    $this->assertEquals($contribution['values'][$contribution['id']]['net_amount'], 95.00);
    $this->assertEquals($contribution['values'][$contribution['id']]['trxn_id'], 23456);
    $this->assertEquals($contribution['values'][$contribution['id']]['invoice_id'], 78910);
    $this->assertEquals($contribution['values'][$contribution['id']]['contribution_source'], 'SSF');
    $this->assertEquals($contribution['values'][$contribution['id']]['contribution_status'], 'Completed');

    // Create a second contribution - we are testing that 'id' gets the right contribution id (not the contact id).
    $p['trxn_id'] = '3847';
    $p['invoice_id'] = '3847';

    $contribution2 = $this->callAPISuccess('contribution', 'create', $p);

    // now we have 2 - test getcount
    $contribution = $this->callAPISuccess('contribution', 'getcount', []);
    $this->assertEquals(2, $contribution);
    //test id only format
    $contribution = $this->callAPISuccess('contribution', 'get', [
      'id' => $this->_contribution['id'],
      'format.only_id' => 1,
    ]);
    $this->assertEquals($this->_contribution['id'], $contribution, print_r($contribution, TRUE));
    //test id only format
    $contribution = $this->callAPISuccess('contribution', 'get', [
      'id' => $contribution2['id'],
      'format.only_id' => 1,
    ]);
    $this->assertEquals($contribution2['id'], $contribution);
    $contribution = $this->callAPISuccess('contribution', 'get', [
      'id' => $this->_contribution['id'],
    ]);
    //test id as field
    $this->assertEquals(1, $contribution['count']);
    // $this->assertEquals($this->_contribution['id'], $contribution['id'] )  ;
    //test get by contact id works
    $contribution = $this->callAPISuccess('contribution', 'get', ['contact_id' => $this->_individualId]);

    $this->assertEquals(2, $contribution['count']);
    $this->callAPISuccess('Contribution', 'Delete', [
      'id' => $this->_contribution['id'],
    ]);
    $this->callAPISuccess('Contribution', 'Delete', [
      'id' => $contribution2['id'],
    ]);
  }

  /**
   * Create an contribution_id=FALSE and financial_type_id=Donation.
   */
  public function testCreateEmptyContributionIDUseDonation() {
    $params = [
      'contribution_id' => FALSE,
      'contact_id' => 1,
      'total_amount' => 1,
      'check_permissions' => FALSE,
      'financial_type_id' => 'Donation',
    ];
    $this->callAPISuccess('contribution', 'create', $params);
  }

  /**
   * Check with complete array + custom field.
   *
   * Note that the test is written on purpose without any
   * variables specific to participant so it can be replicated into other entities
   * and / or moved to the automated test suite
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreateWithCustom(): void {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);

    $params = $this->_params;
    $params['custom_' . $ids['custom_field_id']] = "custom string";

    $result = $this->callAPIAndDocument($this->entity, 'create', $params, __FUNCTION__, __FILE__);
    $this->assertEquals($result['id'], $result['values'][$result['id']]['id']);
    $check = $this->callAPISuccess($this->entity, 'get', [
      'return.custom_' . $ids['custom_field_id'] => 1,
      'id' => $result['id'],
    ]);
    $this->assertEquals('custom string', $check['values'][$check['id']]['custom_' . $ids['custom_field_id']]);
  }

  /**
   * Check with complete array + custom field.
   *
   * Note that the test is written on purpose without any
   * variables specific to participant so it can be replicated into other
   * entities and / or moved to the automated test suite
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreateGetFieldsWithCustom(): void {
    $ids = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, __FILE__);
    $idsContact = $this->entityCustomGroupWithSingleFieldCreate(__FUNCTION__, 'ContactTest.php');
    $result = $this->callAPISuccess('Contribution', 'getfields', []);
    $this->assertArrayHasKey('custom_' . $ids['custom_field_id'], $result['values']);
    $this->assertArrayNotHasKey('custom_' . $idsContact['custom_field_id'], $result['values']);
    $this->customFieldDelete($ids['custom_field_id']);
    $this->customGroupDelete($ids['custom_group_id']);
    $this->customFieldDelete($idsContact['custom_field_id']);
    $this->customGroupDelete($idsContact['custom_group_id']);
  }

  /**
   * Test creating a contribution without skipLineItems.
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreateContributionNoLineItems(): void {
    // Turn off this validation as this test results in invalid
    // financial entities.
    $this->isValidateFinancialsOnPostAssert = FALSE;
    $params = [
      'contact_id' => $this->_individualId,
      'receive_date' => '20120511',
      'total_amount' => 100.00,
      'financial_type_id' => $this->_financialTypeId,
      'payment_instrument_id' => 1,
      'non_deductible_amount' => 10.00,
      'fee_amount' => 50.00,
      'net_amount' => 90.00,
      'trxn_id' => 12345,
      'invoice_id' => 67890,
      'source' => 'SSF',
      'contribution_status_id' => 1,
      'skipLineItem' => 1,
    ];

    $contribution = $this->callAPISuccess('Contribution', 'create', $params);
    $financialItems = $this->callAPISuccess('FinancialItem', 'get', ['return' => 'transaction_date']);
    foreach ($financialItems['values'] as $financialItem) {
      $this->assertEquals(date('Y-m-d H:i:s', strtotime($contribution['values'][$contribution['id']]['receive_date'])), date('Y-m-d H:i:s', strtotime($financialItem['transaction_date'])));
    }
    $this->callAPISuccessGetCount('LineItem', [
      'entity_id' => $contribution['id'],
      'entity_table' => 'civicrm_contribution',
      'sequential' => 1,
    ], 0);
  }

  /**
   * Test checks that passing in line items suppresses the create mechanism.
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreateContributionChainedLineItems(): void {
    $params = [
      'contact_id' => $this->_individualId,
      'receive_date' => '20120511',
      'total_amount' => 100.00,
      'financial_type_id' => $this->_financialTypeId,
      'payment_instrument_id' => 1,
      'non_deductible_amount' => 10.00,
      'fee_amount' => 50.00,
      'net_amount' => 90.00,
      'trxn_id' => 12345,
      'invoice_id' => 67890,
      'source' => 'SSF',
      'contribution_status_id' => 'Pending',
      'skipLineItem' => 1,
      'api.line_item.create' => [
        [
          'price_field_id' => 1,
          'qty' => 2,
          'line_total' => '20',
          'unit_price' => '10',
        ],
        [
          'price_field_id' => 1,
          'qty' => 1,
          'line_total' => '80',
          'unit_price' => '80',
        ],
      ],
    ];

    $description = 'Create Contribution with Nested Line Items.';
    $subFile = 'CreateWithNestedLineItems';
    $contribution = $this->callAPIAndDocument('Contribution', 'create', $params, __FUNCTION__, __FILE__, $description, $subFile);

    $this->callAPISuccessGetCount('LineItem', [
      'entity_id' => $contribution['id'],
      'contribution_id' => $contribution['id'],
      'entity_table' => 'civicrm_contribution',
      'sequential' => 1,
    ], 2);
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function testCreateContributionOffline(): void {
    $params = [
      'contact_id' => $this->_individualId,
      'receive_date' => '20120511',
      'total_amount' => 100.00,
      'financial_type_id' => 1,
      'trxn_id' => 12345,
      'invoice_id' => 67890,
      'source' => 'SSF',
      'contribution_status_id' => 1,
      'sequential' => 1,
    ];

    $contribution = $this->callAPISuccess('Contribution', 'create', $params)['values'][0];
    $this->assertEquals($this->_individualId, $contribution['contact_id']);
    $this->assertEquals(100.00, $contribution['total_amount']);
    $this->assertEquals(1, $contribution['financial_type_id']);
    $this->assertEquals(12345, $contribution['trxn_id']);
    $this->assertEquals(67890, $contribution['invoice_id']);
    $this->assertEquals('SSF', $contribution['source']);
    $this->assertEquals(1, $contribution['contribution_status_id']);
    $lineItems = $this->callAPISuccess('LineItem', 'get', [
      'entity_id' => $contribution['id'],
      'contribution_id' => $contribution['id'],
      'entity_table' => 'civicrm_contribution',
      'sequential' => 1,
      'return' => ['entity_id', 'contribution_id'],
    ]);
    $this->assertEquals(1, $lineItems['count']);
    $this->assertEquals($contribution['id'], $lineItems['values'][0]['entity_id']);
    $this->assertEquals($contribution['id'], $lineItems['values'][0]['contribution_id']);
    $this->_checkFinancialRecords($contribution, 'offline');
    $this->contributionGetnCheck($params, $contribution['id']);
  }

  /**
   * Test create with valid payment instrument.
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreateContributionWithPaymentInstrument(): void {
    $params = $this->_params + ['payment_instrument' => 'EFT'];
    $contribution = $this->callAPISuccess('contribution', 'create', $params);
    $contribution = $this->callAPISuccess('contribution', 'get', [
      'sequential' => 1,
      'id' => $contribution['id'],
      'return' => 'payment_instrument',
    ]);
    $this->assertArrayHasKey('payment_instrument', $contribution['values'][0]);
    $this->assertEquals('EFT', $contribution['values'][0]['payment_instrument']);

    $this->callAPISuccess('Contribution', 'create', [
      'id' => $contribution['id'],
      'payment_instrument' => 'Credit Card',
    ]);
    $contribution = $this->callAPISuccess('Contribution', 'get', [
      'sequential' => 1,
      'id' => $contribution['id'],
      'return' => 'payment_instrument',
    ]);
    $this->assertArrayHasKey('payment_instrument', $contribution['values'][0]);
    $this->assertEquals('Credit Card', $contribution['values'][0]['payment_instrument']);
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function testGetContributionByPaymentInstrument(): void {
    $params = $this->_params + ['payment_instrument' => 'EFT'];
    $params2 = $this->_params + ['payment_instrument' => 'Cash'];
    $this->callAPISuccess('contribution', 'create', $params);
    $this->callAPISuccess('contribution', 'create', $params2);
    $contribution = $this->callAPISuccess('Contribution', 'get', [
      'sequential' => 1,
      'contribution_payment_instrument' => 'Cash',
      'return' => 'payment_instrument',
    ]);
    $this->assertArrayHasKey('payment_instrument', $contribution['values'][0]);
    $this->assertEquals('Cash', $contribution['values'][0]['payment_instrument']);
    $this->assertEquals(1, $contribution['count']);
    $contribution = $this->callAPISuccess('Contribution', 'get', ['sequential' => 1, 'payment_instrument' => 'Cash', 'return' => 'payment_instrument']);
    $this->assertArrayHasKey('payment_instrument', $contribution['values'][0]);
    $this->assertEquals('Cash', $contribution['values'][0]['payment_instrument']);
    $this->assertEquals(1, $contribution['count']);
    $contribution = $this->callAPISuccess('Contribution', 'get', [
      'sequential' => 1,
      'payment_instrument_id' => 5,
      'return' => 'payment_instrument',
    ]);
    $this->assertArrayHasKey('payment_instrument', $contribution['values'][0]);
    $this->assertEquals('EFT', $contribution['values'][0]['payment_instrument']);
    $this->assertEquals(1, $contribution['count']);
    $contribution = $this->callAPISuccess('Contribution', 'get', [
      'sequential' => 1,
      'payment_instrument' => 'EFT',
      'return' => 'payment_instrument',
    ]);
    $this->assertArrayHasKey('payment_instrument', $contribution['values'][0]);
    $this->assertEquals('EFT', $contribution['values'][0]['payment_instrument']);
    $this->assertEquals(1, $contribution['count']);
    $contribution = $this->callAPISuccess('contribution', 'create', [
      'id' => $contribution['id'],
      'payment_instrument' => 'Credit Card',
      'return' => 'payment_instrument',
    ]);
    $contribution = $this->callAPISuccess('Contribution', 'get', [
      'sequential' => 1,
      'id' => $contribution['id'],
      'return' => 'payment_instrument',
    ]);
    $this->assertArrayHasKey('payment_instrument', $contribution['values'][0]);
    $this->assertEquals('Credit Card', $contribution['values'][0]['payment_instrument']);
    $this->assertEquals(1, $contribution['count']);
  }

  /**
   * CRM-16227 introduces invoice_id as a parameter.
   *
   * @throws \CRM_Core_Exception
   */
  public function testGetContributionByInvoice(): void {
    $this->callAPISuccess('Contribution', 'create', array_merge($this->_params, ['invoice_id' => 'curly']));
    $this->callAPISuccess('Contribution', 'create', array_merge($this->_params), ['invoice_id' => 'churlish']);
    $this->callAPISuccessGetCount('Contribution', [], 2);
    $this->callAPISuccessGetCount('Contribution', ['invoice_id' => 'curly'], 1);
  }

  /**
   * Check the credit note retrieval is case insensitive.
   *
   * @throws \CRM_Core_Exception
   */
  public function testGetCreditNoteCaseInsensitive(): void {
    $this->contributionCreate(['contact_id' => $this->_individualId]);
    $this->contributionCreate(['creditnote_id' => 'cN1234', 'contact_id' => $this->_individualId, 'invoice_id' => 91011, 'trxn_id' => 456]);
    $contribution = $this->callAPISuccess('Contribution', 'getsingle', ['creditnote_id' => 'CN1234', 'return' => 'creditnote_id']);
    $this->assertEquals('cN1234', $contribution['creditnote_id']);
  }

  /**
   * Test retrieval by total_amount works.
   *
   * @throws \CRM_Core_Exception
   */
  public function testGetContributionByTotalAmount(): void {
    $this->callAPISuccess('Contribution', 'create', array_merge($this->_params, ['total_amount' => '5']));
    $this->callAPISuccess('Contribution', 'create', array_merge($this->_params, ['total_amount' => '10']));
    $this->callAPISuccessGetCount('Contribution', ['total_amount' => 10], 1);
    $this->callAPISuccessGetCount('Contribution', ['total_amount' => ['>' => 6]], 1);
    $this->callAPISuccessGetCount('Contribution', ['total_amount' => ['>' => 0]], 2);
    $this->callAPISuccessGetCount('Contribution', ['total_amount' => ['>' => -5]], 2);
    $this->callAPISuccessGetCount('Contribution', ['total_amount' => ['<' => 0]], 0);
    $this->callAPISuccessGetCount('Contribution', [], 2);
  }

  /**
   * @dataProvider createLocalizedContributionDataProvider
   *
   * @param float|int|string $totalAmount
   * @param string $decimalPoint
   * @param string $thousandSeparator
   * @param string $currency
   * @param bool $expectedResult
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreateLocalizedContribution($totalAmount, string $decimalPoint, string $thousandSeparator, string $currency, bool $expectedResult): void {
    $this->setDefaultCurrency($currency);
    $this->setMonetaryDecimalPoint($decimalPoint);
    $this->setMonetaryThousandSeparator($thousandSeparator);

    $_params = [
      'contact_id' => $this->_individualId,
      'receive_date' => '20120511',
      'total_amount' => $totalAmount,
      'financial_type_id' => $this->_financialTypeId,
      'contribution_status_id' => 1,
    ];

    if ($expectedResult) {
      $this->callAPISuccess('Contribution', 'create', $_params);
    }
    else {
      $this->callAPIFailure('Contribution', 'create', $_params);
    }
  }

  /**
   * @return array
   */
  public function createLocalizedContributionDataProvider(): array {
    return [
      [10, '.', ',', 'USD', TRUE],
      ['145.0E+3', '.', ',', 'USD', FALSE],
      ['10', '.', ',', 'USD', TRUE],
      [-10, '.', ',', 'USD', TRUE],
      ['-10', '.', ',', 'USD', TRUE],
      ['-10foo', '.', ',', 'USD', FALSE],
      ['-10.0345619', '.', ',', 'USD', TRUE],
      ['-10.010,4345619', '.', ',', 'USD', TRUE],
      ['10.0104345619', '.', ',', 'USD', TRUE],
      ['-0', '.', ',', 'USD', TRUE],
      ['-.1', '.', ',', 'USD', TRUE],
      ['.1', '.', ',', 'USD', TRUE],
      // Test currency symbols too, default locale uses $, so if we wanted to test others we'd need to reconfigure locale
      ['$1,234,567.89', '.', ',', 'USD', TRUE],
      ['-$1,234,567.89', '.', ',', 'USD', TRUE],
      ['$-1,234,567.89', '.', ',', 'USD', TRUE],
      // This is the float format. Encapsulated in strings
      ['1234567.89', '.', ',', 'USD', TRUE],
      // This is the float format.
      [1234567.89, '.', ',', 'USD', TRUE],
      // Test EURO currency
      ['€1,234,567.89', '.', ',', 'EUR', TRUE],
      ['-€1,234,567.89', '.', ',', 'EUR', TRUE],
      ['€-1,234,567.89', '.', ',', 'EUR', TRUE],
      // This is the float format. Encapsulated in strings
      ['1234567.89', '.', ',', 'EUR', TRUE],
      // This is the float format.
      [1234567.89, '.', ',', 'EUR', TRUE],
      // Test Norwegian KR currency
      ['kr1,234,567.89', '.', ',', 'NOK', TRUE],
      ['kr 1,234,567.89', '.', ',', 'NOK', TRUE],
      ['-kr1,234,567.89', '.', ',', 'NOK', TRUE],
      ['-kr 1,234,567.89', '.', ',', 'NOK', TRUE],
      ['kr-1,234,567.89', '.', ',', 'NOK', TRUE],
      ['kr -1,234,567.89', '.', ',', 'NOK', TRUE],
      // This is the float format. Encapsulated in strings
      ['1234567.89', '.', ',', 'NOK', TRUE],
      // This is the float format.
      [1234567.89, '.', ',', 'NOK', TRUE],
      // Test different localization options: , as decimal separator and dot as thousand separator
      ['$1.234.567,89', ',', '.', 'USD', TRUE],
      ['-$1.234.567,89', ',', '.', 'USD', TRUE],
      ['$-1.234.567,89', ',', '.', 'USD', TRUE],
      ['1.234.567,89', ',', '.', 'USD', TRUE],
      // This is the float format. Encapsulated in strings
      ['1234567.89', ',', '.', 'USD', TRUE],
      // This is the float format.
      [1234567.89, ',', '.', 'USD', TRUE],
      ['$1,234,567.89', ',', '.', 'USD', FALSE],
      ['-$1,234,567.89', ',', '.', 'USD', FALSE],
      ['$-1,234,567.89', ',', '.', 'USD', FALSE],
      // Now with a space as thousand separator
      ['$1 234 567,89', ',', ' ', 'USD', TRUE],
      ['-$1 234 567,89', ',', ' ', 'USD', TRUE],
      ['$-1 234 567,89', ',', ' ', 'USD', TRUE],
      ['1 234 567,89', ',', ' ', 'USD', TRUE],
      // This is the float format. Encapsulated in strings
      ['1234567.89', ',', ' ', 'USD', TRUE],
      // This is the float format.
      [1234567.89, ',', ' ', 'USD', TRUE],
    ];
  }

  /**
   * Create test with unique field name on source.
   */
  public function testCreateContributionSource() {

    $params = [
      'contact_id' => $this->_individualId,
      'receive_date' => date('Ymd'),
      'total_amount' => 100.00,
      'financial_type_id' => $this->_financialTypeId,
      'payment_instrument_id' => 1,
      'non_deductible_amount' => 10.00,
      'fee_amount' => 50.00,
      'net_amount' => 90.00,
      'trxn_id' => 12345,
      'invoice_id' => 67890,
      'contribution_source' => 'SSF',
      'contribution_status_id' => 1,
    ];

    $contribution = $this->callAPISuccess('contribution', 'create', $params);
    $this->assertEquals($contribution['values'][$contribution['id']]['total_amount'], 100.00);
    $this->assertEquals($contribution['values'][$contribution['id']]['source'], 'SSF');
  }

  /**
   * Create test with unique field name on source.
   *
   * @param string $thousandSeparator
   *   punctuation used to refer to thousands.
   *
   * @dataProvider getThousandSeparators
   */
  public function testCreateDefaultNow($thousandSeparator) {
    $this->setCurrencySeparators($thousandSeparator);
    $params = $this->_params;
    unset($params['receive_date'], $params['net_amount']);

    $params['total_amount'] = $this->formatMoneyInput(5000.77);
    $params['fee_amount'] = $this->formatMoneyInput(.77);
    $params['skipCleanMoney'] = FALSE;

    $contribution = $this->callAPISuccess('contribution', 'create', $params);
    $contribution = $this->callAPISuccessGetSingle('contribution', ['id' => $contribution['id']]);
    $this->assertEquals(date('Y-m-d'), date('Y-m-d', strtotime($contribution['receive_date'])));
    $this->assertEquals(5000.77, $contribution['total_amount'], 'failed to handle ' . $this->formatMoneyInput(5000.77));
    $this->assertEquals(.77, $contribution['fee_amount']);
    $this->assertEquals(5000, $contribution['net_amount']);
  }

  /**
   * Create test with unique field name on source.
   */
  public function testCreateContributionNullOutThankyouDate() {

    $params = $this->_params;
    $params['thankyou_date'] = 'yesterday';

    $contribution = $this->callAPISuccess('contribution', 'create', $params);
    $contribution = $this->callAPISuccessGetSingle('contribution', ['id' => $contribution['id']]);
    $this->assertEquals(date('Y-m-d', strtotime('yesterday')), date('Y-m-d', strtotime($contribution['thankyou_date'])));

    $params['thankyou_date'] = 'null';
    $contribution = $this->callAPISuccess('contribution', 'create', $params);
    $this->assertTrue(empty($contribution['thankyou_date']));
  }

  /**
   * Create test with unique field name on source.
   */
  public function testCreateContributionSourceInvalidContact() {

    $params = [
      'contact_id' => 999,
      'receive_date' => date('Ymd'),
      'total_amount' => 100.00,
      'financial_type_id' => $this->_financialTypeId,
      'payment_instrument_id' => 1,
      'non_deductible_amount' => 10.00,
      'fee_amount' => 50.00,
      'net_amount' => 90.00,
      'trxn_id' => 12345,
      'invoice_id' => 67890,
      'contribution_source' => 'SSF',
      'contribution_status_id' => 1,
    ];

    $this->callAPIFailure('contribution', 'create', $params, 'contact_id is not valid : 999');
  }

  /**
   * Test note created correctly.
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreateContributionWithNote(): void {
    $description = 'Demonstrates creating contribution with Note Entity.';
    $subFile = 'ContributionCreateWithNote';
    $params = [
      'contact_id' => $this->_individualId,
      'receive_date' => '2012-01-01',
      'total_amount' => 100.00,
      'financial_type_id' => $this->_financialTypeId,
      'payment_instrument_id' => 1,
      'non_deductible_amount' => 10.00,
      'fee_amount' => 50.00,
      'net_amount' => 90.00,
      'trxn_id' => 12345,
      'invoice_id' => 67890,
      'source' => 'SSF',
      'contribution_status_id' => 1,
      'note' => 'my contribution note',
    ];

    $contribution = $this->callAPIAndDocument('contribution', 'create', $params, __FUNCTION__, __FILE__, $description, $subFile);
    $result = $this->callAPISuccess('note', 'get', [
      'entity_table' => 'civicrm_contribution',
      'entity_id' => $contribution['id'],
      'sequential' => 1,
      'return' => 'note',
    ]);
    $this->assertEquals('my contribution note', $result['values'][0]['note']);
    $this->callAPISuccess('contribution', 'delete', ['id' => $contribution['id']]);
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function testCreateContributionWithNoteUniqueNameAliases(): void {
    $params = [
      'contact_id' => $this->_individualId,
      'receive_date' => '2012-01-01',
      'total_amount' => 100.00,
      'financial_type_id' => $this->_financialTypeId,
      'payment_instrument_id' => 1,
      'non_deductible_amount' => 10.00,
      'fee_amount' => 50.00,
      'net_amount' => 90.00,
      'trxn_id' => 12345,
      'invoice_id' => 67890,
      'source' => 'SSF',
      'contribution_status_id' => 1,
      'contribution_note' => 'my contribution note',
    ];

    $contribution = $this->callAPISuccess('contribution', 'create', $params);
    $result = $this->callAPISuccess('note', 'get', [
      'entity_table' => 'civicrm_contribution',
      'entity_id' => $contribution['id'],
      'sequential' => 1,
      'return' => 'note',
    ]);
    $this->assertEquals('my contribution note', $result['values'][0]['note']);
  }

  /**
   * This is the test for creating soft credits.
   */
  public function testCreateContributionWithSoftCredit() {
    $description = "Demonstrates creating contribution with SoftCredit.";
    $subfile = "ContributionCreateWithSoftCredit";
    $contact2 = $this->callAPISuccess('Contact', 'create', [
      'display_name' => 'superman',
      'contact_type' => 'Individual',
    ]);
    $softParams = [
      'contact_id' => $contact2['id'],
      'amount' => 50,
      'soft_credit_type_id' => 3,
    ];

    $params = $this->_params + ['soft_credit' => [1 => $softParams]];
    $contribution = $this->callAPIAndDocument('contribution', 'create', $params, __FUNCTION__, __FILE__, $description, $subfile);
    $result = $this->callAPISuccess('contribution', 'get', ['return' => 'soft_credit', 'sequential' => 1]);

    $this->assertEquals($softParams['contact_id'], $result['values'][0]['soft_credit'][1]['contact_id']);
    $this->assertEquals($softParams['amount'], $result['values'][0]['soft_credit'][1]['amount']);
    $this->assertEquals($softParams['soft_credit_type_id'], $result['values'][0]['soft_credit'][1]['soft_credit_type']);

    $this->callAPISuccess('contribution', 'delete', ['id' => $contribution['id']]);
    $this->callAPISuccess('contact', 'delete', ['id' => $contact2['id']]);
  }

  public function testCreateContributionWithSoftCreditDefaults() {
    $description = "Demonstrates creating contribution with Soft Credit defaults for amount and type.";
    $subfile = "ContributionCreateWithSoftCreditDefaults";
    $contact2 = $this->callAPISuccess('Contact', 'create', [
      'display_name' => 'superman',
      'contact_type' => 'Individual',
    ]);
    $params = $this->_params + [
      'soft_credit_to' => $contact2['id'],
    ];
    $contribution = $this->callAPIAndDocument('contribution', 'create', $params, __FUNCTION__, __FILE__, $description, $subfile);
    $result = $this->callAPISuccess('contribution', 'get', ['return' => 'soft_credit', 'sequential' => 1]);

    $this->assertEquals($contact2['id'], $result['values'][0]['soft_credit'][1]['contact_id']);
    // Default soft credit amount = contribution.total_amount
    $this->assertEquals($this->_params['total_amount'], $result['values'][0]['soft_credit'][1]['amount']);
    $this->assertEquals(CRM_Core_OptionGroup::getDefaultValue("soft_credit_type"), $result['values'][0]['soft_credit'][1]['soft_credit_type']);

    $this->callAPISuccess('contribution', 'delete', ['id' => $contribution['id']]);
    $this->callAPISuccess('contact', 'delete', ['id' => $contact2['id']]);
  }

  /**
   * Test creating contribution with Soft Credit by passing in honor_contact_id.
   */
  public function testCreateContributionWithHonoreeContact() {
    $description = "Demonstrates creating contribution with Soft Credit by passing in honor_contact_id.";
    $subfile = "ContributionCreateWithHonoreeContact";
    $contact2 = $this->callAPISuccess('Contact', 'create', [
      'display_name' => 'superman',
      'contact_type' => 'Individual',
    ]);
    $params = $this->_params + [
      'honor_contact_id' => $contact2['id'],
    ];
    $contribution = $this->callAPIAndDocument('contribution', 'create', $params, __FUNCTION__, __FILE__, $description, $subfile);
    $result = $this->callAPISuccess('contribution', 'get', ['return' => 'soft_credit', 'sequential' => 1]);

    $this->assertEquals($contact2['id'], $result['values'][0]['soft_credit'][1]['contact_id']);
    // Default soft credit amount = contribution.total_amount
    // Legacy mode in create api (honor_contact_id param) uses the standard "In Honor of" soft credit type
    $this->assertEquals($this->_params['total_amount'], $result['values'][0]['soft_credit'][1]['amount']);
    $softCreditValueTypeID = $result['values'][0]['soft_credit'][1]['soft_credit_type'];
    $this->assertEquals('in_honor_of', CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_ContributionSoft', 'soft_credit_type_id', $softCreditValueTypeID));

    $this->callAPISuccess('contribution', 'delete', ['id' => $contribution['id']]);
    $this->callAPISuccess('contact', 'delete', ['id' => $contact2['id']]);
  }

  /**
   * Test using example code.
   */
  public function testContributionCreateExample() {
    //make sure at least on page exists since there is a truncate in tear down
    $this->callAPISuccess('contribution_page', 'create', $this->_pageParams);
    require_once 'api/v3/examples/Contribution/Create.ex.php';
    $result = contribution_create_example();
    $id = $result['id'];
    $expectedResult = contribution_create_expectedresult();
    $this->checkArrayEquals($expectedResult, $result);
    $this->contributionDelete($id);
  }

  /**
   * Function tests that additional financial records are created when fee amount is recorded.
   */
  public function testCreateContributionWithFee() {
    $params = [
      'contact_id' => $this->_individualId,
      'receive_date' => '20120511',
      'total_amount' => 100.00,
      'fee_amount' => 50,
      'financial_type_id' => 1,
      'trxn_id' => 12345,
      'invoice_id' => 67890,
      'source' => 'SSF',
      'contribution_status_id' => 1,
    ];

    $contribution = $this->callAPISuccess('contribution', 'create', $params);
    $this->assertEquals($contribution['values'][$contribution['id']]['contact_id'], $this->_individualId);
    $this->assertEquals($contribution['values'][$contribution['id']]['total_amount'], 100.00);
    $this->assertEquals($contribution['values'][$contribution['id']]['fee_amount'], 50.00);
    $this->assertEquals($contribution['values'][$contribution['id']]['net_amount'], 50.00);
    $this->assertEquals($contribution['values'][$contribution['id']]['financial_type_id'], 1);
    $this->assertEquals($contribution['values'][$contribution['id']]['trxn_id'], 12345);
    $this->assertEquals($contribution['values'][$contribution['id']]['invoice_id'], 67890);
    $this->assertEquals($contribution['values'][$contribution['id']]['source'], 'SSF');
    $this->assertEquals($contribution['values'][$contribution['id']]['contribution_status_id'], 1);

    $lineItems = $this->callAPISuccess('line_item', 'get', [

      'entity_id' => $contribution['id'],
      'entity_table' => 'civicrm_contribution',
      'sequential' => 1,
      'return' => ['entity_id', 'contribution_id'],
    ]);
    $this->assertEquals(1, $lineItems['count']);
    $this->assertEquals($contribution['id'], $lineItems['values'][0]['entity_id']);
    $this->assertEquals($contribution['id'], $lineItems['values'][0]['contribution_id']);
    $this->callAPISuccessGetCount('line_item', [

      'entity_id' => $contribution['id'],
      'contribution_id' => $contribution['id'],
      'entity_table' => 'civicrm_contribution',
      'sequential' => 1,
    ], 1);
    $this->_checkFinancialRecords($contribution, 'feeAmount');
  }

  /**
   * Function tests that additional financial records are created when online
   * contribution is created.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testCreateContributionOnline(): void {
    CRM_Financial_BAO_PaymentProcessor::create($this->_processorParams);
    $contributionPage = $this->callAPISuccess('contribution_page', 'create', $this->_pageParams);
    $this->assertAPISuccess($contributionPage);
    $params = [
      'contact_id' => $this->_individualId,
      'receive_date' => '20120511',
      'total_amount' => 100.00,
      'financial_type_id' => 1,
      'contribution_page_id' => $contributionPage['id'],
      'payment_processor' => $this->paymentProcessorID,
      'trxn_id' => 12345,
      'invoice_id' => 67890,
      'source' => 'SSF',
      'contribution_status_id' => 1,

    ];

    $contribution = $this->callAPISuccess('Contribution', 'create', $params);
    $this->assertEquals($contribution['values'][$contribution['id']]['contact_id'], $this->_individualId);
    $this->assertEquals($contribution['values'][$contribution['id']]['total_amount'], 100.00);
    $this->assertEquals($contribution['values'][$contribution['id']]['financial_type_id'], 1);
    $this->assertEquals($contribution['values'][$contribution['id']]['trxn_id'], 12345);
    $this->assertEquals($contribution['values'][$contribution['id']]['invoice_id'], 67890);
    $this->assertEquals($contribution['values'][$contribution['id']]['source'], 'SSF');
    $this->assertEquals($contribution['values'][$contribution['id']]['contribution_status_id'], 1);
    $contribution['payment_instrument_id'] = $this->callAPISuccessGetValue('PaymentProcessor', [
      'id' => $this->paymentProcessorID,
      'return' => 'payment_instrument_id',
    ]);
    $this->_checkFinancialRecords($contribution, 'online');
  }

  /**
   * Check handling of financial type.
   *
   * In the interests of removing financial type / contribution type checks from
   * legacy format function lets test that the api is doing this for us
   */
  public function testCreateInvalidFinancialType(): void {
    $params = $this->_params;
    $params['financial_type_id'] = 99999;
    $this->callAPIFailure($this->entity, 'create', $params);
  }

  /**
   * Check handling of financial type.
   *
   * In the interests of removing financial type / contribution type checks from
   * legacy format function lets test that the api is doing this for us
   *
   * @throws \CRM_Core_Exception
   */
  public function testValidNamedFinancialType() {
    $params = $this->_params;
    $params['financial_type_id'] = 'Donation';
    $this->callAPISuccess($this->entity, 'create', $params);
  }

  /**
   * Tests that additional financial records are created.
   *
   * Checks when online contribution with pay later option is created
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreateContributionPayLaterOnline(): void {
    $this->_pageParams['is_pay_later'] = 1;
    $contributionPage = $this->callAPISuccess('contribution_page', 'create', $this->_pageParams);
    $this->assertAPISuccess($contributionPage);
    $params = [
      'contact_id' => $this->_individualId,
      'receive_date' => '20120511',
      'total_amount' => 100.00,
      'financial_type_id' => 1,
      'contribution_page_id' => $contributionPage['id'],
      'trxn_id' => 12345,
      'is_pay_later' => 1,
      'invoice_id' => 67890,
      'source' => 'SSF',
      'contribution_status_id' => 'Pending',

    ];

    $contribution = $this->callAPIAndDocument('Contribution', 'create', $params, __FUNCTION__, __FILE__);
    $contribution = $contribution['values'][$contribution['id']];
    $this->assertEquals($contribution['contact_id'], $this->_individualId);
    $this->assertEquals($contribution['total_amount'], 100.00);
    $this->assertEquals($contribution['financial_type_id'], 1);
    $this->assertEquals($contribution['trxn_id'], 12345);
    $this->assertEquals($contribution['invoice_id'], 67890);
    $this->assertEquals($contribution['source'], 'SSF');
    $this->assertEquals($contribution['contribution_status_id'], 2);
    $this->_checkFinancialRecords($contribution, 'payLater');
  }

  /**
   * Function tests that additional financial records are created for online contribution with pending option.
   */
  public function testCreateContributionPendingOnline() {
    CRM_Financial_BAO_PaymentProcessor::create($this->_processorParams);
    $contributionPage = $this->callAPISuccess('contribution_page', 'create', $this->_pageParams);
    $this->assertAPISuccess($contributionPage);
    $params = [
      'contact_id' => $this->_individualId,
      'receive_date' => '20120511',
      'total_amount' => 100.00,
      'financial_type_id' => 1,
      'contribution_page_id' => $contributionPage['id'],
      'trxn_id' => 12345,
      'invoice_id' => 67890,
      'source' => 'SSF',
      'contribution_status_id' => 2,
    ];

    $contribution = $this->callAPISuccess('contribution', 'create', $params);
    $this->assertEquals($contribution['values'][$contribution['id']]['contact_id'], $this->_individualId);
    $this->assertEquals($contribution['values'][$contribution['id']]['total_amount'], 100.00);
    $this->assertEquals($contribution['values'][$contribution['id']]['financial_type_id'], 1);
    $this->assertEquals($contribution['values'][$contribution['id']]['trxn_id'], 12345);
    $this->assertEquals($contribution['values'][$contribution['id']]['invoice_id'], 67890);
    $this->assertEquals($contribution['values'][$contribution['id']]['source'], 'SSF');
    $this->assertEquals(2, $contribution['values'][$contribution['id']]['contribution_status_id']);
    $this->_checkFinancialRecords($contribution, 'pending');
  }

  /**
   * Test that BAO defaults work.
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreateBAODefaults() {
    unset($this->_params['contribution_source_id'], $this->_params['payment_instrument_id']);
    $contribution = $this->callAPISuccess('Contribution', 'create', $this->_params);
    $contribution = $this->callAPISuccess('Contribution', 'getsingle', [
      'id' => $contribution['id'],
      'api.contribution.delete' => 1,
      'return' => ['contribution_status_id', 'payment_instrument'],
    ]);
    $this->assertEquals(1, $contribution['contribution_status_id']);
    $this->assertEquals('Check', $contribution['payment_instrument']);
    $this->callAPISuccessGetCount('Contribution', ['id' => $contribution['id']], 0);
  }

  /**
   * Test that getsingle can be chained with delete.
   *
   * @throws CRM_Core_Exception
   */
  public function testDeleteChainedGetSingle() {
    $contribution = $this->callAPISuccess('Contribution', 'create', $this->_params);
    $contribution = $this->callAPISuccess('Contribution', 'getsingle', [
      'id' => $contribution['id'],
      'api.contribution.delete' => 1,
      'return' => 'id',
    ]);
    $this->callAPISuccessGetCount('Contribution', ['id' => $contribution['id']], 0);
  }

  /**
   * Function tests that line items, financial records are updated when contribution amount is changed.
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreateUpdateContributionChangeTotal() {
    $contribution = $this->callAPISuccess('contribution', 'create', $this->_params);
    $lineItems = $this->callAPISuccess('line_item', 'getvalue', [

      'entity_id' => $contribution['id'],
      'entity_table' => 'civicrm_contribution',
      'sequential' => 1,
      'return' => 'line_total',
    ]);
    $this->assertEquals('100.00', $lineItems);
    $trxnAmount = $this->_getFinancialTrxnAmount($contribution['id']);
    // Financial trxn SUM = 100 + 5 (fee)
    $this->assertEquals('105.00', $trxnAmount);
    $newParams = [

      'id' => $contribution['id'],
      'total_amount' => '125',
    ];
    $contribution = $this->callAPISuccess('Contribution', 'create', $newParams);

    $lineItems = $this->callAPISuccess('line_item', 'getvalue', [

      'entity_id' => $contribution['id'],
      'entity_table' => 'civicrm_contribution',
      'sequential' => 1,
      'return' => 'line_total',
    ]);

    $this->assertEquals('125.00', $lineItems);
    $trxnAmount = $this->_getFinancialTrxnAmount($contribution['id']);

    // Financial trxn SUM = 125 + 5 (fee).
    $this->assertEquals('130.00', $trxnAmount);
    $this->assertEquals('125.00', $this->_getFinancialItemAmount($contribution['id']));
  }

  /**
   * Function tests that line items, financial records are updated when pay later contribution is received.
   */
  public function testCreateUpdateContributionPayLater() {
    $contribParams = [
      'contact_id' => $this->_individualId,
      'receive_date' => '2012-01-01',
      'total_amount' => 100.00,
      'financial_type_id' => $this->_financialTypeId,
      'payment_instrument_id' => 1,
      'contribution_status_id' => 2,
      'is_pay_later' => 1,

    ];
    $contribution = $this->callAPISuccess('contribution', 'create', $contribParams);

    $newParams = array_merge($contribParams, [
      'id' => $contribution['id'],
      'contribution_status_id' => 1,
    ]);
    $contribution = $this->callAPISuccess('contribution', 'create', $newParams);
    $contribution = $contribution['values'][$contribution['id']];
    $this->assertEquals($contribution['contribution_status_id'], '1');
    $this->_checkFinancialItem($contribution['id'], 'paylater');
    $this->_checkFinancialTrxn($contribution, 'payLater');
  }

  /**
   * Function tests that financial records are updated when Payment Instrument is changed.
   */
  public function testCreateUpdateContributionPaymentInstrument() {
    $instrumentId = $this->_addPaymentInstrument();
    $contribParams = [
      'contact_id' => $this->_individualId,
      'total_amount' => 100.00,
      'financial_type_id' => $this->_financialTypeId,
      'payment_instrument_id' => 4,
      'contribution_status_id' => 1,

    ];
    $contribution = $this->callAPISuccess('contribution', 'create', $contribParams);

    $newParams = array_merge($contribParams, [
      'id' => $contribution['id'],
      'payment_instrument_id' => $instrumentId,
    ]);
    $contribution = $this->callAPISuccess('contribution', 'create', $newParams);
    $this->assertAPISuccess($contribution);
    $this->checkFinancialTrxnPaymentInstrumentChange($contribution['id'], 4, $instrumentId);

    // cleanup - delete created payment instrument
    $this->_deletedAddedPaymentInstrument();
  }

  /**
   * Function tests that financial records are updated when Payment Instrument is changed when amount is negative.
   */
  public function testCreateUpdateNegativeContributionPaymentInstrument() {
    $instrumentId = $this->_addPaymentInstrument();
    $contribParams = [
      'contact_id' => $this->_individualId,
      'total_amount' => -100.00,
      'financial_type_id' => $this->_financialTypeId,
      'payment_instrument_id' => 4,
      'contribution_status_id' => 1,

    ];
    $contribution = $this->callAPISuccess('contribution', 'create', $contribParams);

    $newParams = array_merge($contribParams, [
      'id' => $contribution['id'],
      'payment_instrument_id' => $instrumentId,
    ]);
    $contribution = $this->callAPISuccess('contribution', 'create', $newParams);
    $this->assertAPISuccess($contribution);
    $this->checkFinancialTrxnPaymentInstrumentChange($contribution['id'], 4, $instrumentId, -100);

    // cleanup - delete created payment instrument
    $this->_deletedAddedPaymentInstrument();
  }

  /**
   * Function tests that financial records are added when Contribution is Refunded.
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreateUpdateContributionRefund() {
    $contributionParams = [
      'contact_id' => $this->_individualId,
      'receive_date' => '2012-01-01',
      'total_amount' => 100.00,
      'financial_type_id' => $this->_financialTypeId,
      'payment_instrument_id' => 4,
      'contribution_status_id' => 1,
      'trxn_id' => 'original_payment',
    ];
    $contribution = $this->callAPISuccess('Contribution', 'create', $contributionParams);
    $newParams = array_merge($contributionParams, [
      'id' => $contribution['id'],
      'contribution_status_id' => 'Refunded',
      'cancel_date' => '2015-01-01 09:00',
      'refund_trxn_id' => 'the refund',
    ]);

    $contribution = $this->callAPISuccess('contribution', 'create', $newParams);
    $this->_checkFinancialTrxn($contribution, 'refund');
    $this->_checkFinancialItem($contribution['id'], 'refund');
    $this->assertEquals('original_payment', $this->callAPISuccessGetValue('Contribution', [
      'id' => $contribution['id'],
      'return' => 'trxn_id',
    ]));
  }

  /**
   * Refund a contribution for a financial type with a contra account.
   *
   * CRM-17951 the contra account is a financial account with a relationship to a
   * financial type. It is not always configured but should be reflected
   * in the financial_trxn & financial_item table if it is.
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreateUpdateChargebackContributionDefaultAccount() {
    $contribution = $this->callAPISuccess('Contribution', 'create', $this->_params);
    $this->callAPISuccess('Contribution', 'create', [
      'id' => $contribution['id'],
      'contribution_status_id' => 'Chargeback',
    ]);
    $this->callAPISuccessGetSingle('Contribution', ['contribution_status_id' => 'Chargeback']);

    $lineItems = $this->callAPISuccessGetSingle('LineItem', [
      'contribution_id' => $contribution['id'],
      'api.FinancialItem.getsingle' => ['amount' => ['<' => 0]],
    ]);
    $this->assertEquals(1, $lineItems['api.FinancialItem.getsingle']['financial_account_id']);
    $this->callAPISuccessGetSingle('FinancialTrxn', [
      'total_amount' => -100,
      'status_id' => 'Chargeback',
      'to_financial_account_id' => 6,
    ]);
  }

  /**
   * Refund a contribution for a financial type with a contra account.
   *
   * CRM-17951 the contra account is a financial account with a relationship to a
   * financial type. It is not always configured but should be reflected
   * in the financial_trxn & financial_item table if it is.
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreateUpdateChargebackContributionCustomAccount() {
    $financialAccount = $this->callAPISuccess('FinancialAccount', 'create', [
      'name' => 'Chargeback Account',
      'is_active' => TRUE,
    ]);

    $entityFinancialAccount = $this->callAPISuccess('EntityFinancialAccount', 'create', [
      'entity_id' => $this->_financialTypeId,
      'entity_table' => 'civicrm_financial_type',
      'account_relationship' => 'Chargeback Account is',
      'financial_account_id' => 'Chargeback Account',
    ]);

    $contribution = $this->callAPISuccess('Contribution', 'create', $this->_params);
    $this->callAPISuccess('Contribution', 'create', [
      'id' => $contribution['id'],
      'contribution_status_id' => 'Chargeback',
    ]);
    $this->callAPISuccessGetSingle('Contribution', ['contribution_status_id' => 'Chargeback']);

    $lineItems = $this->callAPISuccessGetSingle('LineItem', [
      'contribution_id' => $contribution['id'],
      'api.FinancialItem.getsingle' => ['amount' => ['<' => 0]],
    ]);
    $this->assertEquals($financialAccount['id'], $lineItems['api.FinancialItem.getsingle']['financial_account_id']);

    $this->callAPISuccess('Contribution', 'delete', ['id' => $contribution['id']]);
    $this->callAPISuccess('EntityFinancialAccount', 'delete', ['id' => $entityFinancialAccount['id']]);
    $this->callAPISuccess('FinancialAccount', 'delete', ['id' => $financialAccount['id']]);
  }

  /**
   * Refund a contribution for a financial type with a contra account.
   *
   * CRM-17951 the contra account is a financial account with a relationship to a
   * financial type. It is not always configured but should be reflected
   * in the financial_trxn & financial_item table if it is.
   */
  public function testCreateUpdateRefundContributionConfiguredContraAccount() {
    $financialAccount = $this->callAPISuccess('FinancialAccount', 'create', [
      'name' => 'Refund Account',
      'is_active' => TRUE,
    ]);

    $entityFinancialAccount = $this->callAPISuccess('EntityFinancialAccount', 'create', [
      'entity_id' => $this->_financialTypeId,
      'entity_table' => 'civicrm_financial_type',
      'account_relationship' => 'Credit/Contra Revenue Account is',
      'financial_account_id' => 'Refund Account',
    ]);

    $contribution = $this->callAPISuccess('Contribution', 'create', $this->_params);
    $this->callAPISuccess('Contribution', 'create', [
      'id' => $contribution['id'],
      'contribution_status_id' => 'Refunded',
    ]);

    $lineItems = $this->callAPISuccessGetSingle('LineItem', [
      'contribution_id' => $contribution['id'],
      'api.FinancialItem.getsingle' => ['amount' => ['<' => 0]],
    ]);
    $this->assertEquals($financialAccount['id'], $lineItems['api.FinancialItem.getsingle']['financial_account_id']);

    $this->callAPISuccess('Contribution', 'delete', ['id' => $contribution['id']]);
    $this->callAPISuccess('EntityFinancialAccount', 'delete', ['id' => $entityFinancialAccount['id']]);
    $this->callAPISuccess('FinancialAccount', 'delete', ['id' => $financialAccount['id']]);
  }

  /**
   * Function tests that trxn_id is set when passed in.
   *
   * Here we ensure that the civicrm_financial_trxn.trxn_id & the civicrm_contribution.trxn_id are set
   * when trxn_id is passed in.
   */
  public function testCreateUpdateContributionRefundTrxnIDPassedIn() {
    $contributionParams = [
      'contact_id' => $this->_individualId,
      'receive_date' => '2012-01-01',
      'total_amount' => 100.00,
      'financial_type_id' => $this->_financialTypeId,
      'payment_instrument_id' => 4,
      'contribution_status_id' => 1,
      'trxn_id' => 'original_payment',
    ];
    $contribution = $this->callAPISuccess('contribution', 'create', $contributionParams);
    $newParams = array_merge($contributionParams, [
      'id' => $contribution['id'],
      'contribution_status_id' => 'Refunded',
      'cancel_date' => '2015-01-01 09:00',
      'trxn_id' => 'the refund',
    ]);

    $contribution = $this->callAPISuccess('contribution', 'create', $newParams);
    $this->_checkFinancialTrxn($contribution, 'refund');
    $this->_checkFinancialItem($contribution['id'], 'refund');
    $this->assertEquals('the refund', $this->callAPISuccessGetValue('Contribution', [
      'id' => $contribution['id'],
      'return' => 'trxn_id',
    ]));
  }

  /**
   * Function tests that trxn_id is set when passed in.
   *
   * Here we ensure that the civicrm_contribution.trxn_id is set
   * when trxn_id is passed in but if refund_trxn_id is different then that
   * is kept for the refund transaction.
   */
  public function testCreateUpdateContributionRefundRefundAndTrxnIDPassedIn() {
    $contributionParams = [
      'contact_id' => $this->_individualId,
      'receive_date' => '2012-01-01',
      'total_amount' => 100.00,
      'financial_type_id' => $this->_financialTypeId,
      'payment_instrument_id' => 4,
      'contribution_status_id' => 1,
      'trxn_id' => 'original_payment',
    ];
    $contribution = $this->callAPISuccess('contribution', 'create', $contributionParams);
    $newParams = array_merge($contributionParams, [
      'id' => $contribution['id'],
      'contribution_status_id' => 'Refunded',
      'cancel_date' => '2015-01-01 09:00',
      'trxn_id' => 'cont id',
      'refund_trxn_id' => 'the refund',
    ]);

    $contribution = $this->callAPISuccess('contribution', 'create', $newParams);
    $this->_checkFinancialTrxn($contribution, 'refund');
    $this->_checkFinancialItem($contribution['id'], 'refund');
    $this->assertEquals('cont id', $this->callAPISuccessGetValue('Contribution', [
      'id' => $contribution['id'],
      'return' => 'trxn_id',
    ]));
  }

  /**
   * Function tests that refund_trxn_id is set when passed in empty.
   *
   * Here we ensure that the civicrm_contribution.trxn_id is set
   * when trxn_id is passed in but if refund_trxn_id isset but empty then that
   * is kept for the refund transaction.
   */
  public function testCreateUpdateContributionRefundRefundNullTrxnIDPassedIn() {
    $contributionParams = [
      'contact_id' => $this->_individualId,
      'receive_date' => '2012-01-01',
      'total_amount' => 100.00,
      'financial_type_id' => $this->_financialTypeId,
      'payment_instrument_id' => 4,
      'contribution_status_id' => 1,
      'trxn_id' => 'original_payment',
    ];
    $contribution = $this->callAPISuccess('contribution', 'create', $contributionParams);
    $newParams = array_merge($contributionParams, [
      'id' => $contribution['id'],
      'contribution_status_id' => 'Refunded',
      'cancel_date' => '2015-01-01 09:00',
      'trxn_id' => 'cont id',
      'refund_trxn_id' => '',
    ]);

    $contribution = $this->callAPISuccess('contribution', 'create', $newParams);
    $this->_checkFinancialTrxn($contribution, 'refund', NULL, ['trxn_id' => NULL]);
    $this->_checkFinancialItem($contribution['id'], 'refund');
    $this->assertEquals('cont id', $this->callAPISuccessGetValue('Contribution', [
      'id' => $contribution['id'],
      'return' => 'trxn_id',
    ]));
  }

  /**
   * Function tests invalid contribution status change.
   */
  public function testCreateUpdateContributionInValidStatusChange() {
    $contribParams = [
      'contact_id' => 1,
      'receive_date' => '2012-01-01',
      'total_amount' => 100.00,
      'financial_type_id' => 1,
      'payment_instrument_id' => 1,
      'contribution_status_id' => 1,
    ];
    $contribution = $this->callAPISuccess('contribution', 'create', $contribParams);
    $newParams = array_merge($contribParams, [
      'id' => $contribution['id'],
      'contribution_status_id' => 2,
    ]);
    $this->callAPIFailure('contribution', 'create', $newParams, ts('Cannot change contribution status from Completed to Pending.'));

  }

  /**
   * Function tests that financial records are added when Pending Contribution is Canceled.
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreateUpdateContributionCancelPending() {
    $contribParams = [
      'contact_id' => $this->_individualId,
      'receive_date' => '2012-01-01',
      'total_amount' => 100.00,
      'financial_type_id' => $this->_financialTypeId,
      'payment_instrument_id' => 1,
      'contribution_status_id' => 2,
      'is_pay_later' => 1,

    ];
    $contribution = $this->callAPISuccess('contribution', 'create', $contribParams);
    $newParams = array_merge($contribParams, [
      'id' => $contribution['id'],
      'contribution_status_id' => 3,
      'cancel_date' => '2012-02-02 09:00',
    ]);
    //Check if trxn_date is same as cancel_date.
    $checkTrxnDate = [
      'trxn_date' => '2012-02-02 09:00:00',
    ];
    $contribution = $this->callAPISuccess('contribution', 'create', $newParams);
    $this->_checkFinancialTrxn($contribution, 'cancelPending', NULL, $checkTrxnDate);
    $this->_checkFinancialItem($contribution['id'], 'cancelPending');
  }

  /**
   * Function tests that financial records are added when Financial Type is Changed.
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreateUpdateContributionChangeFinancialType() {
    $contribParams = [
      'contact_id' => $this->_individualId,
      'receive_date' => '2012-01-01',
      'total_amount' => 100.00,
      'financial_type_id' => 1,
      'payment_instrument_id' => 1,
      'contribution_status_id' => 1,

    ];
    $contribution = $this->callAPISuccess('contribution', 'create', $contribParams);
    $newParams = array_merge($contribParams, [
      'id' => $contribution['id'],
      'financial_type_id' => 3,
    ]);
    $contribution = $this->callAPISuccess('contribution', 'create', $newParams);
    $this->_checkFinancialTrxn($contribution, 'changeFinancial');
    $this->_checkFinancialItem($contribution['id'], 'changeFinancial');
  }

  /**
   * Function tets that financial records are correctly added when financial type is changed
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreateUpdateContributionWithFeeAmountChangeFinancialType() {
    $contribParams = [
      'contact_id' => $this->_individualId,
      'receive_date' => '2012-01-01',
      'total_amount' => 100.00,
      'fee_amount' => 0.57,
      'financial_type_id' => 1,
      'payment_instrument_id' => 1,
      'contribution_status_id' => 1,

    ];
    $contribution = $this->callAPISuccess('contribution', 'create', $contribParams);
    $newParams = array_merge($contribParams, [
      'id' => $contribution['id'],
      'financial_type_id' => 3,
    ]);
    $contribution = $this->callAPISuccess('contribution', 'create', $newParams);
    $this->_checkFinancialTrxn($contribution, 'changeFinancial', NULL, ['fee_amount' => '-0.57', 'net_amount' => '-99.43']);
    $this->_checkFinancialItem($contribution['id'], 'changeFinancial');
  }

  /**
   * Test that update does not change status id CRM-15105.
   */
  public function testCreateUpdateWithoutChangingPendingStatus() {
    $contribution = $this->callAPISuccess('contribution', 'create', array_merge($this->_params, ['contribution_status_id' => 2]));
    $this->callAPISuccess('contribution', 'create', ['id' => $contribution['id'], 'source' => 'new source']);
    $contribution = $this->callAPISuccess('contribution', 'getsingle', [
      'id' => $contribution['id'],
      'api.contribution.delete' => 1,
    ]);
    $this->assertEquals(2, $contribution['contribution_status_id']);
  }

  /**
   * Test Updating a Contribution.
   *
   * CHANGE: we require the API to do an incremental update
   */
  public function testCreateUpdateContribution() {
    $contributionID = $this->contributionCreate([
      'contact_id' => $this->_individualId,
      'trxn_id' => 212355,
      'financial_type_id' => $this->_financialTypeId,
      'invoice_id' => 'old_invoice',
    ]);
    $old_params = [
      'contribution_id' => $contributionID,
    ];
    $original = $this->callAPISuccess('contribution', 'get', $old_params);
    $this->assertEquals($original['id'], $contributionID);
    //set up list of old params, verify

    //This should not be required on update:
    $old_contact_id = $original['values'][$contributionID]['contact_id'];
    $old_payment_instrument = $original['values'][$contributionID]['instrument_id'];
    $old_fee_amount = $original['values'][$contributionID]['fee_amount'];
    $old_source = $original['values'][$contributionID]['contribution_source'];

    $old_trxn_id = $original['values'][$contributionID]['trxn_id'];
    $old_invoice_id = $original['values'][$contributionID]['invoice_id'];

    //check against values in CiviUnitTestCase::createContribution()
    $this->assertEquals($old_contact_id, $this->_individualId);
    $this->assertEquals($old_fee_amount, 5.00);
    $this->assertEquals($old_source, 'SSF');
    $this->assertEquals($old_trxn_id, 212355);
    $this->assertEquals($old_invoice_id, 'old_invoice');
    $params = [
      'id' => $contributionID,
      'contact_id' => $this->_individualId,
      'total_amount' => 105.00,
      'fee_amount' => 7.00,
      'financial_type_id' => $this->_financialTypeId,
      'non_deductible_amount' => 22.00,
      'contribution_status_id' => 1,
      'note' => 'Donating for Noble Cause',
    ];

    $contribution = $this->callAPISuccess('contribution', 'create', $params);

    $new_params = [
      'contribution_id' => $contribution['id'],
    ];
    $contribution = $this->callAPISuccessGetSingle('contribution', $new_params);

    $this->assertEquals($contribution['contact_id'], $this->_individualId);
    $this->assertEquals($contribution['total_amount'], 105.00);
    $this->assertEquals($contribution['financial_type_id'], $this->_financialTypeId);
    $this->assertEquals($contribution['financial_type'], 'Donation');
    $this->assertEquals($contribution['instrument_id'], $old_payment_instrument);
    $this->assertEquals($contribution['non_deductible_amount'], 22.00);
    $this->assertEquals($contribution['fee_amount'], 7.00);
    $this->assertEquals($contribution['trxn_id'], $old_trxn_id);
    $this->assertEquals($contribution['invoice_id'], $old_invoice_id);
    $this->assertEquals($contribution['contribution_source'], $old_source);
    $this->assertEquals($contribution['contribution_status'], 'Completed');

    $this->assertEquals($contribution['net_amount'], $contribution['total_amount'] - $contribution['fee_amount']);

    $params = [
      'contribution_id' => $contributionID,
    ];
    $result = $this->callAPISuccess('contribution', 'delete', $params);
    $this->assertAPISuccess($result);
  }

  /**
   * Check that net_amount is updated when a contribution is updated.
   *
   * Update fee amount AND total amount, just fee amount, just total amount
   * and neither to check that net_amount is keep updated.
   */
  public function testUpdateContributionNetAmountVariants() {
    $contributionID = $this->contributionCreate(['contact_id' => $this->individualCreate()]);

    $this->callAPISuccess('Contribution', 'create', [
      'id' => $contributionID,
      'total_amount' => 90,
      'fee_amount' => 6,
    ]);
    $contribution = $this->callAPISuccessGetSingle('Contribution', [
      'id' => $contributionID,
      'return' => ['net_amount', 'fee_amount', 'total_amount'],
    ]);
    $this->assertEquals(6, $contribution['fee_amount']);
    $this->assertEquals(90, $contribution['total_amount']);
    $this->assertEquals(84, $contribution['net_amount']);

    $this->callAPISuccess('Contribution', 'create', [
      'id' => $contributionID,
      'fee_amount' => 3,
    ]);
    $contribution = $this->callAPISuccessGetSingle('Contribution', [
      'id' => $contributionID,
      'return' => ['net_amount', 'fee_amount', 'total_amount'],
    ]);
    $this->assertEquals(3, $contribution['fee_amount']);
    $this->assertEquals(90, $contribution['total_amount']);
    $this->assertEquals(87, $contribution['net_amount']);

    $this->callAPISuccess('Contribution', 'create', [
      'id' => $contributionID,
      'total_amount' => 200,
    ]);
    $contribution = $this->callAPISuccessGetSingle('Contribution', [
      'id' => $contributionID,
      'return' => ['net_amount', 'fee_amount', 'total_amount'],
    ]);
    $this->assertEquals(3, $contribution['fee_amount']);
    $this->assertEquals(200, $contribution['total_amount']);
    $this->assertEquals(197, $contribution['net_amount']);

    $this->callAPISuccess('Contribution', 'create', [
      'id' => $contributionID,
      'payment_instrument' => 'Cash',
    ]);
    $contribution = $this->callAPISuccessGetSingle('Contribution', [
      'id' => $contributionID,
      'return' => ['net_amount', 'fee_amount', 'total_amount'],
    ]);
    $this->assertEquals(3, $contribution['fee_amount']);
    $this->assertEquals(200, $contribution['total_amount']);
    $this->assertEquals(197, $contribution['net_amount']);
  }

  /**
   * Attempt (but fail) to delete a contribution without parameters.
   */
  public function testDeleteEmptyParamsContribution() {
    $params = [];
    $this->callAPIFailure('contribution', 'delete', $params);
  }

  public function testDeleteWrongParamContribution() {
    $params = [
      'contribution_source' => 'SSF',
    ];
    $this->callAPIFailure('contribution', 'delete', $params);
  }

  public function testDeleteContribution() {
    $contributionID = $this->contributionCreate([
      'contact_id' => $this->_individualId,
      'financial_type_id' => $this->_financialTypeId,
    ]);
    $params = [
      'id' => $contributionID,
    ];
    $this->callAPIAndDocument('contribution', 'delete', $params, __FUNCTION__, __FILE__);
  }

  /**
   * Test civicrm_contribution_search with empty params.
   *
   * All available contributions expected.
   */
  public function testSearchEmptyParams() {
    $params = [];

    $p = [
      'contact_id' => $this->_individualId,
      'receive_date' => date('Ymd'),
      'total_amount' => 100.00,
      'financial_type_id' => $this->_financialTypeId,
      'non_deductible_amount' => 10.00,
      'fee_amount' => 5.00,
      'net_amount' => 95.00,
      'trxn_id' => 23456,
      'invoice_id' => 78910,
      'source' => 'SSF',
      'contribution_status_id' => 1,
    ];
    $contribution = $this->callAPISuccess('contribution', 'create', $p);

    $result = $this->callAPISuccess('contribution', 'get', $params);
    // We're taking the first element.
    $res = $result['values'][$contribution['id']];

    $this->assertEquals($p['contact_id'], $res['contact_id']);
    $this->assertEquals($p['total_amount'], $res['total_amount']);
    $this->assertEquals($p['financial_type_id'], $res['financial_type_id']);
    $this->assertEquals($p['net_amount'], $res['net_amount']);
    $this->assertEquals($p['non_deductible_amount'], $res['non_deductible_amount']);
    $this->assertEquals($p['fee_amount'], $res['fee_amount']);
    $this->assertEquals($p['trxn_id'], $res['trxn_id']);
    $this->assertEquals($p['invoice_id'], $res['invoice_id']);
    $this->assertEquals($p['source'], $res['contribution_source']);
    // contribution_status_id = 1 => Completed
    $this->assertEquals('Completed', $res['contribution_status']);

    $this->contributionDelete($contribution['id']);
  }

  /**
   * Test civicrm_contribution_search. Success expected.
   */
  public function testSearch() {
    $p1 = [
      'contact_id' => $this->_individualId,
      'receive_date' => date('Ymd'),
      'total_amount' => 100.00,
      'financial_type_id' => $this->_financialTypeId,
      'non_deductible_amount' => 10.00,
      'contribution_status_id' => 1,
    ];
    $contribution1 = $this->callAPISuccess('contribution', 'create', $p1);

    $p2 = [
      'contact_id' => $this->_individualId,
      'receive_date' => date('Ymd'),
      'total_amount' => 200.00,
      'financial_type_id' => $this->_financialTypeId,
      'non_deductible_amount' => 20.00,
      'trxn_id' => 5454565,
      'invoice_id' => 1212124,
      'fee_amount' => 50.00,
      'net_amount' => 60.00,
      'contribution_status_id' => 2,
    ];
    $contribution2 = $this->callAPISuccess('contribution', 'create', $p2);

    $params = [
      'contribution_id' => $contribution2['id'],
    ];
    $result = $this->callAPISuccess('contribution', 'get', $params);
    $res = $result['values'][$contribution2['id']];

    $this->assertEquals($p2['contact_id'], $res['contact_id']);
    $this->assertEquals($p2['total_amount'], $res['total_amount']);
    $this->assertEquals($p2['financial_type_id'], $res['financial_type_id']);
    $this->assertEquals($p2['net_amount'], $res['net_amount']);
    $this->assertEquals($p2['non_deductible_amount'], $res['non_deductible_amount']);
    $this->assertEquals($p2['fee_amount'], $res['fee_amount']);
    $this->assertEquals($p2['trxn_id'], $res['trxn_id']);
    $this->assertEquals($p2['invoice_id'], $res['invoice_id']);
    $this->assertEquals(CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Pending'), $res['contribution_status_id']);

    $this->contributionDelete($contribution1['id']);
    $this->contributionDelete($contribution2['id']);
  }

  /**
   * Test completing a transaction via the API.
   *
   * Note that we are creating a logged in user because email goes out from
   * that person
   */
  public function testCompleteTransaction() {
    $mut = new CiviMailUtils($this, TRUE);
    $this->swapMessageTemplateForTestTemplate();
    $this->createLoggedInUser();
    $params = array_merge($this->_params, ['contribution_status_id' => 2]);
    $contribution = $this->callAPISuccess('contribution', 'create', $params);
    $this->callAPISuccess('contribution', 'completetransaction', [
      'id' => $contribution['id'],
    ]);
    $contribution = $this->callAPISuccess('contribution', 'getsingle', ['id' => $contribution['id']]);
    $this->assertEquals('SSF', $contribution['contribution_source']);
    $this->assertEquals('Completed', $contribution['contribution_status']);
    $this->assertEquals(date('Y-m-d'), date('Y-m-d', strtotime($contribution['receipt_date'])));
    $mut->checkMailLog([
      'email:::anthony_anderson@civicrm.org',
      'is_monetary:::1',
      'amount:::100.00',
      'currency:::USD',
      'receive_date:::' . date('Ymd', strtotime($contribution['receive_date'])),
      "receipt_date:::\n",
      'title:::Contribution',
      'contributionStatus:::Completed',
    ]);
    $mut->stop();
    $this->revertTemplateToReservedTemplate();
  }

  /**
   * Test completing a transaction via the API with a non-USD transaction.
   */
  public function testCompleteTransactionEuro() {
    $mut = new CiviMailUtils($this, TRUE);
    $this->swapMessageTemplateForTestTemplate();
    $this->createLoggedInUser();
    $params = array_merge($this->_params, ['contribution_status_id' => 2, 'currency' => 'EUR']);
    $contribution = $this->callAPISuccess('contribution', 'create', $params);

    $this->callAPISuccess('contribution', 'completetransaction', [
      'id' => $contribution['id'],
    ]);

    $contribution = $this->callAPISuccess('contribution', 'getsingle', ['id' => $contribution['id']]);
    $this->assertEquals('SSF', $contribution['contribution_source']);
    $this->assertEquals('Completed', $contribution['contribution_status']);
    $this->assertEquals(date('Y-m-d'), date('Y-m-d', strtotime($contribution['receipt_date'])));

    $entityFinancialTransactions = $this->getFinancialTransactionsForContribution($contribution['id']);
    $entityFinancialTransaction = reset($entityFinancialTransactions);
    $financialTrxn = $this->callAPISuccessGetSingle('FinancialTrxn', ['id' => $entityFinancialTransaction['financial_trxn_id']]);
    $this->assertEquals('EUR', $financialTrxn['currency']);

    $mut->checkMailLog([
      'email:::anthony_anderson@civicrm.org',
      'is_monetary:::1',
      'amount:::100.00',
      'currency:::EUR',
      'receive_date:::' . date('Ymd', strtotime($contribution['receive_date'])),
      "receipt_date:::\n",
      'title:::Contribution',
      'contributionStatus:::Completed',
    ]);
    $mut->stop();
    $this->revertTemplateToReservedTemplate();
  }

  /**
   * Test to ensure mail is sent for pay later
   *
   * @throws \CRM_Core_Exception
   * @throws \API_Exception
   */
  public function testPayLater(): void {
    $mut = new CiviMailUtils($this, TRUE);
    $this->swapMessageTemplateForTestTemplate();
    $this->createLoggedInUser();
    $contributionPageID = $this->createQuickConfigContributionPage();

    $params = [
      'id' => $contributionPageID,
      'price_' . $this->ids['PriceField']['basic'] => $this->ids['PriceFieldValue']['basic'],
      'contact_id' => $this->_individualId,
      'email-5' => 'anthony_anderson@civicrm.org',
      'payment_processor_id' => 0,
      'currencyID' => 'USD',
      'is_pay_later' => 1,
      'invoiceID' => 'f28e1ddc86f8c4a0ff5bcf46393e4bc8',
      'description' => 'Online Contribution: Help Support CiviCRM!',
    ];
    $this->callAPISuccess('ContributionPage', 'submit', $params);

    $mut->checkMailLog([
      'is_pay_later:::1',
      'email:::anthony_anderson@civicrm.org',
      'pay_later_receipt:::This is a pay later receipt',
      'contributionPageId:::' . $contributionPageID,
      'title:::Test Contribution Page',
      'amount:::100',
    ]);
    $mut->stop();
    $this->revertTemplateToReservedTemplate();
  }

  /**
   * Test to check whether contact billing address is used when no contribution address
   */
  public function testBillingAddress() {
    $mut = new CiviMailUtils($this, TRUE);
    $this->swapMessageTemplateForTestTemplate();
    $this->createLoggedInUser();

    //Scenario 1: When Contact don't have any address
    $params = array_merge($this->_params, ['contribution_status_id' => 2]);
    $contribution = $this->callAPISuccess('contribution', 'create', $params);
    $this->callAPISuccess('contribution', 'completetransaction', [
      'id' => $contribution['id'],
    ]);
    $mut->checkMailLog([
      'address:::',
    ]);

    // Scenario 2: Contribution using address
    $address = $this->callAPISuccess('address', 'create', [
      'street_address' => 'contribution billing st',
      'location_type_id' => 2,
      'contact_id' => $this->_params['contact_id'],
    ]);
    $params = array_merge($this->_params, [
      'contribution_status_id' => 2,
      'address_id' => $address['id'],
    ]
    );
    $contribution = $this->callAPISuccess('contribution', 'create', $params);
    $this->callAPISuccess('contribution', 'completetransaction', [
      'id' => $contribution['id'],
    ]);
    $mut->checkMailLog([
      'address:::contribution billing st',
    ]);

    // Scenario 3: Contribution wtth no address but contact has a billing address
    $this->callAPISuccess('address', 'create', [
      'id' => $address['id'],
      'street_address' => 'is billing st',
      'contact_id' => $this->_params['contact_id'],
    ]);
    $params = array_merge($this->_params, ['contribution_status_id' => 2]);
    $contribution = $this->callAPISuccess('contribution', 'create', $params);
    $this->callAPISuccess('contribution', 'completetransaction', [
      'id' => $contribution['id'],
    ]);
    $mut->checkMailLog([
      'address:::is billing st',
    ]);

    $mut->stop();
    $this->revertTemplateToReservedTemplate();
  }

  /**
   * Test completing a transaction via the API.
   *
   * Note that we are creating a logged in user because email goes out from
   * that person
   */
  public function testCompleteTransactionFeeAmount() {
    $this->createLoggedInUser();
    $params = array_merge($this->_params, ['contribution_status_id' => 2]);
    $contribution = $this->callAPISuccess('contribution', 'create', $params);
    $this->callAPISuccess('contribution', 'completetransaction', [
      'id' => $contribution['id'],
      'fee_amount' => '.56',
      'trxn_id' => '7778888',
    ]);
    $contribution = $this->callAPISuccess('contribution', 'getsingle', ['id' => $contribution['id'], 'sequential' => 1]);
    $this->assertEquals('Completed', $contribution['contribution_status']);
    $this->assertEquals('7778888', $contribution['trxn_id']);
    $this->assertEquals('0.56', $contribution['fee_amount']);
    $this->assertEquals('99.44', $contribution['net_amount']);
  }

  /**
   * CRM-19126 Add test to verify when complete transaction is called tax
   * amount is not changed.
   *
   * We start of with a pending contribution.
   *  - total_amount (input) = 100
   *  - total_amount post save (based on tax being added) = 105
   *  - net_amount = 95
   *  - fee_amount = 5
   *  - non_deductible_amount = 10
   *  - tax rate = 5%
   *  - tax_amount = 5
   *  - sum of (calculated) line items = 105
   *
   * Note the fee_amount should really be set when the payment is received
   * and whatever the non_deductible amount is, it is ignored.
   *
   * The fee amount when the payment comes in is 6 rather than 5. The net_amount
   * and fee_amount should change, but not the total_amount or
   * the line items.
   *
   * @param string $thousandSeparator
   *   punctuation used to refer to thousands.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @dataProvider getThousandSeparators
   */
  public function testCheckTaxAmount(string $thousandSeparator): void {
    $this->setCurrencySeparators($thousandSeparator);
    $this->createFinancialTypeWithSalesTax();
    $financialTypeId = $this->ids['FinancialType']['taxable'];

    $contributionID = $this->callAPISuccess('Order', 'create',
      array_merge($this->_params, ['financial_type_id' => $financialTypeId])
    )['id'];
    $contributionPrePayment = $this->callAPISuccessGetSingle('Contribution', ['id' => $contributionID, 'return' => ['tax_amount', 'total_amount']]);
    $this->validateAllContributions();
    $this->callAPISuccess('Contribution', 'completetransaction', [
      'id' => $contributionID,
      'trxn_id' => '777788888',
      'fee_amount' => '6.00',
      'sequential' => 1,
    ]);
    $contributionPostPayment = $this->callAPISuccessGetSingle('Contribution', ['id' => $contributionID, 'return' => ['tax_amount', 'fee_amount', 'net_amount']]);
    $this->assertEquals(4.76, $contributionPrePayment['tax_amount']);
    $this->assertEquals(4.76, $contributionPostPayment['tax_amount']);
    $this->assertEquals('6.00', $contributionPostPayment['fee_amount']);
    $this->assertEquals('94.00', $contributionPostPayment['net_amount']);
    $this->validateAllContributions();
    $this->validateAllPayments();
  }

  /**
   * Test repeat contribution successfully creates line item.
   *
   * @throws \CRM_Core_Exception
   */
  public function testRepeatTransaction(): void {
    $originalContribution = $this->setUpRepeatTransaction([], 'single');
    $this->callAPISuccess('contribution', 'repeattransaction', [
      'original_contribution_id' => $originalContribution['id'],
      'contribution_status_id' => 'Completed',
      'trxn_id' => 4567,
    ]);
    $lineItemParams = [
      'entity_id' => $originalContribution['id'],
      'sequential' => 1,
      'return' => [
        'entity_table',
        'qty',
        'unit_price',
        'line_total',
        'label',
        'financial_type_id',
        'deductible_amount',
        'price_field_value_id',
        'price_field_id',
      ],
    ];
    $lineItem1 = $this->callAPISuccess('line_item', 'get', array_merge($lineItemParams, [
      'entity_id' => $originalContribution['id'],
    ]));
    $lineItem2 = $this->callAPISuccess('line_item', 'get', array_merge($lineItemParams, [
      'entity_id' => $originalContribution['id'] + 1,
    ]));
    unset($lineItem1['values'][0]['id'], $lineItem1['values'][0]['entity_id']);
    unset($lineItem2['values'][0]['id'], $lineItem2['values'][0]['entity_id']);
    $this->assertEquals($lineItem1['values'][0], $lineItem2['values'][0]);
    $this->_checkFinancialRecords([
      'id' => $originalContribution['id'] + 1,
      'payment_instrument_id' => $this->callAPISuccessGetValue('PaymentProcessor', [
        'id' => $originalContribution['payment_processor_id'],
        'return' => 'payment_instrument_id',
      ]),
    ], 'online');
  }

  /**
   * Test custom data is copied over from the template transaction.
   *
   * (Over time various discussions have deemed this to be the most recent one, allowing
   * users to alter custom data going forwards. This is implemented for line items already.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   */
  public function testRepeatTransactionWithCustomData(): void {
    $this->createCustomGroupWithFieldOfType(['extends' => 'Contribution', 'name' => 'Repeat'], 'text');
    $originalContribution = $this->setUpRepeatTransaction([], 'single', [$this->getCustomFieldName('text') => 'first']);
    $this->callAPISuccess('contribution', 'repeattransaction', [
      'contribution_recur_id' => $originalContribution['contribution_recur_id'],
      'contribution_status_id' => 'Completed',
      'trxn_id' => 'my_trxn',
    ]);

    $contribution = Contribution::get()
      ->addWhere('trxn_id', '=', 'my_trxn')
      ->addSelect('Custom_Group.Enter_text_here')
      ->addSelect('id')
      ->execute()->first();
    $this->assertEquals('first', $contribution['Custom_Group.Enter_text_here']);

    Contribution::update()->setValues(['Custom_Group.Enter_text_here' => 'second'])->addWhere('id', '=', $contribution['id'])->execute();

    $this->callAPISuccess('contribution', 'repeattransaction', [
      'original_contribution_id' => $originalContribution['id'],
      'contribution_status_id' => 'Completed',
      'trxn_id' => 'number_3',
    ]);

    $contribution = Contribution::get()
      ->addWhere('trxn_id', '=', 'number_3')
      ->setSelect(['id', 'Custom_Group.Enter_text_here'])
      ->execute()->first();
    $this->assertEquals('second', $contribution['Custom_Group.Enter_text_here']);
  }

  /**
   * Test repeat contribution successfully creates line items (plural).
   *
   * @throws \CRM_Core_Exception
   */
  public function testRepeatTransactionLineItems(): void {
    // CRM-19309
    $originalContribution = $this->setUpRepeatTransaction([], 'multiple');
    $this->callAPISuccess('contribution', 'repeattransaction', [
      'original_contribution_id' => $originalContribution['id'],
      'contribution_status_id' => 'Completed',
      'trxn_id' => 1234,
    ]);

    $lineItemParams = [
      'entity_id' => $originalContribution['id'],
      'sequential' => 1,
      'return' => [
        'entity_table',
        'qty',
        'unit_price',
        'line_total',
        'label',
        'financial_type_id',
        'deductible_amount',
        'price_field_value_id',
        'price_field_id',
      ],
    ];
    $lineItem1 = $this->callAPISuccess('line_item', 'get', array_merge($lineItemParams, [
      'entity_id' => $originalContribution['id'],
      'options' => ['sort' => 'qty'],
    ]))['values'];
    $lineItem2 = $this->callAPISuccess('line_item', 'get', array_merge($lineItemParams, [
      'entity_id' => $originalContribution['id'] + 1,
      'options' => ['sort' => 'qty'],
    ]))['values'];

    // unset id and entity_id for all of them to be able to compare the lineItems:
    unset($lineItem1[0]['id'], $lineItem1[0]['entity_id']);
    unset($lineItem2[0]['id'], $lineItem2[0]['entity_id']);
    $this->assertEquals($lineItem1[0], $lineItem2[0]);

    unset($lineItem1[1]['id'], $lineItem1[1]['entity_id']);
    unset($lineItem2[1]['id'], $lineItem2[1]['entity_id']);
    $this->assertEquals($lineItem1[1], $lineItem2[1]);

    // CRM-19309 so in future we also want to:
    // check that financial_line_items have been created for entity_id 3 and 4;

    $this->callAPISuccessGetCount('FinancialItem', ['description' => 'Sales Tax', 'amount' => 0], 0);
  }

  /**
   * Test repeat contribution successfully creates is_test transaction.
   *
   * @throws \CRM_Core_Exception
   */
  public function testRepeatTransactionIsTest(): void {
    $this->_params['is_test'] = 1;
    $originalContribution = $this->setUpRepeatTransaction(['is_test' => 1], 'single');

    $this->callAPISuccess('contribution', 'repeattransaction', [
      'original_contribution_id' => $originalContribution['id'],
      'contribution_status_id' => 'Completed',
      'trxn_id' => '1234',
    ]);
    $this->callAPISuccessGetCount('Contribution', ['contribution_test' => 1], 2);
  }

  /**
   * Test repeat contribution passed in status.
   *
   * @throws \CRM_Core_Exception
   */
  public function testRepeatTransactionPassedInStatus(): void {
    $originalContribution = $this->setUpRepeatTransaction([], 'single');

    $this->callAPISuccess('contribution', 'repeattransaction', [
      'original_contribution_id' => $originalContribution['id'],
      'contribution_status_id' => 'Pending',
      'trxn_id' => 1234,
    ]);
    $this->callAPISuccessGetCount('Contribution', ['contribution_status_id' => 2], 1);
  }

  /**
   * Test repeat contribution accepts recur_id instead of
   * original_contribution_id.
   *
   * @throws \CRM_Core_Exception
   */
  public function testRepeatTransactionAcceptRecurID(): void {
    $contributionRecur = $this->callAPISuccess('contribution_recur', 'create', [
      'contact_id' => $this->_individualId,
      'installments' => '12',
      'frequency_interval' => '1',
      'amount' => '100',
      'contribution_status_id' => 1,
      'start_date' => '2012-01-01 00:00:00',
      'currency' => 'USD',
      'frequency_unit' => 'month',
      'payment_processor_id' => $this->paymentProcessorID,
    ]);
    $this->callAPISuccess('contribution', 'create', array_merge(
        $this->_params,
        ['contribution_recur_id' => $contributionRecur['id']])
    );

    $this->callAPISuccess('contribution', 'repeattransaction', [
      'contribution_recur_id' => $contributionRecur['id'],
      'contribution_status_id' => 'Completed',
      'trxn_id' => 1234,
    ]);

  }

  /**
   * CRM-19873 Test repeattransaction if contribution_recur_id is a test.
   *
   * @throws \CRM_Core_Exception
   */
  public function testRepeatTransactionTestRecurId() {
    $contributionRecur = $this->callAPISuccess('ContributionRecur', 'create', [
      'contact_id' => $this->_individualId,
      'frequency_interval' => '1',
      'amount' => '1.00',
      'contribution_status_id' => 1,
      'start_date' => '2017-01-01 00:00:00',
      'currency' => 'USD',
      'frequency_unit' => 'month',
      'payment_processor_id' => $this->paymentProcessorID,
      'is_test' => 1,
    ]);
    $this->callAPISuccess('contribution', 'create', array_merge(
        $this->_params,
        [
          'contribution_recur_id' => $contributionRecur['id'],
          'is_test' => 1,
        ])
    );

    $repeatedContribution = $this->callAPISuccess('contribution', 'repeattransaction', [
      'contribution_recur_id' => $contributionRecur['id'],
      'contribution_status_id' => 'Completed',
      'trxn_id' => 'magic_number',
    ]);

    $this->assertEquals($contributionRecur['values'][1]['is_test'], $repeatedContribution['values'][2]['is_test']);
  }

  /**
   * CRM-19945 Tests that Contribute.repeattransaction renews a membership when contribution status=Completed
   *
   * @throws \CRM_Core_Exception
   */
  public function testRepeatTransactionMembershipRenewCompletedContribution(): void {
    [$originalContribution, $membership] = $this->setUpAutoRenewMembership();

    $this->callAPISuccess('Contribution', 'repeattransaction', [
      'contribution_recur_id' => $originalContribution['values'][1]['contribution_recur_id'],
      'contribution_status_id' => 'Failed',
    ]);

    $this->callAPISuccess('membership', 'create', [
      'id' => $membership['id'],
      'end_date' => 'yesterday',
      'status_id' => 'Expired',
    ]);

    $contribution = $this->callAPISuccess('Contribution', 'repeattransaction', [
      'contribution_recur_id' => $originalContribution['values'][1]['contribution_recur_id'],
      'contribution_status_id' => 'Completed',
      'trxn_id' => 'bobsled',
    ]);

    $membershipStatusId = $this->callAPISuccess('membership', 'getvalue', [
      'id' => $membership['id'],
      'return' => 'status_id',
    ]);

    $membership = $this->callAPISuccess('membership', 'get', [
      'id' => $membership['id'],
    ]);

    $this->assertEquals('New', CRM_Core_PseudoConstant::getName('CRM_Member_BAO_Membership', 'status_id', $membershipStatusId));

    $lineItem = $this->callAPISuccessGetSingle('LineItem', ['contribution_id' => $contribution['id']]);
    $this->assertEquals('civicrm_membership', $lineItem['entity_table']);
    $this->callAPISuccessGetCount('MembershipPayment', ['membership_id' => $membership['id']]);
  }

  /**
   * This is one of those tests that locks in existing behaviour.
   *
   * I feel like correct behaviour is arguable & has been discussed in the past. However, if the membership has
   * a date which says it should be expired then the result of repeattransaction is to push that date
   * to be one membership term from 'now' with status 'new'.
   */
  public function testRepeattransactionRenewMembershipOldMembership() {
    $entities = $this->setUpAutoRenewMembership();
    $newStatusID = CRM_Core_PseudoConstant::getKey('CRM_Member_BAO_Membership', 'status_id', 'New');
    $membership = $this->callAPISuccess('Membership', 'create', [
      'id' => $entities[1]['id'],
      'join_date' => '4 months ago',
      'start_date' => '3 months ago',
      'end_date' => '2 months ago',
    ]);
    $membership = $membership['values'][$membership['id']];

    // This status does not appear to be calculated at all and is set to 'new'. Feels like a bug.
    $this->assertEquals($newStatusID, $membership['status_id']);

    // So it seems renewing this expired membership results in it's new status being current and it being pushed to a future date
    $this->callAPISuccess('Contribution', 'repeattransaction', ['original_contribution_id' => $entities[0]['id'], 'contribution_status_id' => 'Completed']);
    $membership = $this->callAPISuccessGetSingle('Membership', ['id' => $membership['id']]);
    // If this date calculation winds up being flakey the spirit of the test would be maintained by just checking
    // date is greater than today.
    $this->assertEquals(date('Y-m-d', strtotime('+ 1 month -1 day')), $membership['end_date']);
    $this->assertEquals($newStatusID, $membership['membership_type_id']);
  }

  /**
   * CRM-19945 Tests that Contribute.repeattransaction DOES NOT renew a membership when contribution status=Failed
   *
   * @dataProvider contributionStatusProvider
   *
   * @throws \CRM_Core_Exception
   */
  public function testRepeatTransactionMembershipRenewContributionNotCompleted($contributionStatus): void {
    // Completed status should renew so we don't test that here
    // In Progress status was never actually intended to be available for contributions.
    // Partially paid is not valid.
    if (in_array($contributionStatus['name'], ['Completed', 'In Progress', 'Partially paid'])) {
      return;
    }
    [$originalContribution, $membership] = $this->setUpAutoRenewMembership();

    $this->callAPISuccess('Contribution', 'repeattransaction', [
      'original_contribution_id' => $originalContribution['id'],
      'contribution_status_id' => 'Completed',
    ]);

    $this->callAPISuccess('membership', 'create', [
      'id' => $membership['id'],
      'end_date' => 'yesterday',
      'status_id' => 'Expired',
    ]);

    $contribution = $this->callAPISuccess('contribution', 'repeattransaction', [
      'contribution_recur_id' => $originalContribution['values'][1]['contribution_recur_id'],
      'contribution_status_id' => $contributionStatus['name'],
      'trxn_id' => 'bobsled',
    ]);

    $updatedMembership = $this->callAPISuccess('membership', 'getsingle', ['id' => $membership['id']]);

    $dateTime = new DateTime('yesterday');
    $this->assertEquals($dateTime->format('Y-m-d'), $updatedMembership['end_date']);
    $this->assertEquals(CRM_Core_PseudoConstant::getKey('CRM_Member_BAO_Membership', 'status_id', 'Expired'), $updatedMembership['status_id']);

    $lineItem = $this->callAPISuccessGetSingle('LineItem', ['contribution_id' => $contribution['id']]);
    $this->assertEquals('civicrm_membership', $lineItem['entity_table']);
    $this->callAPISuccessGetCount('MembershipPayment', ['membership_id' => $membership['id']]);
  }

  /**
   * Dataprovider provides contribution status as [optionvalue=>contribution_status_name]
   * FIXME: buildOptions seems to die in CRM_Core_Config::_construct when in test mode.
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  public function contributionStatusProvider() {
    $contributionStatuses = civicrm_api3('OptionValue', 'get', [
      'return' => ["id", "name"],
      'option_group_id' => "contribution_status",
    ]);
    foreach ($contributionStatuses['values'] as $statusName) {
      $statuses[] = [$statusName];
    }
    return $statuses;
  }

  /**
   * CRM-16397 test appropriate action if total amount has changed for single
   * line items.
   *
   * @throws \CRM_Core_Exception
   */
  public function testRepeatTransactionAlteredAmount(): void {
    $paymentProcessorID = $this->paymentProcessorCreate();
    $contributionRecur = $this->callAPISuccess('contribution_recur', 'create', [
      'contact_id' => $this->_individualId,
      'installments' => '12',
      'frequency_interval' => '1',
      'amount' => '500',
      'contribution_status_id' => 1,
      'start_date' => '2012-01-01 00:00:00',
      'currency' => 'USD',
      'frequency_unit' => 'month',
      'payment_processor_id' => $paymentProcessorID,
    ]);
    $originalContribution = $this->callAPISuccess('contribution', 'create', array_merge(
        $this->_params,
        [
          'contribution_recur_id' => $contributionRecur['id'],
        ])
    );

    $this->callAPISuccess('contribution', 'repeattransaction', [
      'original_contribution_id' => $originalContribution['id'],
      'contribution_status_id' => 'Completed',
      'trxn_id' => 1234,
      'total_amount' => '400',
      'fee_amount' => 50,
    ]);

    $lineItemParams = [
      'entity_id' => $originalContribution['id'],
      'sequential' => 1,
      'return' => [
        'entity_table',
        'qty',
        'unit_price',
        'line_total',
        'label',
        'financial_type_id',
        'deductible_amount',
        'price_field_value_id',
        'price_field_id',
      ],
    ];
    $this->callAPISuccessGetSingle('contribution', [
      'total_amount' => 400,
      'fee_amount' => 50,
      'net_amount' => 350,
    ]);
    $lineItem1 = $this->callAPISuccess('line_item', 'get', array_merge($lineItemParams, [
      'entity_id' => $originalContribution['id'],
    ]));
    $expectedLineItem = array_merge(
      $lineItem1['values'][0], [
        'line_total' => '400.00',
        'unit_price' => '400.00',
      ]
    );

    $lineItem2 = $this->callAPISuccess('line_item', 'get', array_merge($lineItemParams, [
      'entity_id' => $originalContribution['id'] + 1,
    ]));

    unset($expectedLineItem['id'], $expectedLineItem['entity_id'], $lineItem2['values'][0]['id'], $lineItem2['values'][0]['entity_id']);
    $this->assertEquals($expectedLineItem, $lineItem2['values'][0]);
  }

  /**
   * Test financial_type_id override behaviour with a single line item.
   *
   * CRM-17718 a passed in financial_type_id is allowed to override the
   * original contribution where there is only one line item.
   *
   * @throws \CRM_Core_Exception
   */
  public function testRepeatTransactionPassedInFinancialType() {
    $originalContribution = $this->setUpRecurringContribution();

    $this->callAPISuccess('Contribution', 'repeattransaction', [
      'original_contribution_id' => $originalContribution['id'],
      'contribution_status_id' => 'Completed',
      'trxn_id' => 12345,
      'financial_type_id' => 2,
    ]);
    $lineItemParams = [
      'entity_id' => $originalContribution['id'],
      'sequential' => 1,
      'return' => [
        'entity_table',
        'qty',
        'unit_price',
        'line_total',
        'label',
        'financial_type_id',
        'deductible_amount',
        'price_field_value_id',
        'price_field_id',
      ],
    ];

    $this->callAPISuccessGetSingle('Contribution', [
      'total_amount' => 100,
      'financial_type_id' => 2,
    ]);
    $lineItem1 = $this->callAPISuccess('line_item', 'get', array_merge($lineItemParams, [
      'entity_id' => $originalContribution['id'],
    ]));
    $expectedLineItem = array_merge(
      $lineItem1['values'][0], [
        'line_total' => '100.00',
        'unit_price' => '100.00',
        'financial_type_id' => 2,
        'contribution_type_id' => 2,
      ]
    );
    $lineItem2 = $this->callAPISuccess('line_item', 'get', array_merge($lineItemParams, [
      'entity_id' => $originalContribution['id'] + 1,
    ]));
    unset($expectedLineItem['id'], $expectedLineItem['entity_id']);
    unset($lineItem2['values'][0]['id'], $lineItem2['values'][0]['entity_id']);
    $this->assertEquals($expectedLineItem, $lineItem2['values'][0]);
  }

  /**
   * Test Contribution with Order api.
   *
   * @throws \CRM_Core_Exception|\CiviCRM_API3_Exception
   */
  public function testContributionOrder() {
    $this->createContributionAndMembershipOrder();
    $contribution = $this->callAPISuccess('contribution', 'get')['values'][$this->ids['Contribution'][0]];
    $this->assertEquals('Pending Label**', $contribution['contribution_status']);
    $membership = $this->callAPISuccessGetSingle('Membership', ['contact_id' => $this->ids['Contact']['order']]);

    $this->callAPISuccess('Payment', 'create', [
      'contribution_id' => $this->ids['Contribution'][0],
      'payment_instrument_id' => 'Check',
      'total_amount' => 300,
    ]);
    $contribution = $this->callAPISuccess('contribution', 'get')['values'][$this->ids['Contribution'][0]];
    $this->assertEquals('Completed', $contribution['contribution_status']);

    $lineItem = $this->callAPISuccess('LineItem', 'get', [
      'sequential' => 1,
      'contribution_id' => $this->ids['Contribution'][0],
    ])['values'];
    $this->assertCount(2, $lineItem);
    $this->assertEquals($this->ids['Contribution'][0], $lineItem[0]['entity_id']);
    $this->assertEquals('civicrm_contribution', $lineItem[0]['entity_table']);
    $this->assertEquals($this->ids['Contribution'][0], $lineItem[0]['contribution_id']);
    $this->assertEquals($this->ids['Contribution'][0], $lineItem[1]['contribution_id']);
    $this->assertEquals('100.00', $lineItem[0]['line_total']);
    $this->assertEquals('200.00', $lineItem[1]['line_total']);
    $this->assertEquals($membership['id'], $lineItem[1]['entity_id']);
    $this->assertEquals('civicrm_membership', $lineItem[1]['entity_table']);
  }

  /**
   * Test financial_type_id override behaviour with a single line item.
   *
   * CRM-17718 a passed in financial_type_id is not allowed to override the
   * original contribution where there is more than one line item.
   *
   * @throws \CRM_Core_Exception
   */
  public function testRepeatTransactionPassedInFinancialTypeTwoLineItems(): void {
    $this->_params = $this->getParticipantOrderParams();
    $originalContribution = $this->setUpRecurringContribution();

    $this->callAPISuccess('Contribution', 'repeattransaction', [
      'original_contribution_id' => $originalContribution['id'],
      'contribution_status_id' => 'Completed',
      'trxn_id' => 'repeat',
      'financial_type_id' => 2,
    ]);

    // Retrieve the new contribution and note the financial type passed in has been ignored.
    $contribution = $this->callAPISuccessGetSingle('Contribution', [
      'trxn_id' => 'repeat',
    ]);
    $this->assertEquals(4, $contribution['financial_type_id']);

    $lineItems = $this->callAPISuccess('line_item', 'get', [
      'entity_id' => $contribution['id'],
    ])['values'];
    foreach ($lineItems as $lineItem) {
      $this->assertNotEquals(2, $lineItem['financial_type_id']);
    }
  }

  /**
   * CRM-17718 test appropriate action if financial type has changed for single
   * line items.
   *
   * @throws \CRM_Core_Exception
   */
  public function testRepeatTransactionUpdatedFinancialType(): void {
    $originalContribution = $this->setUpRecurringContribution([], ['financial_type_id' => 2]);

    $this->callAPISuccess('contribution', 'repeattransaction', [
      'contribution_recur_id' => $originalContribution['id'],
      'contribution_status_id' => 'Completed',
      'trxn_id' => 234,
    ]);
    $lineItemParams = [
      'entity_id' => $originalContribution['id'],
      'sequential' => 1,
      'return' => [
        'entity_table',
        'qty',
        'unit_price',
        'line_total',
        'label',
        'financial_type_id',
        'deductible_amount',
        'price_field_value_id',
        'price_field_id',
      ],
    ];

    $this->callAPISuccessGetSingle('contribution', [
      'total_amount' => 100,
      'financial_type_id' => 2,
    ]);
    $lineItem1 = $this->callAPISuccess('line_item', 'get', array_merge($lineItemParams, [
      'entity_id' => $originalContribution['id'],
    ]))['values'][0];
    $expectedLineItem = array_merge(
      $lineItem1, [
        'line_total' => '100.00',
        'unit_price' => '100.00',
        'financial_type_id' => 2,
        'contribution_type_id' => 2,
      ]
    );

    $lineItem2 = $this->callAPISuccess('line_item', 'get', array_merge($lineItemParams, [
      'entity_id' => $originalContribution['id'] + 1,
    ]));
    unset($expectedLineItem['id'], $expectedLineItem['entity_id']);
    unset($lineItem2['values'][0]['id'], $lineItem2['values'][0]['entity_id']);
    $this->assertEquals($expectedLineItem, $lineItem2['values'][0]);
  }

  /**
   * CRM-16397 test appropriate action if campaign has been passed in.
   */
  public function testRepeatTransactionPassedInCampaign() {
    $paymentProcessorID = $this->paymentProcessorCreate();
    $campaignID = $this->campaignCreate();
    $campaignID2 = $this->campaignCreate();
    $contributionRecur = $this->callAPISuccess('contribution_recur', 'create', [
      'contact_id' => $this->_individualId,
      'installments' => '12',
      'frequency_interval' => '1',
      'amount' => '100',
      'contribution_status_id' => 1,
      'start_date' => '2012-01-01 00:00:00',
      'currency' => 'USD',
      'frequency_unit' => 'month',
      'payment_processor_id' => $paymentProcessorID,
    ]);
    $originalContribution = $this->callAPISuccess('contribution', 'create', array_merge(
      $this->_params,
      [
        'contribution_recur_id' => $contributionRecur['id'],
        'campaign_id' => $campaignID,
      ])
    );

    $this->callAPISuccess('contribution', 'repeattransaction', [
      'original_contribution_id' => $originalContribution['id'],
      'contribution_status_id' => 'Completed',
      'trxn_id' => 2345,
      'campaign_id' => $campaignID2,
    ]);

    $this->callAPISuccessGetSingle('contribution', [
      'total_amount' => 100,
      'campaign_id' => $campaignID2,
    ]);
  }

  /**
   * CRM-17718 campaign stored on contribution recur gets priority.
   *
   * This reflects the fact we permit people to update them.
   *
   * @throws \CRM_Core_Exception
   */
  public function testRepeatTransactionUpdatedCampaign(): void {
    $paymentProcessorID = $this->paymentProcessorCreate();
    $campaignID = $this->campaignCreate();
    $campaignID2 = $this->campaignCreate();
    $contributionRecur = $this->callAPISuccess('contribution_recur', 'create', [
      'contact_id' => $this->_individualId,
      'installments' => '12',
      'frequency_interval' => '1',
      'amount' => '100',
      'contribution_status_id' => 1,
      'start_date' => '2012-01-01 00:00:00',
      'currency' => 'USD',
      'frequency_unit' => 'month',
      'payment_processor_id' => $paymentProcessorID,
      'campaign_id' => $campaignID,
    ]);
    $originalContribution = $this->callAPISuccess('contribution', 'create', array_merge(
      $this->_params,
      [
        'contribution_recur_id' => $contributionRecur['id'],
        'campaign_id' => $campaignID2,
      ])
    );

    $this->callAPISuccess('contribution', 'repeattransaction', [
      'original_contribution_id' => $originalContribution['id'],
      'contribution_status_id' => 'Completed',
      'trxn_id' => 789,
    ]);

    $this->callAPISuccessGetSingle('Contribution', [
      'total_amount' => 100,
      'campaign_id' => $campaignID,
    ]);
  }

  /**
   * CRM-20685 Repeattransaction produces incorrect Financial Type ID (in
   * specific circumstance) - if number of lineItems = 1.
   *
   * This case happens when the line item & contribution do not have the same
   * type in his initiating transaction.
   *
   * @throws \CRM_Core_Exception
   */
  public function testRepeatTransactionUpdatedFinancialTypeAndNotEquals(): void {
    $originalContribution = $this->setUpRecurringContribution([], ['financial_type_id' => 2]);
    // This will made the trick to get the not equals behaviour.
    $this->callAPISuccess('line_item', 'create', ['id' => 1, 'financial_type_id' => 4]);
    $this->callAPISuccess('contribution', 'repeattransaction', [
      'contribution_recur_id' => $originalContribution['id'],
      'contribution_status_id' => 'Completed',
      'trxn_id' => 1234,
    ]);
    $lineItemParams = [
      'entity_id' => $originalContribution['id'],
      'sequential' => 1,
      'return' => [
        'entity_table',
        'qty',
        'unit_price',
        'line_total',
        'label',
        'financial_type_id',
        'deductible_amount',
        'price_field_value_id',
        'price_field_id',
      ],
    ];
    $this->callAPISuccessGetSingle('contribution', [
      'total_amount' => 100,
      'financial_type_id' => 2,
    ]);
    $lineItem1 = $this->callAPISuccess('line_item', 'get', array_merge($lineItemParams, [
      'entity_id' => $originalContribution['id'],
    ]));
    $expectedLineItem = array_merge(
      $lineItem1['values'][0], [
        'line_total' => '100.00',
        'unit_price' => '100.00',
        'financial_type_id' => 4,
        'contribution_type_id' => 4,
      ]
    );

    $lineItem2 = $this->callAPISuccess('line_item', 'get', array_merge($lineItemParams, [
      'entity_id' => $originalContribution['id'] + 1,
    ]));
    $this->callAPISuccess('line_item', 'create', ['id' => 1, 'financial_type_id' => 1]);
    unset($expectedLineItem['id'], $expectedLineItem['entity_id']);
    unset($lineItem2['values'][0]['id'], $lineItem2['values'][0]['entity_id']);
    $this->assertEquals($expectedLineItem, $lineItem2['values'][0]);
  }

  /**
   * Test completing a transaction does not 'mess' with net amount (CRM-15960).
   */
  public function testCompleteTransactionNetAmountOK() {
    $this->createLoggedInUser();
    $params = array_merge($this->_params, ['contribution_status_id' => 2]);
    unset($params['net_amount']);
    $contribution = $this->callAPISuccess('contribution', 'create', $params);
    $this->callAPISuccess('contribution', 'completetransaction', [
      'id' => $contribution['id'],
    ]);
    $contribution = $this->callAPISuccess('contribution', 'getsingle', ['id' => $contribution['id']]);
    $this->assertEquals('Completed', $contribution['contribution_status']);
    $this->assertTrue(($contribution['total_amount'] - $contribution['net_amount']) == $contribution['fee_amount']);
  }

  /**
   * CRM-14151 - Test completing a transaction via the API.
   */
  public function testCompleteTransactionWithReceiptDateSet() {
    $this->swapMessageTemplateForTestTemplate();
    $mut = new CiviMailUtils($this, TRUE);
    $this->createLoggedInUser();
    $params = array_merge($this->_params, ['contribution_status_id' => 2, 'receipt_date' => 'now']);
    $contribution = $this->callAPISuccess('contribution', 'create', $params);
    $this->callAPISuccess('contribution', 'completetransaction', ['id' => $contribution['id'], 'trxn_date' => date('Y-m-d')]);
    $contribution = $this->callAPISuccess('contribution', 'get', ['id' => $contribution['id'], 'sequential' => 1]);
    $this->assertEquals('Completed', $contribution['values'][0]['contribution_status']);
    // Make sure receive_date is original date and make sure payment date is today
    $this->assertEquals('2012-05-11', date('Y-m-d', strtotime($contribution['values'][0]['receive_date'])));
    $payment = $this->callAPISuccess('payment', 'get', ['contribution_id' => $contribution['id'], 'sequential' => 1]);
    $this->assertEquals(date('Y-m-d'), date('Y-m-d', strtotime($payment['values'][0]['trxn_date'])));
    $mut->checkMailLog([
      'Receipt - Contribution',
      'receipt_date:::' . date('Ymd'),
    ]);
    $mut->stop();
    $this->revertTemplateToReservedTemplate();
  }

  /**
   * CRM-1960 - Test to ensure that completetransaction respects the is_email_receipt setting
   */
  public function testCompleteTransactionWithEmailReceiptInput() {
    $contributionPage = $this->createReceiptableContributionPage();

    $this->_params['contribution_page_id'] = $contributionPage['id'];
    $params = array_merge($this->_params, ['contribution_status_id' => 2]);
    $contribution = $this->callAPISuccess('contribution', 'create', $params);
    // Complete the transaction overriding is_email_receipt to = FALSE
    $this->callAPISuccess('contribution', 'completetransaction', [
      'id' => $contribution['id'],
      'trxn_date' => date('2011-04-09'),
      'trxn_id' => 'kazam',
      'is_email_receipt' => 0,
    ]);
    // Check if a receipt was issued
    $receipt_date = $this->callAPISuccess('Contribution', 'getvalue', ['id' => $contribution['id'], 'return' => 'receipt_date']);
    $this->assertEquals('', $receipt_date);
  }

  /**
   * Test that $is_recur is assigned to the receipt.
   */
  public function testCompleteTransactionForRecurring() {
    $this->mut = new CiviMailUtils($this, TRUE);
    $this->swapMessageTemplateForTestTemplate();
    $recurring = $this->setUpRecurringContribution();
    $contributionPage = $this->createReceiptableContributionPage(['is_recur' => TRUE, 'recur_frequency_unit' => 'month', 'recur_interval' => 1]);

    $this->_params['contribution_page_id'] = $contributionPage['id'];
    $this->_params['contribution_recur_id'] = $recurring['id'];

    $contribution = $this->setUpForCompleteTransaction();

    $this->callAPISuccess('contribution', 'completetransaction', [
      'id' => $contribution['id'],
      'trxn_date' => date('2011-04-09'),
      'trxn_id' => 'kazam',
      'is_email_receipt' => 1,
    ]);

    $this->mut->checkMailLog([
      'is_recur:::1',
      'cancelSubscriptionUrl:::' . CIVICRM_UF_BASEURL,
    ]);
    $this->mut->stop();
    $this->revertTemplateToReservedTemplate();
  }

  /**
   * CRM-19710 - Test to ensure that completetransaction respects the input for
   * is_email_receipt setting.
   *
   * If passed in it will override the default from contribution page.
   *
   * @throws \CRM_Core_Exception
   */
  public function testCompleteTransactionWithEmailReceiptInputTrue(): void {
    $mut = new CiviMailUtils($this, TRUE);
    $this->createLoggedInUser();
    $contributionPageParams = ['is_email_receipt' => 0];
    // Create a Contribution Page with is_email_receipt = FALSE
    $contributionPageID = $this->createQuickConfigContributionPage($contributionPageParams);
    $this->_params['contribution_page_id'] = $contributionPageID;
    $params = array_merge($this->_params, ['contribution_status_id' => 2, 'receipt_date' => 'now']);
    $contribution = $this->callAPISuccess('contribution', 'create', $params);
    // Complete the transaction overriding is_email_receipt to = TRUE
    $this->callAPISuccess('contribution', 'completetransaction', [
      'id' => $contribution['id'],
      'is_email_receipt' => 1,
    ]);
    $mut->checkMailLog([
      'Contribution Information',
    ]);
    $mut->stop();
  }

  /**
   * Complete the transaction using the template with all the possible.
   */
  public function testCompleteTransactionWithTestTemplate() {
    $this->swapMessageTemplateForTestTemplate();
    $contribution = $this->setUpForCompleteTransaction();
    $this->callAPISuccess('contribution', 'completetransaction', [
      'id' => $contribution['id'],
      'trxn_date' => date('2011-04-09'),
      'trxn_id' => 'kazam',
    ]);
    $receive_date = $this->callAPISuccess('Contribution', 'getvalue', ['id' => $contribution['id'], 'return' => 'receive_date']);
    $this->mut->checkMailLog([
      'email:::anthony_anderson@civicrm.org',
      'is_monetary:::1',
      'amount:::100.00',
      'currency:::USD',
      'receive_date:::' . date('Ymd', strtotime($receive_date)),
      'receipt_date:::' . date('Ymd'),
      'title:::Contribution',
      'trxn_id:::kazam',
      'contactID:::' . $this->_params['contact_id'],
      'contributionID:::' . $contribution['id'],
      'financialTypeId:::1',
      'financialTypeName:::Donation',
    ]);
    $this->mut->stop();
    $this->revertTemplateToReservedTemplate();
  }

  /**
   * Complete the transaction using the template with all the possible.
   */
  public function testCompleteTransactionContributionPageFromAddress() {
    $contributionPage = $this->callAPISuccess('ContributionPage', 'create', [
      'receipt_from_name' => 'Mickey Mouse',
      'receipt_from_email' => 'mickey@mouse.com',
      'title' => "Test Contribution Page",
      'financial_type_id' => 1,
      'currency' => 'NZD',
      'goal_amount' => 50,
      'is_pay_later' => 1,
      'is_monetary' => TRUE,
      'is_email_receipt' => TRUE,
    ]);
    $this->_params['contribution_page_id'] = $contributionPage['id'];
    $contribution = $this->setUpForCompleteTransaction();
    $this->callAPISuccess('contribution', 'completetransaction', ['id' => $contribution['id']]);
    $this->mut->checkMailLog([
      'mickey@mouse.com',
      'Mickey Mouse <',
    ]);
    $this->mut->stop();
  }

  /**
   * Test completing first transaction in a recurring series.
   *
   * The status should be set to 'in progress' and the next scheduled payment date calculated.
   *
   * @dataProvider getScheduledDateData
   *
   * @param array $dataSet
   *
   * @throws \Exception
   */
  public function testCompleteTransactionSetStatusToInProgress($dataSet) {
    $paymentProcessorID = $this->paymentProcessorCreate();
    $contributionRecur = $this->callAPISuccess('contribution_recur', 'create', array_merge([
      'contact_id' => $this->_individualId,
      'installments' => '2',
      'frequency_interval' => '1',
      'amount' => '500',
      'contribution_status_id' => 'Pending',
      'start_date' => '2012-01-01 00:00:00',
      'currency' => 'USD',
      'frequency_unit' => 'month',
      'payment_processor_id' => $paymentProcessorID,
    ], $dataSet['data']));
    $contribution = $this->callAPISuccess('contribution', 'create', array_merge(
      $this->_params,
      [
        'contribution_recur_id' => $contributionRecur['id'],
        'contribution_status_id' => 'Pending',
        'receive_date' => $dataSet['receive_date'],
      ])
    );
    $this->callAPISuccess('Contribution', 'completetransaction', [
      'id' => $contribution,
      'receive_date' => $dataSet['receive_date'],
    ]);
    $contributionRecur = $this->callAPISuccessGetSingle('ContributionRecur', [
      'id' => $contributionRecur['id'],
      'return' => ['next_sched_contribution_date', 'contribution_status_id'],
    ]);
    $this->assertEquals(5, $contributionRecur['contribution_status_id']);
    $this->assertEquals($dataSet['expected'], $contributionRecur['next_sched_contribution_date']);
    $this->callAPISuccess('Contribution', 'create', array_merge(
      $this->_params,
      [
        'contribution_recur_id' => $contributionRecur['id'],
        'contribution_status_id' => 'Completed',
      ]
    ));
    $contributionRecur = $this->callAPISuccessGetSingle('ContributionRecur', [
      'id' => $contributionRecur['id'],
      'return' => ['contribution_status_id'],
    ]);
    $this->assertEquals(1, $contributionRecur['contribution_status_id']);
  }

  /**
   * Get dates for testing.
   *
   * @return array
   */
  public function getScheduledDateData() {
    $result = [];
    $result[]['2016-08-31-1-month'] = [
      'data' => [
        'start_date' => '2016-08-31',
        'frequency_interval' => 1,
        'frequency_unit' => 'month',
      ],
      'receive_date' => '2016-08-31',
      'expected' => '2016-10-01 00:00:00',
    ];
    $result[]['2012-01-01-1-month'] = [
      'data' => [
        'start_date' => '2012-01-01',
        'frequency_interval' => 1,
        'frequency_unit' => 'month',
      ],
      'receive_date' => '2012-01-01',
      'expected' => '2012-02-01 00:00:00',
    ];
    $result[]['2012-01-01-1-month'] = [
      'data' => [
        'start_date' => '2012-01-01',
        'frequency_interval' => 1,
        'frequency_unit' => 'month',
      ],
      'receive_date' => '2012-02-29',
      'expected' => '2012-03-29 00:00:00',
    ];
    $result['receive_date_includes_time']['2012-01-01-1-month'] = [
      'data' => [
        'start_date' => '2012-01-01',
        'frequency_interval' => 1,
        'frequency_unit' => 'month',
        'next_sched_contribution_date' => '2012-02-29',
      ],
      'receive_date' => '2012-02-29 16:00:00',
      'expected' => '2012-03-29 00:00:00',
    ];
    return $result;
  }

  /**
   * Test completing a pledge with the completeTransaction api..
   *
   * Note that we are creating a logged in user because email goes out from
   * that person.
   */
  public function testCompleteTransactionUpdatePledgePayment() {
    $this->swapMessageTemplateForTestTemplate();
    $mut = new CiviMailUtils($this, TRUE);
    $mut->clearMessages();
    $this->createLoggedInUser();
    $contributionID = $this->createPendingPledgeContribution();
    $this->callAPISuccess('contribution', 'completetransaction', [
      'id' => $contributionID,
      'trxn_date' => '1 Feb 2013',
    ]);
    $pledge = $this->callAPISuccessGetSingle('Pledge', [
      'id' => $this->_ids['pledge'],
    ]);
    $this->assertEquals('Completed', $pledge['pledge_status']);

    $status = $this->callAPISuccessGetValue('PledgePayment', [
      'pledge_id' => $this->_ids['pledge'],
      'return' => 'status_id',
    ]);
    $this->assertEquals(1, $status);
    $mut->checkMailLog([
      'amount:::500.00',
      // The `receive_date` should remain as it was created.
      // TODO: the latest payment transaction date (and maybe other details,
      // such as amount and payment instrument) would be a useful token to make
      // available.
      'receive_date:::20120511000000',
      "receipt_date:::\n",
    ]);
    $mut->stop();
    $this->revertTemplateToReservedTemplate();
  }

  /**
   * Test completing a transaction with an event via the API.
   *
   * Note that we are creating a logged in user because email goes out from
   * that person
   *
   * @throws \CRM_Core_Exception
   */
  public function testCompleteTransactionWithParticipantRecord(): void {
    $mut = new CiviMailUtils($this, TRUE);
    $mut->clearMessages();
    $this->_individualId = $this->createLoggedInUser();
    $this->_params['source'] = 'Online Event Registration: Annual CiviCRM meet';
    $contributionID = $this->createPendingParticipantContribution();
    $this->createJoinedProfile(['entity_id' => $this->_ids['event']['test'], 'entity_table' => 'civicrm_event']);
    $this->createJoinedProfile(['entity_id' => $this->_ids['event']['test'], 'entity_table' => 'civicrm_event', 'weight' => 2], ['name' => 'post_1', 'title' => 'title_post_2', 'frontend_title' => 'public 2']);
    $this->createJoinedProfile(['entity_id' => $this->_ids['event']['test'], 'entity_table' => 'civicrm_event', 'weight' => 3], ['name' => 'post_2', 'title' => 'title_post_3', 'frontend_title' => 'public 3']);
    $this->eliminateUFGroupOne();

    $this->callAPISuccess('contribution', 'completetransaction', ['id' => $contributionID]);
    $contribution = $this->callAPISuccessGetSingle('Contribution', ['id' => $contributionID, 'return' => ['contribution_source']]);
    $this->assertEquals('Online Event Registration: Annual CiviCRM meet', $contribution['contribution_source']);
    $participantStatus = $this->callAPISuccessGetValue('participant', [
      'id' => $this->_ids['participant'],
      'return' => 'participant_status_id',
    ]);
    $this->assertEquals(1, $participantStatus);

    //Assert only three activities are created.
    $activities = $this->callAPISuccess('Activity', 'get', [
      'contact_id' => $this->_individualId,
    ])['values'];

    $this->assertCount(3, $activities);
    $activityNames = array_count_values(CRM_Utils_Array::collect('activity_name', $activities));
    // record two activities before and after completing payment for Event registration
    $this->assertEquals(2, $activityNames['Event Registration']);
    // update the original 'Contribution' activity created after completing payment
    $this->assertEquals(1, $activityNames['Contribution']);

    $mut->checkMailLog([
      'Annual CiviCRM meet',
      'Event',
      'This is a confirmation that your registration has been received and your status has been updated to Registered.',
      'First Name: Logged In',
      'Public title',
      'public 2',
      'public 3',
    ], ['Back end title', 'title_post_2', 'title_post_3']);
    $mut->stop();
  }

  /**
   * Test membership is renewed when transaction completed.
   *
   * @throws \API_Exception
   */
  public function testCompleteTransactionMembershipPriceSet(): void {
    $this->createPriceSetWithPage('membership');
    $this->createInitialPaidMembership();
    $membership = $this->callAPISuccess('Membership', 'getsingle', [
      'id' => $this->getMembershipID(),
      'status_id' => 'Grace',
      'return' => ['end_date'],
    ]);
    $this->assertEquals(date('Y-m-d', strtotime('yesterday')), $membership['end_date']);

    $this->createSubsequentPendingMembership();
    $this->callAPISuccess('Payment', 'create', [
      'contribution_id' => $this->getContributionID('second'),
      'total_amount' => 20,
    ]);
    $membership = $this->callAPISuccess('membership', 'getsingle', ['id' => $this->_ids['membership']]);
    $this->assertEquals(date('Y-m-d', strtotime('yesterday + 2 year')), $membership['end_date']);
    $logs = $this->callAPISuccess('MembershipLog', 'get', [
      'membership_id' => $this->getMembershipID(),
    ]);
    $this->assertEquals(4, $logs['count']);
    //Assert only three activities are created.
    $activityNames = (array) ActivityContact::get(FALSE)
      ->addWhere('contact_id', '=', $this->_ids['contact'])
      ->addSelect('activity_id.activity_type_id:name')->execute()->indexBy('activity_id.activity_type_id:name');
    $this->assertArrayHasKey('Contribution', $activityNames);
    $this->assertArrayHasKey('Membership Signup', $activityNames);
    $this->assertArrayHasKey('Change Membership Status', $activityNames);
  }

  /**
   * Test if renewal activity is create after changing Pending contribution to
   * Completed via offline
   *
   * @throws \CRM_Core_Exception
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testPendingToCompleteContribution(): void {
    $this->createPriceSetWithPage('membership');
    $this->setUpPendingContribution($this->_ids['price_field_value'][0]);
    $this->callAPISuccess('membership', 'getsingle', ['id' => $this->_ids['membership']]);
    // Case 1: Assert that Membership Signup Activity is created on Pending to Completed Contribution via backoffice
    $activity = $this->callAPISuccess('Activity', 'get', [
      'activity_type_id' => 'Membership Signup',
      'source_record_id' => $this->_ids['membership'],
      'status_id' => 'Scheduled',
    ]);
    $this->assertEquals(1, $activity['count']);

    // change pending contribution to completed
    $form = new CRM_Contribute_Form_Contribution();

    $form->_params = [
      'id' => $this->getContributionID(),
      'total_amount' => 20,
      'net_amount' => 20,
      'fee_amount' => 0,
      'financial_type_id' => 1,
      'contact_id' => $this->_individualId,
      'contribution_status_id' => 1,
      'billing_middle_name' => '',
      'billing_last_name' => 'Adams',
      'billing_street_address-5' => '790L Lincoln St S',
      'billing_city-5' => 'Mary knoll',
      'billing_state_province_id-5' => 1031,
      'billing_postal_code-5' => 10545,
      'billing_country_id-5' => 1228,
      'frequency_interval' => 1,
      'frequency_unit' => 'month',
      'installments' => '',
      'hidden_AdditionalDetail' => 1,
      'hidden_Premium' => 1,
      'from_email_address' => '"civi45" <civi45@civicrm.com>',
      'receipt_date' => '',
      'receipt_date_time' => '',
      'payment_processor_id' => $this->paymentProcessorID,
      'currency' => 'USD',
      'contribution_page_id' => $this->_ids['contribution_page'],
      'source' => 'Membership Signup and Renewal',
    ];

    $form->testSubmit($form->_params, CRM_Core_Action::UPDATE);

    // Case 2: After successful payment for Pending backoffice there are three activities created
    //  2.a Update status of existing Scheduled Membership Signup (created in step 1) to Completed
    $activity = $this->callAPISuccess('Activity', 'get', [
      'activity_type_id' => 'Membership Signup',
      'source_record_id' => $this->getMembershipID(),
      'status_id' => 'Completed',
    ]);
    $this->assertEquals(1, $activity['count']);
    // 2.b Contribution activity created to record successful payment
    $activity = $this->callAPISuccess('Activity', 'get', [
      'activity_type_id' => 'Contribution',
      'source_record_id' => $this->getContributionID(),
      'status_id' => 'Completed',
    ]);
    $this->assertEquals(1, $activity['count']);

    // 2.c 'Change membership type' activity created to record Membership status change from Grace to Current
    $activity = $this->callAPISuccess('Activity', 'get', [
      'activity_type_id' => 'Change Membership Status',
      'source_record_id' => $this->getMembershipID(),
      'status_id' => 'Completed',
    ]);
    $this->assertEquals(1, $activity['count']);
    $this->assertEquals('Status changed from Pending to New', $activity['values'][$activity['id']]['subject']);
    $membershipLogs = $this->callAPISuccess('MembershipLog', 'get', ['sequential' => 1])['values'];
    $this->assertEquals('Pending', CRM_Core_PseudoConstant::getName('CRM_Member_BAO_Membership', 'status_id', $membershipLogs[0]['status_id']));
    $this->assertEquals('New', CRM_Core_PseudoConstant::getName('CRM_Member_BAO_Membership', 'status_id', $membershipLogs[1]['status_id']));
    //Create another pending contribution for renewal
    $this->setUpPendingContribution($this->_ids['price_field_value'][0], 'second', [], ['entity_id' => $this->getMembershipID()], ['id' => $this->getMembershipID()]);

    //Update it to Failed.
    $form->_params['id'] = $this->getContributionID('second');
    $form->_params['contribution_status_id'] = 4;

    $form->testSubmit($form->_params, CRM_Core_Action::UPDATE);
    //Existing membership should not get updated to expired.
    $membership = $this->callAPISuccess('Membership', 'getsingle', ['id' => $this->_ids['membership']]);
    $this->assertNotEquals(4, $membership['status_id']);
  }

  /**
   * Test membership is renewed for 2 terms when transaction completed based on the line item having 2 terms as qty.
   *
   * Also check that altering the qty for the most recent contribution results in repeattransaction picking it up.
   */
  public function testCompleteTransactionMembershipPriceSetTwoTerms(): void {
    $this->createPriceSetWithPage('membership');
    $this->createInitialPaidMembership();
    $this->createSubsequentPendingMembership();

    $this->callAPISuccess('Payment', 'create', [
      'contribution_id' => $this->getContributionID('second'),
      'total_amount' => 20,
    ]);

    $membership = $this->callAPISuccessGetSingle('membership', ['id' => $this->getMembershipID()]);
    $this->assertEquals(date('Y-m-d', strtotime('yesterday + 2 years')), $membership['end_date']);

    $paymentProcessorID = $this->paymentProcessorAuthorizeNetCreate();

    $contributionRecurID = $this->callAPISuccess('ContributionRecur', 'create', ['contact_id' => $membership['contact_id'], 'payment_processor_id' => $paymentProcessorID, 'amount' => 20, 'frequency_interval' => 1])['id'];
    $this->callAPISuccess('Contribution', 'create', ['id' => $this->getContributionID(), 'contribution_recur_id' => $contributionRecurID]);
    $this->callAPISuccess('contribution', 'repeattransaction', ['contribution_recur_id' => $contributionRecurID, 'contribution_status_id' => 'Completed']);
    $membership = $this->callAPISuccessGetSingle('membership', ['id' => $this->getMembershipID()]);
    $this->assertEquals(date('Y-m-d', strtotime('yesterday + 4 years')), $membership['end_date']);

    // Update the most recent contribution to have a qty of 1 in it's line item and then repeat, expecting just 1 year to be added.
    $contribution = Contribution::get()->setOrderBy(['id' => 'DESC'])->setSelect(['id'])->execute()->first();
    CRM_Core_DAO::executeQuery('UPDATE civicrm_line_item SET price_field_value_id = ' . $this->_ids['price_field_value'][0] . ' WHERE contribution_id = ' . $contribution['id']);
    $this->callAPISuccess('contribution', 'repeattransaction', ['contribution_recur_id' => $contributionRecurID, 'contribution_status_id' => 'Completed']);
    $membership = $this->callAPISuccessGetSingle('membership', ['id' => $this->_ids['membership']]);
    $this->assertEquals(date('Y-m-d', strtotime('yesterday + 5 years')), $membership['end_date']);
  }

  public function cleanUpAfterPriceSets() {
    $this->quickCleanUpFinancialEntities();
    $this->contactDelete($this->_ids['contact']);
  }

  /**
   * Set up a pending transaction with a specific price field id.
   *
   * @param int $priceFieldValueID
   * @param string $key
   * @param array $contributionParams
   * @param array $lineParams
   * @param array $membershipParams
   */
  public function setUpPendingContribution(int $priceFieldValueID, string $key = 'first', array $contributionParams = [], array $lineParams = [], array $membershipParams = []): void {
    $contactID = $this->individualCreate();
    $membershipParams = array_merge([
      'contact_id' => $contactID,
      'membership_type_id' => $this->_ids['membership_type'],
    ], $membershipParams);
    if ($key === 'first') {
      // If we want these after the initial we will set them.
      $membershipParams['start_date'] = 'yesterday - 1 year';
      $membershipParams['end_date'] = 'yesterday';
      $membershipParams['join_date'] = 'yesterday - 1 year';
    }
    $contribution = $this->callAPISuccess('Order', 'create', array_merge([
      'domain_id' => 1,
      'contact_id' => $contactID,
      'receive_date' => date('Ymd'),
      'total_amount' => 20.00,
      'financial_type_id' => 1,
      'payment_instrument_id' => 'Credit Card',
      'non_deductible_amount' => 10.00,
      'source' => 'SSF',
      'contribution_page_id' => $this->_ids['contribution_page'],
      'line_items' => [
        [
          'line_item' => [
            array_merge([
              'price_field_id' => $this->_ids['price_field'][0],
              'qty' => 1,
              'entity_table' => 'civicrm_membership',
              'unit_price' => 20,
              'line_total' => 20,
              'financial_type_id' => 1,
              'price_field_value_id' => $priceFieldValueID,
            ], $lineParams),
          ],
          'params' => $membershipParams,
        ],
      ],
    ], $contributionParams));

    $this->_ids['contact'] = $contactID;
    $this->ids['contribution'][$key] = $contribution['id'];
    $this->_ids['membership'] = $this->callAPISuccessGetValue('MembershipPayment', ['return' => 'membership_id', 'contribution_id' => $contribution['id']]);
  }

  /**
   * Test sending a mail via the API.
   *
   * @throws \CRM_Core_Exception
   */
  public function testSendMail(): void {
    $mut = new CiviMailUtils($this, TRUE);
    $orderParams = $this->_params;
    $orderParams['contribution_status_id'] = 'Pending';
    $orderParams['api.PaymentProcessor.pay'] = [
      'payment_processor_id' => $this->paymentProcessorID,
      'credit_card_type' => 'Visa',
      'credit_card_number' => 41111111111111,
      'amount' => 5,
    ];

    $order = $this->callAPISuccess('Order', 'create', $orderParams);
    $this->callAPISuccess('Payment', 'create', ['total_amount' => 5, 'is_send_notification' => 0, 'order_id' => $order['id']]);
    $address = $this->callAPISuccess('Address', 'create', ['contribution_id' => $order['id'], 'name' => 'bob', 'contact_id' => 1, 'street_address' => 'blah']);
    $this->callAPISuccess('Contribution', 'create', ['id' => $order['id'], 'address_id' => $address['id']]);
    $this->callAPISuccess('contribution', 'sendconfirmation', [
      'id' => $order['id'],
      'receipt_from_email' => 'api@civicrm.org',
    ]);
    $mut->checkMailLog([
      '$ 100.00',
      'Contribution Information',
    ], [
      'Event',
    ]);

    $this->checkCreditCardDetails($mut, $order['id']);
    $mut->stop();
    $tplVars = CRM_Core_Smarty::singleton()->get_template_vars();
    $this->assertEquals('bob', $tplVars['billingName']);
  }

  /**
   * Test sending a mail via the API.
   * This simulates webform_civicrm using pay later contribution page
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testSendConfirmationPayLater(): void {
    $mut = new CiviMailUtils($this, TRUE);
    // Create contribution page
    $pageParams = [
      'title' => 'Webform Contributions',
      'financial_type_id' => 1,
      'contribution_type_id' => 1,
      'is_confirm_enabled' => 1,
      'is_pay_later' => 1,
      'pay_later_text' => 'I will send payment by cheque',
      'pay_later_receipt' => 'Send your cheque payable to "CiviCRM LLC" to the office',
    ];
    $contributionPage = $this->callAPISuccess('ContributionPage', 'create', $pageParams);

    // Create pay later contribution
    $contribution = $this->callAPISuccess('Order', 'create', [
      'contact_id' => $this->_individualId,
      'financial_type_id' => 1,
      'is_pay_later' => 1,
      'contribution_status_id' => 2,
      'contribution_page_id' => $contributionPage['id'],
      'total_amount' => '10.00',
      'line_items' => [
        [
          'line_item' => [
            [
              'entity_table' => 'civicrm_contribution',
              'label' => 'My lineitem label',
              'qty' => 1,
              'unit_price' => '10.00',
              'line_total' => '10.00',
            ],
          ],
        ],
      ],
    ]);

    // Create email
    try {
      civicrm_api3('contribution', 'sendconfirmation', [
        'id' => $contribution['id'],
        'receipt_from_email' => 'api@civicrm.org',
      ]);
    }
    catch (Exception $e) {
      // Need to figure out how to stop this some other day
      // We don't care about the Payment Processor because this is Pay Later
      // The point of this test is to check we get the pay_later version of the mail
      if ($e->getMessage() !== "Undefined variable: CRM16923AnUnreliableMethodHasBeenUserToDeterminePaymentProcessorFromContributionPage") {
        throw $e;
      }
    }

    // Retrieve mail & check it has the pay_later_receipt info
    $mut->getMostRecentEmail('raw');
    $mut->checkMailLog([
      (string) 10,
      $pageParams['pay_later_receipt'],
    ], [
      'Event',
    ]);
    $this->checkReceiptDetails($mut, $contributionPage['id'], $contribution['id'], $pageParams);
    $mut->stop();
  }

  /**
   * Check credit card details in sent mail via API
   *
   * @param CiviMailUtils $mut
   * @param int $contributionID Contribution ID
   *
   * @throws \CRM_Core_Exception
   */
  public function checkCreditCardDetails($mut, $contributionID) {
    $this->callAPISuccess('contribution', 'create', $this->_params);
    $this->callAPISuccess('contribution', 'sendconfirmation', [
      'id' => $contributionID,
      'receipt_from_email' => 'api@civicrm.org',
      'payment_processor_id' => $this->paymentProcessorID,
    ]);
    $mut->checkMailLog([
      // billing header
      'Billing Name and Address',
      // billing name
      'anthony_anderson@civicrm.org',
    ], [
      'Event',
    ]);
  }

  /**
   * Check receipt details in sent mail via API
   *
   * @param CiviMailUtils $mut
   * @param int $pageID Page ID
   * @param int $contributionID Contribution ID
   * @param array $pageParams
   *
   * @throws \CRM_Core_Exception
   */
  public function checkReceiptDetails($mut, $pageID, $contributionID, $pageParams): void {
    $pageReceipt = [
      'receipt_from_name' => 'Page FromName',
      'receipt_from_email' => 'page_from@email.com',
      'cc_receipt' => 'page_cc@email.com',
      'receipt_text' => 'Page Receipt Text',
      'pay_later_receipt' => $pageParams['pay_later_receipt'],
    ];
    $customReceipt = [
      'receipt_from_name' => 'Custom FromName',
      'receipt_from_email' => 'custom_from@email.com',
      'cc_receipt' => 'custom_cc@email.com',
      'receipt_text' => 'Test Custom Receipt Text',
      'pay_later_receipt' => 'Mail your check to test@example.com within 3 business days.',
    ];
    $this->callAPISuccess('ContributionPage', 'create', array_merge([
      'id' => $pageID,
      'is_email_receipt' => 1,
    ], $pageReceipt));

    $this->callAPISuccess('contribution', 'sendconfirmation', array_merge([
      'id' => $contributionID,
      'payment_processor_id' => $this->paymentProcessorID,
    ], $customReceipt));

    //Verify if custom receipt details are present in email.
    //Page receipt details shouldn't be included.
    $mut->checkMailLog(array_values($customReceipt), array_values($pageReceipt));
  }

  /**
   * Test sending a mail via the API.
   */
  public function testSendMailEvent() {
    $mut = new CiviMailUtils($this, TRUE);
    $contribution = $this->callAPISuccess('contribution', 'create', $this->_params);
    $event = $this->eventCreate([
      'is_email_confirm' => 1,
      'confirm_from_email' => 'test@civicrm.org',
    ]);
    $this->_eventID = $event['id'];
    $participantParams = [
      'contact_id' => $this->_individualId,
      'event_id' => $this->_eventID,
      'status_id' => 1,
      'role_id' => 1,
      // to ensure it matches later on
      'register_date' => '2007-07-21 00:00:00',
      'source' => 'Online Event Registration: API Testing',

    ];
    $participant = $this->callAPISuccess('participant', 'create', $participantParams);
    $this->callAPISuccess('participant_payment', 'create', [
      'participant_id' => $participant['id'],
      'contribution_id' => $contribution['id'],
    ]);
    $this->callAPISuccess('contribution', 'sendconfirmation', [
      'id' => $contribution['id'],
      'receipt_from_email' => 'api@civicrm.org',
    ]);

    $mut->checkMailLog([
      'Annual CiviCRM meet',
      'Event',
      'To: "Mr. Anthony Anderson II" <anthony_anderson@civicrm.org>',
    ], []);
    $mut->stop();
  }

  /**
   * This function does a GET & compares the result against the $params.
   *
   * Use as a double check on Creates.
   *
   * @param array $params
   * @param int $id
   * @param bool $delete
   *
   * @throws \CRM_Core_Exception
   */
  public function contributionGetnCheck(array $params, int $id, bool $delete = TRUE): void {
    $contribution = $this->callAPISuccess('Contribution', 'Get', [
      'id' => $id,
      'return' => array_merge(['contribution_source'], array_keys($params)),
    ]);
    if ($delete) {
      $this->callAPISuccess('contribution', 'delete', ['id' => $id]);
    }
    $this->assertAPISuccess($contribution, 0);
    $values = $contribution['values'][$contribution['id']];
    $params['receive_date'] = date('Y-m-d H:i:s', strtotime($params['receive_date']));
    // this is not returned in id format
    unset($params['payment_instrument_id']);
    $params['contribution_source'] = $params['source'];
    unset($params['source'], $params['sequential']);
    foreach ($params as $key => $value) {
      $this->assertEquals($value, $values[$key], $key . " value: $value doesn't match " . print_r($values, TRUE));
    }
  }

  /**
   * Create a pending contribution & linked pending pledge record.
   *
   * @throws \CRM_Core_Exception
   */
  public function createPendingPledgeContribution() {

    $pledgeID = $this->pledgeCreate(['contact_id' => $this->_individualId, 'installments' => 1, 'amount' => 500]);
    $this->_ids['pledge'] = $pledgeID;
    $contribution = $this->callAPISuccess('Contribution', 'create', array_merge($this->_params, [
      'contribution_status_id' => 'Pending',
      'total_amount' => 500,
    ]));
    $paymentID = $this->callAPISuccessGetValue('PledgePayment', [
      'options' => ['limit' => 1],
      'return' => 'id',
    ]);
    $this->callAPISuccess('PledgePayment', 'create', [
      'id' => $paymentID,
      'contribution_id' =>
      $contribution['id'],
      'status_id' => 'Pending',
      'scheduled_amount' => 500,
    ]);

    return $contribution['id'];
  }

  /**
   * Create a pending contribution & linked pending participant record (along
   * with an event).
   *
   * @throws \CRM_Core_Exception
   */
  public function createPendingParticipantContribution() {
    $this->_ids['event']['test'] = $this->eventCreate(['is_email_confirm' => 1, 'confirm_from_email' => 'test@civicrm.org'])['id'];
    $participantID = $this->participantCreate(['event_id' => $this->_ids['event']['test'], 'status_id' => 6, 'contact_id' => $this->_individualId]);
    $this->_ids['participant'] = $participantID;
    $params = array_merge($this->_params, ['contact_id' => $this->_individualId, 'contribution_status_id' => 2, 'financial_type_id' => 'Event Fee']);
    $contribution = $this->callAPISuccess('contribution', 'create', $params);
    $this->callAPISuccess('participant_payment', 'create', [
      'contribution_id' => $contribution['id'],
      'participant_id' => $participantID,
    ]);
    $this->callAPISuccess('line_item', 'get', [
      'entity_id' => $contribution['id'],
      'entity_table' => 'civicrm_contribution',
      'api.line_item.create' => [
        'entity_id' => $participantID,
        'entity_table' => 'civicrm_participant',
      ],
    ]);
    return $contribution['id'];
  }

  /**
   * Get financial transaction amount.
   *
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

    return CRM_Core_DAO::singleValueQuery($query);
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
    return CRM_Core_DAO::singleValueQuery($query);
  }

  /**
   * @param int $contId
   * @param $context
   */
  public function _checkFinancialItem($contId, $context) {
    if ($context !== 'paylater') {
      $params = [
        'entity_id' => $contId,
        'entity_table' => 'civicrm_contribution',
      ];
      $trxn = current(CRM_Financial_BAO_FinancialItem::retrieveEntityFinancialTrxn($params, TRUE));
      $entityParams = [
        'financial_trxn_id' => $trxn['financial_trxn_id'],
        'entity_table' => 'civicrm_financial_item',
      ];
      $entityTrxn = current(CRM_Financial_BAO_FinancialItem::retrieveEntityFinancialTrxn($entityParams));
      $params = [
        'id' => $entityTrxn['entity_id'],
      ];
    }
    if ($context === 'paylater') {
      $lineItems = CRM_Price_BAO_LineItem::getLineItems($contId, 'contribution');
      foreach ($lineItems as $key => $item) {
        $params = [
          'entity_id' => $key,
          'entity_table' => 'civicrm_line_item',
        ];
        $compareParams = ['status_id' => 1];
        $this->assertDBCompareValues('CRM_Financial_DAO_FinancialItem', $params, $compareParams);
      }
    }
    elseif ($context === 'refund') {
      $compareParams = [
        'status_id' => 1,
        'financial_account_id' => 1,
        'amount' => -100,
      ];
    }
    elseif ($context === 'cancelPending') {
      $compareParams = [
        'status_id' => 3,
        'financial_account_id' => 1,
        'amount' => -100,
      ];
    }
    elseif ($context === 'changeFinancial') {
      $lineKey = key(CRM_Price_BAO_LineItem::getLineItems($contId, 'contribution'));
      $params = [
        'entity_id' => $lineKey,
        'amount' => -100,
      ];
      $compareParams = [
        'financial_account_id' => 1,
      ];
      $this->assertDBCompareValues('CRM_Financial_DAO_FinancialItem', $params, $compareParams);
      $params = [
        'financial_account_id' => 3,
        'entity_id' => $lineKey,
      ];
      $compareParams = [
        'amount' => 100,
      ];
    }
    if ($context !== 'paylater') {
      $this->assertDBCompareValues('CRM_Financial_DAO_FinancialItem', $params, $compareParams);
    }
  }

  /**
   * Check correct financial transaction entries were created for the change in payment instrument.
   *
   * @param int $contributionID
   * @param int $originalInstrumentID
   * @param int $newInstrumentID
   * @param int $amount
   */
  public function checkFinancialTrxnPaymentInstrumentChange($contributionID, $originalInstrumentID, $newInstrumentID, $amount = 100) {

    $entityFinancialTrxns = $this->getFinancialTransactionsForContribution($contributionID);

    $originalTrxnParams = [
      'to_financial_account_id' => CRM_Financial_BAO_FinancialTypeAccount::getInstrumentFinancialAccount($originalInstrumentID),
      'payment_instrument_id' => $originalInstrumentID,
      'amount' => $amount,
      'status_id' => 1,
    ];

    $reversalTrxnParams = [
      'to_financial_account_id' => CRM_Financial_BAO_FinancialTypeAccount::getInstrumentFinancialAccount($originalInstrumentID),
      'payment_instrument_id' => $originalInstrumentID,
      'amount' => -$amount,
      'status_id' => 1,
    ];

    $newTrxnParams = [
      'to_financial_account_id' => CRM_Financial_BAO_FinancialTypeAccount::getInstrumentFinancialAccount($newInstrumentID),
      'payment_instrument_id' => $newInstrumentID,
      'amount' => $amount,
      'status_id' => 1,
    ];

    foreach ([$originalTrxnParams, $reversalTrxnParams, $newTrxnParams] as $index => $transaction) {
      $entityFinancialTrxn = $entityFinancialTrxns[$index];
      $this->assertEquals($entityFinancialTrxn['amount'], $transaction['amount']);

      $financialTrxn = $this->callAPISuccessGetSingle('FinancialTrxn', [
        'id' => $entityFinancialTrxn['financial_trxn_id'],
      ]);
      $this->assertEquals($transaction['status_id'], $financialTrxn['status_id']);
      $this->assertEquals($transaction['amount'], $financialTrxn['total_amount']);
      $this->assertEquals($transaction['amount'], $financialTrxn['net_amount']);
      $this->assertEquals(0, $financialTrxn['fee_amount']);
      $this->assertEquals($transaction['payment_instrument_id'], $financialTrxn['payment_instrument_id']);
      $this->assertEquals($transaction['to_financial_account_id'], $financialTrxn['to_financial_account_id']);

      // Generic checks.
      $this->assertEquals(1, $financialTrxn['is_payment']);
      $this->assertEquals('USD', $financialTrxn['currency']);
      $this->assertEquals(date('Y-m-d'), date('Y-m-d', strtotime($financialTrxn['trxn_date'])));
    }
  }

  /**
   * Check financial transaction.
   *
   * @todo break this down into sensible functions - most calls to it only use a few lines out of the big if.
   *
   * @param array $contribution
   * @param string $context
   * @param int $instrumentId
   * @param array $extraParams
   */
  public function _checkFinancialTrxn($contribution, $context, $instrumentId = NULL, $extraParams = []) {
    $financialTrxns = $this->getFinancialTransactionsForContribution($contribution['id']);
    $trxn = array_pop($financialTrxns);

    $params = [
      'id' => $trxn['financial_trxn_id'],
    ];
    if ($context === 'payLater') {
      $compareParams = [
        'status_id' => 1,
        'from_financial_account_id' => CRM_Contribute_PseudoConstant::getRelationalFinancialAccount($contribution['financial_type_id'], 'Accounts Receivable Account is'),
      ];
    }
    elseif ($context === 'refund') {
      $compareParams = [
        'to_financial_account_id' => 6,
        'total_amount' => -100,
        'status_id' => 7,
        'trxn_date' => '2015-01-01 09:00:00',
        'trxn_id' => 'the refund',
      ];
    }
    elseif ($context === 'cancelPending') {
      $compareParams = [
        'to_financial_account_id' => 7,
        'total_amount' => -100,
        'status_id' => 3,
      ];
    }
    elseif ($context === 'changeFinancial' || $context === 'paymentInstrument') {
      // @todo checkFinancialTrxnPaymentInstrumentChange instead for paymentInstrument.
      // It does the same thing with greater readability.
      // @todo remove handling for

      $entityParams = [
        'entity_id' => $contribution['id'],
        'entity_table' => 'civicrm_contribution',
        'amount' => -100,
      ];
      $trxn = current(CRM_Financial_BAO_FinancialItem::retrieveEntityFinancialTrxn($entityParams));
      $trxnParams1 = [
        'id' => $trxn['financial_trxn_id'],
      ];
      if (empty($extraParams)) {
        $compareParams = [
          'total_amount' => -100,
          'status_id' => 1,
        ];
      }
      elseif ($context !== 'changeFinancial') {
        $compareParams = [
          'total_amount' => 100,
          'status_id' => 1,
        ];
      }
      if ($context === 'paymentInstrument') {
        $compareParams['to_financial_account_id'] = CRM_Financial_BAO_FinancialTypeAccount::getInstrumentFinancialAccount($instrumentId);
        $compareParams['payment_instrument_id'] = $instrumentId;
      }
      else {
        $compareParams['to_financial_account_id'] = 12;
      }
      $this->assertDBCompareValues('CRM_Financial_DAO_FinancialTrxn', $trxnParams1, array_merge($compareParams, $extraParams));
      $compareParams['total_amount'] = 100;
      // Reverse the extra params now that we will be checking the new positive transaction.
      if ($context === 'changeFinancial' && !empty($extraParams)) {
        foreach ($extraParams as $param => $value) {
          $extraParams[$param] = 0 - $value;
        }
      }
    }

    $this->assertDBCompareValues('CRM_Financial_DAO_FinancialTrxn', $params, array_merge($compareParams, $extraParams));
  }

  /**
   * @return mixed
   */
  public function _addPaymentInstrument() {
    $gId = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', 'payment_instrument', 'id', 'name');
    $optionParams = [
      'option_group_id' => $gId,
      'label' => 'Test Card',
      'name' => 'Test Card',
      'value' => '6',
      'weight' => '6',
      'is_active' => 1,
    ];
    $optionValue = $this->callAPISuccess('option_value', 'create', $optionParams);
    $relationTypeId = key(CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Asset Account is' "));
    $financialParams = [
      'entity_table' => 'civicrm_option_value',
      'entity_id' => $optionValue['id'],
      'account_relationship' => $relationTypeId,
      'financial_account_id' => 7,
    ];
    CRM_Financial_BAO_FinancialTypeAccount::add($financialParams);
    $this->assertNotEmpty($optionValue['values'][$optionValue['id']]['value']);
    return $optionValue['values'][$optionValue['id']]['value'];
  }

  public function _deletedAddedPaymentInstrument() {
    $result = $this->callAPISuccess('OptionValue', 'get', [
      'option_group_id' => 'payment_instrument',
      'name' => 'Test Card',
      'value' => '6',
      'is_active' => 1,
    ]);
    if ($id = CRM_Utils_Array::value('id', $result)) {
      $this->callAPISuccess('OptionValue', 'delete', ['id' => $id]);
    }
  }

  /**
   * Set up the basic recurring contribution for tests.
   *
   * @param array $generalParams
   *   Parameters that can be merged into the recurring AND the contribution.
   *
   * @param array $recurParams
   *   Parameters to merge into the recur only.
   *
   * @return array|int
   * @throws \CRM_Core_Exception
   */
  protected function setUpRecurringContribution($generalParams = [], $recurParams = []) {
    $contributionRecur = $this->callAPISuccess('contribution_recur', 'create', array_merge([
      'contact_id' => $this->_individualId,
      'installments' => '12',
      'frequency_interval' => '1',
      'amount' => '100',
      'contribution_status_id' => 1,
      'start_date' => '2012-01-01 00:00:00',
      'currency' => 'USD',
      'frequency_unit' => 'month',
      'payment_processor_id' => $this->paymentProcessorID,
    ], $generalParams, $recurParams));
    $contributionParams = array_merge(
      $this->_params,
      [
        'contribution_recur_id' => $contributionRecur['id'],
        'contribution_status_id' => 'Pending',
      ], $generalParams);
    $contributionParams['api.Payment.create'] = ['total_amount' => $contributionParams['total_amount']];
    $originalContribution = $this->callAPISuccess('Order', 'create', $contributionParams);
    return $originalContribution;
  }

  /**
   * Set up a basic auto-renew membership for tests.
   *
   * @param array $generalParams
   *   Parameters that can be merged into the recurring AND the contribution.
   *
   * @param array $recurParams
   *   Parameters to merge into the recur only.
   *
   * @return array|int
   * @throws \CRM_Core_Exception
   */
  protected function setUpAutoRenewMembership($generalParams = [], $recurParams = []) {
    $newContact = $this->callAPISuccess('Contact', 'create', [
      'contact_type' => 'Individual',
      'sort_name' => 'McTesterson, Testy',
      'display_name' => 'Testy McTesterson',
      'preferred_language' => 'en_US',
      'preferred_mail_format' => 'Both',
      'first_name' => 'Testy',
      'last_name' => 'McTesterson',
      'contact_is_deleted' => '0',
      'email_id' => '4',
      'email' => 'tmctesterson@example.com',
      'on_hold' => '0',
    ]);
    $membershipType = $this->callAPISuccess('MembershipType', 'create', [
      'domain_id' => 'Default Domain Name',
      'member_of_contact_id' => 1,
      'financial_type_id' => 'Member Dues',
      'duration_unit' => 'month',
      'duration_interval' => 1,
      'period_type' => 'rolling',
      'name' => 'Standard Member',
      'minimum_fee' => 100,
    ]);
    $contributionRecur = $this->callAPISuccess('contribution_recur', 'create', array_merge([
      'contact_id' => $newContact['id'],
      'installments' => '12',
      'frequency_interval' => '1',
      'amount' => '100',
      'contribution_status_id' => 1,
      'start_date' => '2012-01-01 00:00:00',
      'currency' => 'USD',
      'frequency_unit' => 'month',
      'payment_processor_id' => $this->paymentProcessorID,
    ], $generalParams, $recurParams));

    $originalContribution = $this->callAPISuccess('Order', 'create', array_merge(
      $this->_params,
      [
        'contact_id' => $newContact['id'],
        'contribution_recur_id' => $contributionRecur['id'],
        'financial_type_id' => 'Member Dues',
        'api.Payment.create' => ['total_amount' => 100, 'payment_instrument_id' => 'Credit card'],
        'invoice_id' => 2345,
        'line_items' => [
          [
            'line_item' => [
              [
                'membership_type_id' => $membershipType['id'],
                'financial_type_id' => 'Member Dues',
                'line_total' => $generalParams['total_amount'] ?? 100,
              ],
            ],
            'params' => [
              'contact_id' => $newContact['id'],
              'contribution_recur_id' => $contributionRecur['id'],
              'membership_type_id' => $membershipType['id'],
              'num_terms' => 1,
            ],
          ],
        ],
      ], $generalParams)
    );
    $lineItem = $this->callAPISuccess('LineItem', 'getsingle', []);
    $this->assertEquals('civicrm_membership', $lineItem['entity_table']);
    $membership = $this->callAPISuccess('Membership', 'getsingle', ['id' => $lineItem['entity_id']]);
    $this->callAPISuccess('LineItem', 'getsingle', []);
    $this->callAPISuccessGetCount('MembershipPayment', ['membership_id' => $membership['id']], 1);

    return [$originalContribution, $membership];
  }

  /**
   * Set up a repeat transaction.
   *
   * @param array $recurParams
   * @param string $flag
   * @param array $contributionParams
   *
   * @return array
   */
  protected function setUpRepeatTransaction(array $recurParams, $flag, array $contributionParams = []) {
    $paymentProcessorID = $this->paymentProcessorCreate();
    $contributionRecur = $this->callAPISuccess('contribution_recur', 'create', array_merge([
      'contact_id' => $this->_individualId,
      'installments' => '12',
      'frequency_interval' => '1',
      'amount' => '500',
      'contribution_status_id' => 1,
      'start_date' => '2012-01-01 00:00:00',
      'currency' => 'USD',
      'frequency_unit' => 'month',
      'payment_processor_id' => $paymentProcessorID,
    ], $recurParams));

    $originalContribution = '';
    if ($flag === 'multiple') {
      // CRM-19309 create a contribution + also add in line_items (plural):
      $this->createContributionWithTwoLineItemsAgainstPriceSet([
        'contact_id' => $this->_individualId,
        'contribution_recur_id' => $contributionRecur['id'],
      ]);
      $originalContribution = $this->callAPISuccessGetSingle('Contribution', ['contribution_recur_id' => $contributionRecur['id'], 'return' => 'id']);
    }
    elseif ($flag === 'single') {
      $params = array_merge($this->_params, ['contribution_recur_id' => $contributionRecur['id']]);
      $params = array_merge($params, $contributionParams);
      $originalContribution = $this->callAPISuccess('Order', 'create', $params);
      // Note the saved contribution amount will include tax.
      $this->callAPISuccess('Payment', 'create', [
        'contribution_id' => $originalContribution['id'],
        'total_amount' => $originalContribution['values'][$originalContribution['id']]['total_amount'],
      ]);
    }
    $originalContribution['contribution_recur_id'] = $contributionRecur['id'];
    $originalContribution['payment_processor_id'] = $paymentProcessorID;
    return $originalContribution;
  }

  /**
   * Common set up routine.
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected function setUpForCompleteTransaction(): array {
    $this->mut = new CiviMailUtils($this, TRUE);
    $this->createLoggedInUser();
    $params = array_merge($this->_params, ['contribution_status_id' => 2, 'receipt_date' => 'now']);
    return $this->callAPISuccess('Contribution', 'create', $params);
  }

  /**
   * Test repeat contribution uses the Payment Processor' payment_instrument setting.
   *
   * @throws \CRM_Core_Exception
   */
  public function testRepeatTransactionWithNonCreditCardDefault() {
    $contributionRecur = $this->callAPISuccess('ContributionRecur', 'create', [
      'contact_id' => $this->_individualId,
      'installments' => '12',
      'frequency_interval' => '1',
      'amount' => '100',
      'contribution_status_id' => 1,
      'start_date' => '2012-01-01 00:00:00',
      'currency' => 'USD',
      'frequency_unit' => 'month',
      'payment_processor_id' => $this->paymentProcessorID,
    ]);
    $contribution1 = $this->callAPISuccess('contribution', 'create', array_merge(
        $this->_params,
        ['contribution_recur_id' => $contributionRecur['id'], 'payment_instrument_id' => 2])
    );
    $contribution2 = $this->callAPISuccess('contribution', 'repeattransaction', [
      'contribution_status_id' => 'Completed',
      'trxn_id' => 'blah',
      'original_contribution_id' => $contribution1,
    ]);
    $this->assertEquals('Debit Card', CRM_Contribute_PseudoConstant::getLabel('CRM_Contribute_BAO_Contribution', 'payment_instrument_id', $contribution2['values'][$contribution2['id']]['payment_instrument_id']));
  }

  /**
   * CRM-20008 Tests repeattransaction creates pending membership.
   *
   * @throws \CRM_Core_Exception
   */
  public function testRepeatTransactionMembershipCreatePendingContribution(): void {
    [$originalContribution, $membership] = $this->setUpAutoRenewMembership();
    $this->callAPISuccess('membership', 'create', [
      'id' => $membership['id'],
      'end_date' => 'yesterday',
      'status_id' => 'Expired',
    ]);
    $repeatedContribution = $this->callAPISuccess('contribution', 'repeattransaction', [
      'contribution_recur_id' => $originalContribution['values'][1]['contribution_recur_id'],
      'contribution_status_id' => 'Pending',
      'trxn_id' => 1234,
    ]);
    $membershipStatusId = $this->callAPISuccess('membership', 'getvalue', [
      'id' => $membership['id'],
      'return' => 'status_id',
    ]);

    // Let's see if the membership payments got created while we're at it.
    $membershipPayments = $this->callAPISuccess('MembershipPayment', 'get', [
      'membership_id' => $membership['id'],
    ]);
    $this->assertEquals(2, $membershipPayments['count']);

    $this->assertEquals('Expired', CRM_Core_PseudoConstant::getLabel('CRM_Member_BAO_Membership', 'status_id', $membershipStatusId));
    $this->callAPISuccess('Contribution', 'completetransaction', ['id' => $repeatedContribution['id']]);
    $membership = $this->callAPISuccessGetSingle('membership', [
      'id' => $membership['id'],
      'return' => 'status_id, end_date',
    ]);
    $this->assertEquals('New', CRM_Core_PseudoConstant::getName('CRM_Member_BAO_Membership', 'status_id', $membership['status_id']));
    $this->assertEquals(date('Y-m-d', strtotime('yesterday + 1 month')), $membership['end_date']);
  }

  /**
   * Test sending a mail via the API.
   *
   * @throws \CRM_Core_Exception
   */
  public function testSendMailWithAPISetFromDetails() {
    $mut = new CiviMailUtils($this, TRUE);
    $contribution = $this->callAPISuccess('contribution', 'create', $this->_params);
    $this->callAPISuccess('contribution', 'sendconfirmation', [
      'id' => $contribution['id'],
      'receipt_from_email' => 'api@civicrm.org',
      'receipt_from_name' => 'CiviCRM LLC',
    ]);
    $mut->checkMailLog([
      'From: CiviCRM LLC <api@civicrm.org>',
      'Contribution Information',
    ], [
      'Event',
    ]);
    $mut->stop();
  }

  /**
   * Test sending a mail via the API.
   */
  public function testSendMailWithNoFromSetFallToDomain() {
    $this->createLoggedInUser();
    $mut = new CiviMailUtils($this, TRUE);
    $contribution = $this->callAPISuccess('contribution', 'create', $this->_params);
    $this->callAPISuccess('contribution', 'sendconfirmation', [
      'id' => $contribution['id'],
    ]);
    $domain = $this->callAPISuccess('domain', 'getsingle', ['id' => 1]);
    $mut->checkMailLog([
      'From: ' . $domain['from_name'] . ' <' . $domain['from_email'] . '>',
      'Contribution Information',
    ], [
      'Event',
    ]);
    $mut->stop();
  }

  /**
   * Test sending a mail via the API.
   *
   * @throws \CRM_Core_Exception
   */
  public function testSendMailWithRepeatTransactionAPIFalltoDomain() {
    $this->createLoggedInUser();
    $mut = new CiviMailUtils($this, TRUE);
    $contribution = $this->setUpRepeatTransaction([], 'single');
    $this->callAPISuccess('contribution', 'repeattransaction', [
      'contribution_status_id' => 'Completed',
      'trxn_id' => 7890,
      'original_contribution_id' => $contribution,
    ]);
    $domain = $this->callAPISuccess('domain', 'getsingle', ['id' => 1]);
    $mut->checkMailLog([
      'From: ' . $domain['from_name'] . ' <' . $domain['from_email'] . '>',
      'Contribution Information',
    ], [
      'Event',
    ]
    );
    $mut->stop();
  }

  /**
   * Test sending a mail via the API.
   *
   * @throws \CRM_Core_Exception
   */
  public function testSendMailWithRepeatTransactionAPIFalltoContributionPage() {
    $mut = new CiviMailUtils($this, TRUE);
    $contributionPage = $this->contributionPageCreate(['receipt_from_name' => 'CiviCRM LLC', 'receipt_from_email' => 'contributionpage@civicrm.org', 'is_email_receipt' => 1]);
    $paymentProcessorID = $this->paymentProcessorCreate();
    $contributionRecur = $this->callAPISuccess('contribution_recur', 'create', [
      'contact_id' => $this->_individualId,
      'installments' => '12',
      'frequency_interval' => '1',
      'amount' => '500',
      'contribution_status_id' => 1,
      'start_date' => '2012-01-01 00:00:00',
      'currency' => 'USD',
      'frequency_unit' => 'month',
      'payment_processor_id' => $paymentProcessorID,
    ]);
    $originalContribution = $this->callAPISuccess('contribution', 'create', array_merge(
      $this->_params,
      [
        'contribution_recur_id' => $contributionRecur['id'],
        'contribution_page_id' => $contributionPage['id'],
      ])
    );
    $this->callAPISuccess('Contribution', 'repeattransaction', [
      'contribution_status_id' => 'Completed',
      'trxn_id' => 5678,
      'original_contribution_id' => $originalContribution,
    ]
    );
    $mut->checkMailLog([
      'From: CiviCRM LLC <contributionpage@civicrm.org>',
      'Contribution Information',
    ], [
      'Event',
    ]);
    $mut->stop();
  }

  /**
   * Test sending a mail via the API.
   *
   * @throws \CRM_Core_Exception
   */
  public function testSendMailWithRepeatTransactionAPIFalltoSystemFromNoDefaultFrom(): void {
    $mut = new CiviMailUtils($this, TRUE);
    $originalContribution = $this->setUpRepeatTransaction([], 'single');
    $fromEmail = $this->callAPISuccess('optionValue', 'get', ['is_default' => 1, 'option_group_id' => 'from_email_address', 'sequential' => 1]);
    foreach ($fromEmail['values'] as $from) {
      $this->callAPISuccess('optionValue', 'create', ['is_default' => 0, 'id' => $from['id']]);
    }
    $domain = $this->callAPISuccess('domain', 'getsingle', ['id' => CRM_Core_Config::domainID()]);
    $this->callAPISuccess('contribution', 'repeattransaction', [
      'contribution_status_id' => 'Completed',
      'trxn_id' => 4567,
      'original_contribution_id' => $originalContribution,
    ]);
    $mut->checkMailLog([
      'From: ' . $domain['name'] . ' <' . $domain['domain_email'] . '>',
      'Contribution Information',
    ], [
      'Event',
    ]);
    $mut->stop();
  }

  /**
   * Create a Contribution Page with is_email_receipt = TRUE.
   *
   * @param array $params
   *   Params to overwrite with.
   *
   * @return array|int
   */
  protected function createReceiptableContributionPage($params = []) {
    $contributionPage = $this->callAPISuccess('ContributionPage', 'create', array_merge([
      'receipt_from_name' => 'Mickey Mouse',
      'receipt_from_email' => 'mickey@mouse.com',
      'title' => "Test Contribution Page",
      'financial_type_id' => 1,
      'currency' => 'CAD',
      'is_monetary' => TRUE,
      'is_email_receipt' => TRUE,
    ], $params));
    return $contributionPage;
  }

  /**
   * function to test card_type and pan truncation.
   *
   * @throws \CRM_Core_Exception
   */
  public function testCardTypeAndPanTruncation() {
    $creditCardTypeIDs = array_flip(CRM_Financial_DAO_FinancialTrxn::buildOptions('card_type_id'));
    $contactId = $this->individualCreate();
    $params = [
      'contact_id' => $contactId,
      'receive_date' => '2016-01-20',
      'total_amount' => 100,
      'financial_type_id' => 1,
      'payment_instrument' => 'Credit Card',
      'card_type_id' => $creditCardTypeIDs['Visa'],
      'pan_truncation' => 4567,
    ];
    $contribution = $this->callAPISuccess('contribution', 'create', $params);
    $lastFinancialTrxnId = CRM_Core_BAO_FinancialTrxn::getFinancialTrxnId($contribution['id'], 'DESC');
    $financialTrxn = $this->callAPISuccessGetSingle(
      'FinancialTrxn',
      [
        'id' => $lastFinancialTrxnId['financialTrxnId'],
        'return' => ['card_type_id', 'pan_truncation'],
      ]
    );
    $this->assertEquals(CRM_Utils_Array::value('card_type_id', $financialTrxn), $creditCardTypeIDs['Visa']);
    $this->assertEquals(CRM_Utils_Array::value('pan_truncation', $financialTrxn), 4567);
    $params = [
      'id' => $contribution['id'],
      'pan_truncation' => 2345,
      'card_type_id' => $creditCardTypeIDs['Amex'],
    ];
    $this->callAPISuccess('contribution', 'create', $params);
    $financialTrxn = $this->callAPISuccessGetSingle(
      'FinancialTrxn',
      [
        'id' => $lastFinancialTrxnId['financialTrxnId'],
        'return' => ['card_type_id', 'pan_truncation'],
      ]
    );
    $this->assertEquals(CRM_Utils_Array::value('card_type_id', $financialTrxn), $creditCardTypeIDs['Amex']);
    $this->assertEquals(CRM_Utils_Array::value('pan_truncation', $financialTrxn), 2345);
  }

  /**
   * Test repeat contribution uses non default currency
   *
   * @see https://issues.civicrm.org/jira/projects/CRM/issues/CRM-20678
   * @throws \CRM_Core_Exception
   */
  public function testRepeatTransactionWithDifferenceCurrency() {
    $originalContribution = $this->setUpRepeatTransaction(['currency' => 'AUD'], 'single', ['currency' => 'AUD']);
    $contribution = $this->callAPISuccess('Contribution', 'repeattransaction', [
      'original_contribution_id' => $originalContribution['id'],
      'contribution_status_id' => 'Completed',
      'trxn_id' => 3456,
    ]);
    $this->assertEquals('AUD', $contribution['values'][$contribution['id']]['currency']);
  }

  /**
   * Get the financial items for the contribution.
   *
   * @param int $contributionID
   *
   * @return array
   *   Array of associated financial items.
   */
  protected function getFinancialTransactionsForContribution($contributionID) {
    $trxnParams = [
      'entity_id' => $contributionID,
      'entity_table' => 'civicrm_contribution',
    ];
    // @todo the following function has naming errors & has a weird signature & appears to
    // only be called from test classes. Move into test suite & maybe just use api
    // from this function.
    return array_merge(CRM_Financial_BAO_FinancialItem::retrieveEntityFinancialTrxn($trxnParams));
  }

  /**
   * Test getunique api call for Contribution entity
   */
  public function testContributionGetUnique() {
    $result = $this->callAPIAndDocument($this->entity, 'getunique', [], __FUNCTION__, __FILE__);
    $this->assertEquals(2, $result['count']);
    $this->assertEquals(['trxn_id'], $result['values']['UI_contrib_trxn_id']);
    $this->assertEquals(['invoice_id'], $result['values']['UI_contrib_invoice_id']);
  }

  /**
   * Test Repeat Transaction Contribution with Tax amount.
   *
   * https://lab.civicrm.org/dev/core/issues/806
   *
   * @throws \CRM_Core_Exception
   */
  public function testRepeatContributionWithTaxAmount(): void {
    $this->enableTaxAndInvoicing();
    $financialType = $this->callAPISuccess('financial_type', 'create', [
      'name' => 'Test taxable financial Type',
      'is_reserved' => 0,
      'is_active' => 1,
    ]);
    $this->addTaxAccountToFinancialType($financialType['id']);
    $contribution = $this->setUpRepeatTransaction(
      [],
      'single',
      [
        'financial_type_id' => $financialType['id'],
      ]
    );
    $this->callAPISuccess('contribution', 'repeattransaction', [
      'original_contribution_id' => $contribution['id'],
      'contribution_status_id' => 'Completed',
      'trxn_id' => 'test',
    ]);
    $payments = $this->callAPISuccess('Contribution', 'get', ['sequential' => 1, 'return' => ['total_amount', 'tax_amount']])['values'];
    //Assert if first payment and repeated payment has the same contribution amount.
    $this->assertEquals($payments[0]['total_amount'], $payments[1]['total_amount']);
    $this->callAPISuccessGetCount('Contribution', [], 2);

    //Assert line item records.
    $lineItems = $this->callAPISuccess('LineItem', 'get', ['sequential' => 1])['values'];
    foreach ($lineItems as $lineItem) {
      $taxExclusiveAmount = $payments[0]['total_amount'] - $payments[0]['tax_amount'];
      $this->assertEquals($lineItem['unit_price'], $taxExclusiveAmount);
      $this->assertEquals($lineItem['line_total'], $taxExclusiveAmount);
    }
    $this->callAPISuccessGetCount('Contribution', [], 2);
  }

  public function testGetCurrencyOptions() {
    $result = $this->callAPISuccess('Contribution', 'getoptions', [
      'field' => 'currency',
    ]);
    $this->assertEquals('US Dollar', $result['values']['USD']);
    $this->assertNotContains('$', $result['values']);
    $result = $this->callAPISuccess('Contribution', 'getoptions', [
      'field' => 'currency',
      'context' => "abbreviate",
    ]);
    $this->assertEquals('$', $result['values']['USD']);
    $this->assertNotContains('US Dollar', $result['values']);
  }

  /**
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testSetCustomDataInCreateAndHook() {
    $this->createCustomGroupWithFieldOfType([], 'int');
    $this->ids['CustomField']['text'] = (int) $this->createTextCustomField(['custom_group_id' => $this->ids['CustomGroup']['Custom Group']])['id'];
    $this->hookClass->setHook('civicrm_post', [
      $this,
      'civicrmPostContributionCustom',
    ]);
    $params = $this->_params;
    $params['custom_' . $this->ids['CustomField']['text']] = 'Some Text';
    $contribution = $this->callAPISuccess('Contribution', 'create', $params);
    $getContribution = $this->callAPISuccess('Contribution', 'get', [
      'id' => $contribution['id'],
      'return' => ['id', 'custom_' . $this->ids['CustomField']['text'], 'custom_' . $this->ids['CustomField']['int']],
    ]);
    $this->assertEquals(5, $getContribution['values'][$contribution['id']][$this->getCustomFieldName('int')]);
    $this->assertEquals('Some Text', $getContribution['values'][$contribution['id']]['custom_' . $this->ids['CustomField']['text']]);
    $this->callAPISuccess('CustomField', 'delete', ['id' => $this->ids['CustomField']['text']]);
    $this->callAPISuccess('CustomField', 'delete', ['id' => $this->ids['CustomField']['int']]);
    $this->callAPISuccess('CustomGroup', 'delete', ['id' => $this->ids['CustomGroup']['Custom Group']]);
  }

  /**
   * Implement post hook.
   *
   * @param string $op
   * @param string $objectName
   * @param int|null $objectId
   *
   * @throws \CRM_Core_Exception
   */
  public function civicrmPostContributionCustom(string $op, string $objectName, ?int $objectId): void {
    if ($objectName === 'Contribution' && $op === 'create') {
      $this->callAPISuccess('Contribution', 'create', [
        'id' => $objectId,
        'custom_' . $this->ids['CustomField']['int'] => 5,
      ]);
    }
  }

  /**
   * Test that passing in label for an option value linked to a custom field
   * works
   *
   * @see dev/core#1816
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   */
  public function testCustomValueOptionLabelTest(): void {
    $this->createCustomGroupWithFieldOfType([], 'radio');
    $params = $this->_params;
    $params['custom_' . $this->ids['CustomField']['radio']] = 'Red Testing';
    $this->callAPISuccess('Contribution', 'Create', $params);
  }

  /**
   * Test repeatTransaction with installments and next_sched_contribution_date
   *
   * @dataProvider getRepeatTransactionNextSchedData
   *
   * @param array $dataSet
   *
   * @throws \CRM_Core_Exception
   */
  public function testRepeatTransactionUpdateNextSchedContributionDate(array $dataSet): void {
    $paymentProcessorID = $this->paymentProcessorCreate();
    // Create the contribution before the recur so it doesn't trigger the update of next_sched_contribution_date
    $contribution = $this->callAPISuccess('contribution', 'create', array_merge(
        $this->_params,
        [
          'contribution_status_id' => 'Completed',
          'receive_date' => $dataSet['repeat'][0]['receive_date'],
        ])
    );
    $contributionRecur = $this->callAPISuccess('contribution_recur', 'create', array_merge([
      'contact_id' => $this->_individualId,
      'frequency_interval' => '1',
      'amount' => '500',
      'contribution_status_id' => 'Pending',
      'start_date' => '2012-01-01 00:00:00',
      'currency' => 'USD',
      'frequency_unit' => 'month',
      'payment_processor_id' => $paymentProcessorID,
    ], $dataSet['recur']));
    // Link the existing contribution to the recur *after* creating the recur.
    // If we just created the contribution now the next_sched_contribution_date would be automatically set
    //   and we want to test the case when it is empty.
    $this->callAPISuccess('contribution', 'create', [
      'id' => $contribution['id'],
      'contribution_recur_id' => $contributionRecur['id'],
    ]);

    $contributionRecur = $this->callAPISuccessGetSingle('ContributionRecur', [
      'id' => $contributionRecur['id'],
      'return' => ['next_sched_contribution_date', 'contribution_status_id'],
    ]);
    // Check that next_sched_contribution_date is empty
    $this->assertEquals('', $contributionRecur['next_sched_contribution_date'] ?? '');

    $this->callAPISuccess('Contribution', 'repeattransaction', [
      'contribution_status_id' => 'Completed',
      'contribution_recur_id' => $contributionRecur['id'],
      'receive_date' => $dataSet['repeat'][0]['receive_date'],
    ]);
    $contributionRecur = $this->callAPISuccessGetSingle('ContributionRecur', [
      'id' => $contributionRecur['id'],
      'return' => ['next_sched_contribution_date', 'contribution_status_id'],
    ]);
    // Check that recur has status "In Progress"
    $this->assertEquals(
      (string) CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_ContributionRecur', 'contribution_status_id', $dataSet['repeat'][0]['expectedRecurStatus']),
      $contributionRecur['contribution_status_id']
    );
    // Check that next_sched_contribution_date has been set to 1 period after the contribution receive date (ie. 1 month)
    $this->assertEquals($dataSet['repeat'][0]['expectedNextSched'], $contributionRecur['next_sched_contribution_date']);

    // Now call Contribution.repeattransaction again and check that the next_sched_contribution_date has moved forward by 1 period again
    $this->callAPISuccess('Contribution', 'repeattransaction', [
      'contribution_status_id' => 'Completed',
      'contribution_recur_id' => $contributionRecur['id'],
      'receive_date' => $dataSet['repeat'][1]['receive_date'],
    ]);
    $contributionRecur = $this->callAPISuccessGetSingle('ContributionRecur', [
      'id' => $contributionRecur['id'],
      'return' => ['next_sched_contribution_date', 'contribution_status_id'],
    ]);
    // Check that recur has status "In Progress" or "Completed" depending on whether number of installments has been reached
    $this->assertEquals(
      (string) CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_ContributionRecur', 'contribution_status_id', $dataSet['repeat'][1]['expectedRecurStatus']),
      $contributionRecur['contribution_status_id']
    );
    // Check that next_sched_contribution_date has been set to 1 period after the contribution receive date (ie. 1 month)
    $this->assertEquals($dataSet['repeat'][1]['expectedNextSched'], $contributionRecur['next_sched_contribution_date'] ?? '');
  }

  /**
   * Get dates for testing.
   *
   * @return array
   */
  public function getRepeatTransactionNextSchedData(): array {
    // Both these tests handle/test the case that next_sched_contribution_date is empty when Contribution.repeattransaction
    //   is called for the first time. Historically setting it was inconsistent but on new updates it should always be set.
    /*
     * This tests that calling Contribution.repeattransaction with installments does the following:
     * - For the first call to repeattransaction the recur status is In Progress and next_sched_contribution_date is updated
     *   to match next expected receive_date.
     * - Once the 3rd contribution is created contributionRecur status = completed and next_sched_contribution_date = ''.
     */
    $result['receive_date_includes_time_with_installments']['2012-01-01-1-month'] = [
      'recur' => [
        'start_date' => '2012-01-01',
        'frequency_interval' => 1,
        'installments' => '3',
        'frequency_unit' => 'month',
      ],
      'repeat' => [
        [
          'receive_date' => '2012-02-29 16:00:00',
          'expectedNextSched' => '2012-03-29 00:00:00',
          'expectedRecurStatus' => 'In Progress',
        ],
        [
          'receive_date' => '2012-03-29 16:00:00',
          'expectedNextSched' => '',
          'expectedRecurStatus' => 'Completed',
        ],
      ],
    ];
    /*
     * This tests that calling Contribution.repeattransaction with no installments does the following:
     * - For the each call to repeattransaction the recur status is In Progress and next_sched_contribution_date is updated
     *   to match next expected receive_date.
     */
    $result['receive_date_includes_time_no_installments']['2012-01-01-1-month'] = [
      'recur' => [
        'start_date' => '2012-01-01',
        'frequency_interval' => 1,
        'frequency_unit' => 'month',
      ],
      'repeat' => [
        [
          'receive_date' => '2012-02-29 16:00:00',
          'expectedNextSched' => '2012-03-29 00:00:00',
          'expectedRecurStatus' => 'In Progress',
        ],
        [
          'receive_date' => '2012-03-29 16:00:00',
          'expectedNextSched' => '2012-04-29 00:00:00',
          'expectedRecurStatus' => 'In Progress',
        ],
      ],
    ];
    return $result;
  }

  /**
   * Make sure that recording a payment doesn't alter the receive_date of a
   * pending contribution.
   */
  public function testPaymentDontChangeReceiveDate(): void {
    $params = [
      'contact_id' => $this->_individualId,
      'total_amount' => 100,
      'receive_date' => '2020-02-02',
      'contribution_status_id' => 'Pending',
    ];
    $contributionID = $this->contributionCreate($params);
    $paymentParams = [
      'contribution_id' => $contributionID,
      'total_amount' => 100,
      'trxn_date' => '2020-03-04',
    ];
    $this->callAPISuccess('payment', 'create', $paymentParams);

    //check if contribution status is set to "Completed".
    $contribution = $this->callAPISuccess('Contribution', 'getSingle', [
      'id' => $contributionID,
    ]);
    $this->assertEquals('2020-02-02 00:00:00', $contribution['receive_date']);
  }

  /**
   * Make sure that recording a payment with Different Payment Instrument update main contribution record payment
   * instrument too. If multiple Payment Recorded, last payment record payment (when No more due) instrument set to main
   * payment
   */
  public function testPaymentVerifyPaymentInstrumentChange() {
    // Create Pending contribution with pay later mode, with payment instrument Check
    $params = [
      'contact_id' => $this->_individualId,
      'total_amount' => 100,
      'receive_date' => '2020-02-02',
      'contribution_status_id' => 'Pending',
      'is_pay_later' => 1,
      'payment_instrument_id' => 'Check',
    ];
    $contributionID = $this->contributionCreate($params);

    // Record the the Payment with instrument other than Check, e.g EFT
    $paymentParams = [
      'contribution_id' => $contributionID,
      'total_amount' => 50,
      'trxn_date' => '2020-03-04',
      'payment_instrument_id' => 'EFT',
    ];
    $this->callAPISuccess('payment', 'create', $paymentParams);

    $contribution = $this->callAPISuccess('Contribution', 'getSingle', [
      'id' => $contributionID,
    ]);
    // payment status should be 'Partially paid'
    $this->assertEquals('Partially paid', $contribution['contribution_status']);

    // Record the the Payment with instrument other than Check, e.g Cash (pay all remaining amount)
    $paymentParams = [
      'contribution_id' => $contributionID,
      'total_amount' => 50,
      'trxn_date' => '2020-03-04',
      'payment_instrument_id' => 'Cash',
    ];
    $this->callAPISuccess('payment', 'create', $paymentParams);

    //check if contribution Payment Instrument (Payment Method) is is set to "Cash".
    $contribution = $this->callAPISuccess('Contribution', 'getSingle', [
      'id' => $contributionID,
    ]);
    $this->assertEquals('Cash', $contribution['payment_instrument']);
    $this->assertEquals('Completed', $contribution['contribution_status']);
  }

  /**
   * Test the "clean money" functionality.
   */
  public function testCleanMoney() {
    $params = [
      'contact_id' => $this->_individualId,
      'financial_type_id' => 1,
      'total_amount' => '$100',
      'fee_amount' => '$20',
      'net_amount' => '$80',
      'non_deductible_amount' => '$80',
      'sequential' => 1,
    ];
    $id = $this->callAPISuccess('Contribution', 'create', $params)['id'];
    // Reading the return values of the API isn't reliable here; get the data from the db.
    $contribution = $this->callAPISuccess('Contribution', 'getsingle', ['id' => $id]);
    $this->assertEquals('100.00', $contribution['total_amount']);
    $this->assertEquals('20.00', $contribution['fee_amount']);
    $this->assertEquals('80.00', $contribution['net_amount']);
    $this->assertEquals('80.00', $contribution['non_deductible_amount']);
  }

  /**
   * Create a price set with a quick config price set.
   *
   * The params to use this look like
   *
   * ['price_' . $this->ids['PriceField']['basic'] => $this->ids['PriceFieldValue']['basic']]
   *
   * @param array $contributionPageParams
   *
   * @return int
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  private function createQuickConfigContributionPage(array $contributionPageParams = []): int {
    $contributionPageID = $this->callAPISuccess('ContributionPage', 'create', array_merge([
      'receipt_from_name' => 'Mickey Mouse',
      'receipt_from_email' => 'mickey@mouse.com',
      'title' => 'Test Contribution Page',
      'financial_type_id' => 'Member Dues',
      'currency' => 'CAD',
      'is_pay_later' => 1,
      'is_quick_config' => TRUE,
      'pay_later_text' => 'I will send payment by check',
      'pay_later_receipt' => 'This is a pay later receipt',
      'is_allow_other_amount' => 1,
      'min_amount' => 10.00,
      'max_amount' => 10000.00,
      'goal_amount' => 100000.00,
      'is_email_receipt' => 1,
      'is_active' => 1,
      'amount_block_is_active' => 1,
      'is_billing_required' => 0,
    ], $contributionPageParams))['id'];

    $priceSetID = PriceSet::create()->setValues([
      'name' => 'quick config set',
      'title' => 'basic price set',
      'is_quick_config' => TRUE,
      'extends' => 2,
    ])->execute()->first()['id'];

    $priceFieldID = PriceField::create()->setValues([
      'price_set_id' => $priceSetID,
      'name' => 'quick config field name',
      'label' => 'quick config field name',
      'html_type' => 'Radio',
    ])->execute()->first()['id'];
    $this->ids['PriceSet']['basic'] = $priceSetID;
    $this->ids['PriceField']['basic'] = $priceFieldID;
    $this->ids['PriceFieldValue']['basic'] = PriceFieldValue::create()->setValues([
      'price_field_id' => $priceFieldID,
      'name' => 'quick config price field',
      'label' => 'quick config price field',
      'amount' => 100,
      'financial_type_id:name' => 'Member Dues',
    ])->execute()->first()['id'];
    CRM_Price_BAO_PriceSet::addTo('civicrm_contribution_page', $contributionPageID, $priceSetID);
    return $contributionPageID;
  }

  /**
   * Get the created contribution ID.
   *
   * @param string $key
   *
   * @return int
   */
  protected function getContributionID(string $key = 'first'): int {
    return (int) $this->ids['contribution'][$key];
  }

  /**
   * Get the created contribution ID.
   *
   * @return int
   */
  protected function getMembershipID(): int {
    return (int) $this->_ids['membership'];
  }

  /**
   * Create a paid membership for renewal tests.
   */
  protected function createSubsequentPendingMembership(): void {
    $this->setUpPendingContribution($this->_ids['price_field_value'][1], 'second', [], [], [
      'id' => $this->getMembershipID(),
    ]);
  }

  /**
   * Create a paid membership for renewal tests.
   */
  protected function createInitialPaidMembership(): void {
    $this->setUpPendingContribution($this->_ids['price_field_value'][1]);
    $this->callAPISuccess('Payment', 'create', [
      'contribution_id' => $this->getContributionID(),
      'total_amount' => 20,
    ]);
  }

}
