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
use Civi\Api4\Activity;
use Civi\Api4\Contribution;
use Civi\Api4\CustomField;
use Civi\Api4\EntityFinancialTrxn;
use Civi\Api4\FinancialTrxn;
use Civi\Api4\PledgePayment;
use Civi\Api4\Product;

/**
 * Class CRM_Contribute_BAO_ContributionTest
 * @group headless
 */
class CRM_Contribute_BAO_ContributionTest extends CiviUnitTestCase {

  use CRMTraits_Financial_FinancialACLTrait;
  use CRMTraits_Financial_PriceSetTrait;

  /**
   * Clean up after tests.
   */
  public function tearDown(): void {
    $this->disableFinancialACLs();
    $this->quickCleanUpFinancialEntities();
    parent::tearDown();
  }

  /**
   * Test create method (create and update modes).
   */
  public function testCreate(): void {
    $contactID = $this->individualCreate();

    $params = [
      'contact_id' => $contactID,
      'currency' => 'USD',
      'financial_type_id' => 1,
      'contribution_status_id' => 1,
      'payment_instrument_id' => 1,
      'source' => 'STUDENT',
      'receive_date' => '20080522000000',
      'receipt_date' => '20080522000000',
      'non_deductible_amount' => 0.00,
      'total_amount' => 200.00,
      'fee_amount' => 5,
      'net_amount' => 195,
      'trxn_id' => '22444444',
      'invoice_id' => '86ed39c9e9ee6ef6031621ce07eb81',
      'thankyou_date' => '20080522',
      'sequential' => TRUE,
    ];

    $contribution = $this->callAPISuccess('Contribution', 'create', $params)['values'][0];

    $this->assertEquals($params['trxn_id'], $contribution['trxn_id'], 'Check for transaction id creation.');
    $this->assertEquals($contactID, $contribution['contact_id'], 'Check for contact id  creation.');

    // Update contribution amount.
    $params['id'] = $contribution['id'];
    $params['fee_amount'] = 10;
    $params['net_amount'] = 190;

    $contribution = $this->callAPISuccess('Contribution', 'create', $params)['values'][0];

    $this->assertEquals($params['trxn_id'], $contribution['trxn_id'], 'Check for transaction id .');
    $this->assertEquals($params['net_amount'], $contribution['net_amount'], 'Check for Amount update.');
  }

  /**
   * Create() method with custom data.
   *
   * @throws \CRM_Core_Exception
   * @throws \Exception
   */
  public function testCreateWithCustomData(): void {
    $this->individualCreate();

    //create custom data
    $customGroup = $this->customGroupCreate(['extends' => 'Contribution', 'name', 'group']);
    $customGroupID = $customGroup['id'];
    $customGroup = $customGroup['values'][$customGroupID];
    $customFieldID = CustomField::create()->setValues([
      'label' => 'test Field',
      'data_type' => 'String',
      'html_type' => 'Text',
      'is_active' => 1,
      'column_name' => 'field_column',
      'custom_group_id' => $customGroupID,
    ])->execute()->first()['id'];

    $params = [
      'contact_id' => $this->ids['Contact']['individual_0'],
      'currency' => 'USD',
      'financial_type_id' => 1,
      'contribution_status_id' => 1,
      'payment_instrument_id' => 1,
      'source' => 'STUDENT',
      'receive_date' => '20080522000000',
      'receipt_date' => '20080522000000',
      'id' => NULL,
      'non_deductible_amount' => 0.00,
      'total_amount' => 200.00,
      'fee_amount' => 5,
      'net_amount' => 195,
      'trxn_id' => '22w322323',
      'invoice_id' => '22ed39c9e9ee6ef6031621ce0a6da70',
      'thankyou_date' => '20080522',
      'skipCleanMoney' => TRUE,
    ];

    $params['custom'] = [
      $customFieldID => [
        -1 => [
          'value' => 'Test custom value',
          'type' => 'String',
          'custom_field_id' => $customFieldID,
          'custom_group_id' => $customGroupID,
          'table_name' => $customGroup['table_name'],
          'column_name' => 'field_column',
          'file_id' => NULL,
        ],
      ],
    ];

    $contribution = CRM_Contribute_BAO_Contribution::create($params);

    // Check that the custom field value is saved
    $customValueParams = [
      'entityID' => $contribution->id,
      'custom_' . $customFieldID => 1,
    ];
    $values = CRM_Core_BAO_CustomValueTable::getValues($customValueParams);
    $this->assertEquals('Test custom value', $values['custom_' . $customFieldID], 'Check the custom field value');

    $this->assertEquals($params['trxn_id'], $contribution->trxn_id, 'Check for transaction id creation.');
    $this->assertEquals($this->ids['Contact']['individual_0'], $contribution->contact_id);
  }

  /**
   * CRM-21026 Test ContributionCount after contribution created with disabled FT
   */
  public function testContributionCountDisabledFinancialType(): void {
    $contactId = $this->individualCreate();
    $financialType = [
      'name' => 'grass_variety_1',
      'is_reserved' => 0,
      'is_active' => 0,
    ];
    $finType = $this->callAPISuccess('financial_type', 'create', $financialType);
    $params = [
      'contact_id' => $contactId,
      'currency' => 'USD',
      'financial_type_id' => $finType['id'],
      'contribution_status_id' => 1,
      'payment_instrument_id' => 1,
      'source' => 'STUDENT',
      'receive_date' => '20080522000000',
      'receipt_date' => '20080522000000',
      'id' => NULL,
      'non_deductible_amount' => 0.00,
      'total_amount' => 200.00,
      'fee_amount' => 5,
      'net_amount' => 195,
      'trxn_id' => '22322323',
      'invoice_id' => '22ed39c9e9ee6ef6031621ce06da70',
      'thankyou_date' => '20080522',
    ];
    $this->callAPISuccess('Contribution', 'create', $params);
    $this->callAPISuccess('financial_type', 'create', ['is_active' => 0, 'id' => $finType['id']]);
    $contributionCount = CRM_Contribute_BAO_Contribution::contributionCount($contactId);
    $this->assertEquals(1, $contributionCount);
  }

  /**
   * Test contributions are deleted with assets in v3 and v4 api.@\
   *
   * @dataProvider versionThreeAndFour
   *
   * @param int $version
   */
  public function testDeleteContribution(int $version): void {
    $this->_apiversion = $version;
    $contactId = $this->individualCreate();

    $params = [
      'contact_id' => $contactId,
      'currency' => 'USD',
      'financial_type_id' => 1,
      'contribution_status_id' => 1,
      'payment_instrument_id' => 1,
      'source' => 'STUDENT',
      'receive_date' => '20080522000000',
      'receipt_date' => '20080522000000',
      'id' => NULL,
      'non_deductible_amount' => 0.00,
      'total_amount' => 200.00,
      'fee_amount' => 5,
      'net_amount' => 195,
      'sequential' => TRUE,
    ];

    $contribution = $this->callAPISuccess('Contribution', 'create', $params)['values'][0];

    $this->callAPISuccess('Contribution', 'delete', ['id' => $contribution['id']]);
    $this->callAPISuccessGetCount('Contribution', [], 0);
    $this->callAPISuccessGetCount('LineItem', [], 0);
  }

  /**
   * Test that financial type data is not added to the annual query if acls not
   * enabled.
   *
   * @throws \Civi\Core\Exception\DBQueryException
   */
  public function testAnnualQueryWithFinancialACLsEnabled(): void {
    $this->enableFinancialACLs();
    $this->createLoggedInUserWithFinancialACL();
    $permittedFinancialType = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'financial_type_id', 'Donation');
    $sql = CRM_Contribute_BAO_Contribution::getAnnualQuery([1, 2, 3]);
    $this->assertStringContainsString('SUM(total_amount) as amount,', $sql);
    $this->assertStringContainsString('b.contact_id IN (1,2,3)', $sql);
    $this->assertStringContainsString('b.financial_type_id IN (' . $permittedFinancialType . ')', $sql);

    // Run it to make sure it's not bad sql.
    CRM_Core_DAO::executeQuery($sql);
  }

  /**
   * Test the annual query returns a correct result when multiple line items
   * are present.
   *
   * @throws \Civi\Core\Exception\DBQueryException
   */
  public function testAnnualWithMultipleLineItems(): void {
    $contactID = $this->createLoggedInUserWithFinancialACL();
    $this->createContributionWithTwoLineItemsAgainstPriceSet([
      'contact_id' => $contactID,
    ]
    );
    $this->enableFinancialACLs();
    $sql = CRM_Contribute_BAO_Contribution::getAnnualQuery([$contactID]);
    $result = CRM_Core_DAO::executeQuery($sql);
    $result->fetch();
    $this->assertEquals(0, $result->N);

    // It didn't find any rows cos it is restricted to only find contributions where all lines are visible.
    CRM_Core_DAO::executeQuery('UPDATE civicrm_line_item SET financial_type_id = ' . CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'financial_type_id', 'Donation'));
    $result = CRM_Core_DAO::executeQuery($sql);
    $result->fetch();
    $this->assertEquals(300, $result->amount);
    $this->assertEquals(1, $result->count);
    $this->disableFinancialACLs();
  }

  /**
   * Test that financial type data is not added to the annual query if acls not
   * enabled.
   *
   * @throws \Civi\Core\Exception\DBQueryException
   */
  public function testAnnualQueryWithFinancialACLsDisabled(): void {
    $sql = CRM_Contribute_BAO_Contribution::getAnnualQuery([1, 2, 3]);
    $this->assertStringContainsString('SUM(total_amount) as amount,', $sql);
    $this->assertStringContainsString('b.contact_id IN (1,2,3)', $sql);
    $this->assertStringNotContainsString('b.financial_type_id', $sql);
    // Run it to make sure it's not bad sql.
    CRM_Core_DAO::executeQuery($sql);
  }

  /**
   * Test that financial type data is not added to the annual query if acls not
   * enabled.
   *
   * @throws \Civi\Core\Exception\DBQueryException
   */
  public function testAnnualQueryWithFinancialHook(): void {
    $this->hookClass->setHook('civicrm_selectWhereClause', [$this, 'aclNoZero']);
    $sql = CRM_Contribute_BAO_Contribution::getAnnualQuery([1, 2, 3]);
    $this->assertStringContainsString('SUM(total_amount) as amount,', $sql);
    $this->assertStringContainsString('b.contact_id IN (1,2,3)', $sql);
    $this->assertStringContainsString('WHERE b.id NOT IN (0)', $sql);
    $this->assertStringNotContainsString('b.financial_type_id', $sql);
    CRM_Core_DAO::executeQuery($sql);
  }

  /**
   * Add ACL denying values LIKE '0'.
   *
   * @param string $entity
   * @param array $clauses
   */
  public function aclNoZero(string $entity, array &$clauses): void {
    if ($entity !== 'Contribution') {
      return;
    }
    $clauses['id'] = 'NOT IN (0)';
  }

  /**
   * Display sort name during.
   * Update multiple contributions
   * sortName();
   */
  public function testSortName(): void {
    $this->individualCreate([
      'first_name' => 'Shane',
      'last_name' => 'Watson',
    ]);

    $param = [
      'contact_id' => $this->ids['Contact']['individual_0'],
      'currency' => 'USD',
      'financial_type_id' => 1,
      'contribution_status_id' => 1,
      'payment_instrument_id' => 1,
      'source' => 'STUDENT',
      'receive_date' => '20080522000000',
      'receipt_date' => '20080522000000',
      'id' => NULL,
      'non_deductible_amount' => 0.00,
      'total_amount' => 300.00,
      'fee_amount' => 5,
      'net_amount' => 295,
      'trxn_id' => '22323',
      'invoice_id' => '22ed39c9e9ee621ce06da70',
      'thankyou_date' => '20080522',
      'sequential' => TRUE,
    ];

    $contributionID = $this->callAPISuccess('Contribution', 'create', $param)['id'];
    // Display sort name during Update multiple contributions.
    $this->assertEquals('Watson, Shane II', CRM_Contribute_BAO_Contribution::sortName($contributionID));
  }

  /**
   * Add premium during online Contribution.
   *
   * AddPremium();
   *
   * @throws \CRM_Core_Exception
   */
  public function testAddPremium(): void {
    $contactId = $this->individualCreate();

    $params = [
      'name' => 'TEST Premium',
      'sku' => 111,
      'imageOption' => 'noImage',
      'MAX_FILE_SIZE' => 2097152,
      'price' => 100.00,
      'cost' => 90.00,
      'min_contribution' => 100,
      'is_active' => 1,
    ];
    $premium = CRM_Contribute_BAO_Product::create($params);

    $this->assertEquals('TEST Premium', $premium->name, 'Check for premium  name.');

    $contributionParams = [
      'contact_id' => $contactId,
      'currency' => 'USD',
      'financial_type_id' => 1,
      'contribution_status_id' => 1,
      'payment_instrument_id' => 1,
      'source' => 'STUDENT',
      'receive_date' => '20080522000000',
      'receipt_date' => '20080522000000',
      'id' => NULL,
      'non_deductible_amount' => 0.00,
      'total_amount' => 300.00,
      'fee_amount' => 5,
      'net_amount' => 295,
      'trxn_id' => '33er434',
      'invoice_id' => '98ed34f7u9hh672ce0e8fb92',
      'thankyou_date' => '20080522',
      'sequential' => TRUE,
    ];
    $contribution = $this->callAPISuccess('Contribution', 'create', $contributionParams)['values'][0];

    //parameter for adding premium to contribution
    $data = [
      'product_id' => $premium->id,
      'contribution_id' => $contribution['id'],
      'product_option' => NULL,
      'quantity' => 1,
    ];
    $contributionProduct = CRM_Contribute_BAO_Contribution::addPremium($data);
    $this->assertEquals($contributionProduct->product_id, $premium->id, 'Check for Product id .');

    // Delete Product.
    Product::delete()->addWhere('id', '=', $premium->id)->execute();
    $this->assertDBNull('CRM_Contribute_DAO_Product', $premium->name,
      'id', 'name', 'Database check for deleted Product.'
    );
  }

  /**
   * Check duplicate contribution id.
   */
  public function testCheckDuplicateIDs(): void {
    $param = [
      'contact_id' => $this->individualCreate(),
      'currency' => 'USD',
      'financial_type_id' => 1,
      'contribution_status_id' => 1,
      'payment_instrument_id' => 1,
      'source' => 'STUDENT',
      'receive_date' => '20080522000000',
      'receipt_date' => '20080522000000',
      'id' => NULL,
      'non_deductible_amount' => 0.00,
      'total_amount' => 300.00,
      'fee_amount' => 5,
      'net_amount' => 295,
      'trxn_id' => '76er835',
      'invoice_id' => '93ed39a9e9hd621bs0f3da82',
      'thankyou_date' => '20080522',
      'sequential' => TRUE,
    ];

    $contribution = $this->callAPISuccess('Contribution', 'create', $param)['values'][0];

    $data = [
      'id' => $contribution['id'],
      'trxn_id' => $contribution['trxn_id'],
      'invoice_id' => $contribution['invoice_id'],
    ];
    $contributionID = CRM_Contribute_BAO_Contribution::checkDuplicateIds($data);
    $this->assertEquals($contributionID, $contribution['id'], 'Check for duplicate transaction id .');
  }

  /**
   * Create() method (create and update modes).
   */
  public function testIsPaymentFlag(): void {
    $contributionID = $this->callAPISuccess('Contribution', 'create', [
      'contact_id' => $this->individualCreate(),
      'currency' => 'USD',
      'financial_type_id' => 1,
      'contribution_status_id' => 1,
      'payment_instrument_id' => 1,
      'source' => 'STUDENT',
      'receive_date' => '20080522000000',
      'receipt_date' => '20080522000000',
      'non_deductible_amount' => 0.00,
      'total_amount' => 200.00,
      'fee_amount' => 5,
      'net_amount' => 195,
      'trxn_id' => '12345',
      'invoice_id' => '86ed39c9e9ee6ef6541621ce0fe7eb81',
      'thankyou_date' => '20080522',
      'sequential' => TRUE,
    ])['id'];

    $this->assertFinancialTransactionCount(1, 12345, 1);
    // Update contribution amount.
    $params['id'] = $contributionID;
    $params['total_amount'] = 150;
    $this->callAPISuccess('Contribution', 'create', $params);

    $this->assertFinancialTransactionCount(2, 12345, TRUE);
    $this->assertFinancialTransactionCount(1, 12345, FALSE);
  }

  /**
   * Create() method (create and update modes).
   */
  public function testIsPaymentFlagForPending(): void {
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
      'trxn_id' => '2345',
      'invoice_id' => '86ed39c9e9yy6ef6541621ce0e7eb81',
      'thankyou_date' => '20080522',
      'sequential' => TRUE,
    ];

    $contribution = $this->callAPISuccess('Contribution', 'create', $params)['values'][0];

    $this->assertEquals($params['trxn_id'], $contribution['trxn_id'], 'Check for transaction id creation.');
    $this->assertEquals($contactId, $contribution['contact_id'], 'Check for contact id  creation.');

    $this->assertFinancialTransactionCount(2, 2345, FALSE);
    $this->assertFinancialTransactionCount(0, 2345, TRUE);
    // Update contribution amount.
    $params['id'] = $contribution['id'];
    $params['contribution_status_id'] = 1;

    $contribution = $this->callAPISuccess('Contribution', 'create', $params)['values'][0];

    $this->assertEquals($params['contribution_status_id'], $contribution['contribution_status_id'], 'Check for status update.');
    $this->assertFinancialTransactionCount(2, 2345, FALSE);
    $this->assertFinancialTransactionCount(1, 2345, TRUE);
  }

  /**
   * checks db values for financial item
   *
   * @throws \Civi\Core\Exception\DBQueryException
   */
  public function checkItemValues($contribution): void {
    $query = "SELECT eft1.entity_id, ft.total_amount, eft1.amount FROM civicrm_financial_trxn ft INNER JOIN civicrm_entity_financial_trxn eft ON (eft.financial_trxn_id = ft.id AND eft.entity_table = 'civicrm_contribution')
INNER JOIN civicrm_entity_financial_trxn eft1 ON (eft1.financial_trxn_id = eft.financial_trxn_id AND eft1.entity_table = 'civicrm_financial_item')
WHERE eft.entity_id = %1 AND ft.to_financial_account_id <> %2";

    $queryParams[1] = [$contribution->id, 'Integer'];
    $queryParams[2] = [CRM_Financial_BAO_FinancialAccount::getFinancialAccountForFinancialTypeByRelationship(4, 'Accounts Receivable Account is'), 'Integer'];

    $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
    $amounts = [100.00, 50.00];
    while ($dao->fetch()) {
      $this->assertEquals(150.00, $dao->total_amount, 'Mismatch of total amount paid.');
      $this->assertEquals($dao->amount, array_pop($amounts), 'Mismatch of amount proportionally assigned to financial item');
    }
  }

  /**
   * assignProportionalLineItems() method (add and edit modes of participant)
   *
   * @throws \CRM_Core_Exception
   */
  public function testAssignProportionalLineItems(): void {
    // This test doesn't seem to manage financials properly, possibly by design
    $this->isValidateFinancialsOnPostAssert = FALSE;
    $contribution = $this->addParticipantWithContribution();
    // Delete existing financial_trxns. This is because we are testing a code flow we
    // want to deprecate & remove & the test relies on bad data asa starting point.
    // End goal is the Order.create->Payment.create flow.
    CRM_Core_DAO::executeQuery('DELETE FROM civicrm_entity_financial_trxn WHERE entity_table = "civicrm_financial_item"');
    $params = [
      'contribution_id' => $contribution->id,
      'total_amount' => 150.00,
    ];
    $trxn = new CRM_Financial_DAO_FinancialTrxn();
    $trxn->orderBy('id DESC');
    $trxn->find(TRUE);
    CRM_Contribute_BAO_Contribution::assignProportionalLineItems($params, $trxn->id, $contribution->total_amount);
    $this->checkItemValues($contribution);
  }

  /**
   * Add participant with contribution
   *
   * @return CRM_Contribute_BAO_Contribution
   *
   * @throws \CRM_Core_Exception
   */
  public function addParticipantWithContribution(): CRM_Contribute_BAO_Contribution {
    // Creating price set, price field.
    $this->individualCreate();
    $this->eventCreatePaid([]);
    $priceSetID = $this->getPriceSetID('PaidEvent');
    $paramsField = [
      'label' => 'Price Field',
      'name' => CRM_Utils_String::titleToVar('Price Field'),
      'html_type' => 'CheckBox',
      'option_label' => ['1' => 'Price Field 1', '2' => 'Price Field 2'],
      'option_value' => ['1' => 100, '2' => 200],
      'option_name' => ['1' => 'Price Field 1', '2' => 'Price Field 2'],
      'option_weight' => ['1' => 1, '2' => 2],
      'option_amount' => ['1' => 100, '2' => 200],
      'is_display_amounts' => 1,
      'weight' => 1,
      'options_per_line' => 1,
      'is_active' => ['1' => 1, '2' => 1],
      'price_set_id' => $priceSetID,
      'is_enter_qty' => 1,
      'financial_type_id' => CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialType', 'Event Fee', 'id', 'name'),
    ];
    $priceField = CRM_Price_BAO_PriceField::create($paramsField);
    $eventParams = [
      'id' => $this->getEventID(),
      'financial_type_id' => 4,
      'is_monetary' => 1,
    ];
    CRM_Event_BAO_Event::create($eventParams);
    CRM_Price_BAO_PriceSet::addTo('civicrm_event', $this->getEventID(), $priceSetID);

    $priceFields = $this->callAPISuccess('PriceFieldValue', 'get', ['price_field_id' => $priceField->id]);
    $participantParams = [
      'financial_type_id' => 4,
      'event_id' => $this->getEventID(),
      'role_id' => 1,
      'status_id' => 14,
      'fee_currency' => 'USD',
      'contact_id' => $this->ids['Contact']['individual_0'],
    ];
    $participant = CRM_Event_BAO_Participant::add($participantParams);
    $contributionParams = [
      'total_amount' => 300,
      'currency' => 'USD',
      'contact_id' => $this->ids['Contact']['individual_0'],
      'financial_type_id' => 4,
      'contribution_status_id' => 'Pending',
      'participant_id' => $participant->id,
      'sequential' => TRUE,
      'api.Payment.create' => ['total_amount' => 150],
    ];

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
    $contributionParams['line_item'] = $lineItems;
    $contribution = $this->callAPISuccess('Contribution', 'create', $contributionParams)['values'][0];

    $paymentParticipant = [
      'participant_id' => $participant->id,
      'contribution_id' => $contribution['id'],
    ];
    CRM_Event_BAO_ParticipantPayment::create($paymentParticipant);

    $contributionObject = new CRM_Contribute_BAO_Contribution();
    $contributionObject->id = $contribution['id'];
    $contributionObject->find(TRUE);

    return $contributionObject;
  }

  /**
   * Test activity amount updates activity subject.
   *
   * @throws \CRM_Core_Exception
   */
  public function testActivityCreate(): void {
    $params = [
      'contact_id' => $this->individualCreate(),
      'currency' => 'USD',
      'financial_type_id' => 1,
      'contribution_status_id' => 1,
      'payment_instrument_id' => 1,
      'source' => 'STUDENT',
      'receive_date' => '20080522000000',
      'receipt_date' => '20080522000000',
      'non_deductible_amount' => 0.00,
      'total_amount' => 100.00,
      'invoice_id' => '86ed39c9e9ee6ef6031621ce0e7eb81',
      'thankyou_date' => '20160519',
      'sequential' => 1,
    ];

    $contribution = $this->callAPISuccess('Contribution', 'create', $params)['values'][0];

    $this->assertEquals($params['total_amount'], $contribution['total_amount'], 'Check for total amount in contribution.');
    $activityWhere = [
      ['source_record_id', '=', $contribution['id']],
      ['activity_type_id:name', '=', 'Contribution'],
    ];
    $activity = Activity::get()->setWhere($activityWhere)->setSelect(['source_record_id', 'subject'])->execute()->first();

    $this->assertEquals($contribution['id'], $activity['source_record_id'], 'Check for activity associated with contribution.');
    $this->assertEquals('$ 100.00 - STUDENT', $activity['subject'], 'Check for total amount in activity.');

    $params['id'] = $contribution['id'];
    $params['total_amount'] = 200;
    $params['campaign_id'] = $this->campaignCreate();

    $contribution = $this->callAPISuccess('Contribution', 'create', $params)['values'][0];

    $this->assertEquals($params['total_amount'], $contribution['total_amount'], 'Check for total amount in contribution.');

    // Retrieve activity again.
    $activity = Activity::get()->setWhere($activityWhere)->setSelect(['source_record_id', 'subject', 'campaign_id'])->execute()->first();

    $this->assertEquals($contribution['id'], $activity['source_record_id'], 'Check for activity associated with contribution.');
    $this->assertEquals('$ 200.00 - STUDENT', $activity['subject'], 'Check for total amount in activity.');
    $this->assertEquals($params['campaign_id'], $activity['campaign_id']);
  }

  /**
   * Test allowUpdateRevenueRecognitionDate.
   */
  public function testAllowUpdateRevenueRecognitionDate(): void {
    $contactID = $this->individualCreate();
    $params = [
      'contact_id' => $contactID,
      'receive_date' => '2010-01-20',
      'total_amount' => 100,
      'financial_type_id' => 4,
      'contribution_status_id' => 'Pending',
    ];
    $order = $this->callAPISuccess('Order', 'create', $params);
    $allowUpdate = CRM_Contribute_BAO_Contribution::allowUpdateRevenueRecognitionDate($order['id']);
    $this->assertTrue($allowUpdate);

    $event = $this->eventCreatePaid();
    $params = [
      'contact_id' => $contactID,
      'receive_date' => '2010-01-20',
      'total_amount' => 300,
      'financial_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Price_BAO_PriceSet', 'financial_type_id', 'Event Fee'),
      'contribution_status_id' => 'Pending',
    ];
    $priceFields = $this->createPriceSet('event', $event['id']);
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
    $params['line_items'][] = [
      'line_item' => $lineItems,
      'params' => [
        'contact_id' => $contactID,
        'event_id' => $event['id'],
        'status_id' => 1,
        'role_id' => 1,
        'register_date' => '2007-07-21 00:00:00',
        'source' => 'Online Event Registration: API Testing',
      ],
    ];
    $order = $this->callAPISuccess('Order', 'create', $params);
    $allowUpdate = CRM_Contribute_BAO_Contribution::allowUpdateRevenueRecognitionDate($order['id']);
    $this->assertFalse($allowUpdate);

    $params = [
      'contact_id' => $contactID,
      'receive_date' => '2010-01-20',
      'total_amount' => 200,
      'financial_type_id' => $this->getFinancialTypeID('Member Dues'),
      'contribution_status_id' => 'Pending',
    ];
    $membershipType = $this->membershipTypeCreate();
    $priceFields = $this->createPriceSet('contribution_page', NULL, [], 'membership');
    $lineItems = [];
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
    $params['line_items'][] = [
      'line_item' => [array_pop($lineItems)],
      'params' => [
        'contact_id' => $contactID,
        'membership_type_id' => $membershipType,
        'join_date' => '2006-01-21',
        'start_date' => '2006-01-21',
        'end_date' => '2006-12-21',
        'source' => 'Payment',
        'is_override' => 1,
        'status_id' => 1,
      ],
    ];
    $order = $this->callAPISuccess('Order', 'create', $params);
    $allowUpdate = CRM_Contribute_BAO_Contribution::allowUpdateRevenueRecognitionDate($order['id']);
    $this->assertFalse($allowUpdate);
  }

  /**
   * Test recording of amount with comma separator.
   */
  public function testCommaSeparatorAmount(): void {
    $params = [
      'contact_id' => $this->individualCreate(),
      'currency' => 'USD',
      'financial_type_id' => 1,
      'contribution_status_id' => 'Pending',
      'payment_instrument_id' => 1,
      'receive_date' => '20080522000000',
      'receipt_date' => '20080522000000',
      'total_amount' => '20,000.00',
      'api.Payment.create' => ['total_amount' => '8,000.00'],
      'skipCleanMoney' => FALSE,
    ];

    $contribution = $this->callAPISuccess('Order', 'create', $params);
    $lastFinancialTrxnId = CRM_Core_BAO_FinancialTrxn::getFinancialTrxnId($contribution['id'], 'DESC');
    $financialTrxn = $this->callAPISuccessGetSingle(
      'FinancialTrxn',
      [
        'id' => $lastFinancialTrxnId['financialTrxnId'],
        'return' => ['total_amount'],
      ]
    );
    $this->assertEquals(8000, $financialTrxn['total_amount'], 'Invalid amount.');
  }

  /**
   * Test for function getSalesTaxFinancialAccounts().
   */
  public function testGetSalesTaxFinancialAccounts(): void {
    $this->enableTaxAndInvoicing();
    $financialType = $this->createFinancialType();
    $financialAccount = $this->addTaxAccountToFinancialType($financialType['id']);
    $expectedResult = [$financialAccount->financial_account_id => $financialAccount->financial_account_id];
    $financialType = $this->createFinancialType();
    $financialAccount = $this->addTaxAccountToFinancialType($financialType['id']);
    $expectedResult[$financialAccount->financial_account_id] = $financialAccount->financial_account_id;
    $salesTaxFinancialAccount = CRM_Contribute_BAO_Contribution::getSalesTaxFinancialAccounts();
    $this->assertEquals($salesTaxFinancialAccount, $expectedResult);
  }

  /**
   * Test for function createProportionalEntry().
   *
   * @param string $thousandSeparator
   *   punctuation used to refer to thousands.
   *
   * @dataProvider getThousandSeparators
   * @throws \CRM_Core_Exception
   */
  public function testCreateProportionalEntry(string $thousandSeparator): void {
    $this->setCurrencySeparators($thousandSeparator);
    [$contribution, $financialAccount] = $this->createContributionWithTax();
    $params = [
      'total_amount' => 55,
      'to_financial_account_id' => $financialAccount->financial_account_id,
      'payment_instrument_id' => 1,
      'trxn_date' => date('Ymd'),
      'status_id' => 1,
      'entity_id' => $contribution['id'],
    ];
    $financialTrxn = $this->callAPISuccess('FinancialTrxn', 'create', $params);
    $entityParams = [
      'contribution_total_amount' => $contribution['total_amount'],
      'trxn_total_amount' => 55,
      'line_item_amount' => 100,
    ];
    $previousLineItem = CRM_Financial_BAO_FinancialItem::getPreviousFinancialItem($contribution['id']);
    $eftParams = [
      'entity_table' => 'civicrm_financial_item',
      'entity_id' => $previousLineItem['id'],
      'financial_trxn_id' => (string) $financialTrxn['id'],
    ];
    CRM_Contribute_BAO_Contribution::createProportionalEntry($entityParams, $eftParams);
    $trxnTestArray = array_merge($eftParams, [
      'amount' => '50.00',
    ]);
    $this->callAPISuccessGetSingle('EntityFinancialTrxn', $eftParams, $trxnTestArray);
  }

  /**
   * Test for function createProportionalEntry with zero amount().
   *
   * @param string $thousandSeparator
   *   punctuation used to refer to thousands.
   *
   * @throws \CRM_Core_Exception
   * @dataProvider getThousandSeparators
   */
  public function testCreateProportionalEntryZeroAmount(string $thousandSeparator): void {
    $this->setCurrencySeparators($thousandSeparator);
    [$contribution, $financialAccount] = $this->createContributionWithTax(['total_amount' => 0]);
    $params = [
      'total_amount' => 0,
      'to_financial_account_id' => $financialAccount->financial_account_id,
      'payment_instrument_id' => 1,
      'trxn_date' => date('Ymd'),
      'status_id' => 1,
      'entity_id' => $contribution['id'],
    ];
    $financialTrxn = $this->callAPISuccess('FinancialTrxn', 'create', $params);
    $entityParams = [
      'contribution_total_amount' => $contribution['total_amount'],
      'trxn_total_amount' => 0,
      'line_item_amount' => 0,
    ];
    $previousLineItem = CRM_Financial_BAO_FinancialItem::getPreviousFinancialItem($contribution['id']);
    $eftParams = [
      'entity_table' => 'civicrm_financial_item',
      'entity_id' => $previousLineItem['id'],
      'financial_trxn_id' => (string) $financialTrxn['id'],
    ];
    CRM_Contribute_BAO_Contribution::createProportionalEntry($entityParams, $eftParams);
    $trxnTestArray = array_merge($eftParams, [
      'amount' => '0.00',
    ]);
    $this->callAPISuccessGetSingle('EntityFinancialTrxn', $eftParams, $trxnTestArray);
  }

  /**
   * Test for function getLastFinancialItemIds().
   */
  public function testGetLastFinancialItemIDs(): void {
    [$contribution] = $this->createContributionWithTax();
    [$ftIds, $taxItems] = CRM_Contribute_BAO_Contribution::getLastFinancialItemIds($contribution['id']);
    $this->assertCount(1, $ftIds, 'Invalid count.');
    $this->assertCount(1, $taxItems, 'Invalid count.');
    foreach ($taxItems as $value) {
      $this->assertEquals(10, $value['amount'], 'Invalid tax amount.');
    }
  }

  /**
   * Test to ensure proportional entries are creating when adding a payment..
   *
   * In this test we create a pending contribution for $110 consisting of $100 contribution and $10 tax.
   *
   * We pay $50, resulting in it being allocated as $45.45 payment & $4.55 tax. This is in equivalent proportions
   * to the original payment - ie. .0909 of the $110 is 10 & that * 50 is $4.54 (note the rounding seems wrong as it should be
   * saved un-rounded).
   *
   * @throws \CRM_Core_Exception
   */
  public function testCreateProportionalFinancialEntriesViaPaymentCreate(): void {
    [$contribution, $financialAccount] = $this->createContributionWithTax([], FALSE);
    $params = [
      'total_amount' => 50,
      'to_financial_account_id' => $financialAccount->financial_account_id,
      'payment_instrument_id' => 1,
      'trxn_date' => date('Ymd'),
      'status_id' => 1,
      'entity_id' => $contribution['id'],
      'contribution_id' => $contribution['id'],
    ];
    $financialTrxn = $this->callAPISuccess('Payment', 'create', $params);

    $entityFinancialTrxns = EntityFinancialTrxn::get(FALSE)
      ->addWhere('entity_table', '=', 'civicrm_financial_item')
      ->addWhere('financial_trxn_id', '=', $financialTrxn['id'])
      ->addOrderBy('amount')->execute();
    $this->assertCount(2, $entityFinancialTrxns, '2 EntityFinancialTrxns should be created (one for tax).');
    $this->assertEquals(4.55, $entityFinancialTrxns->first()['amount'], 'Incorrect tax amount in entity financial trxn');
    $this->assertEquals(45.45, $entityFinancialTrxns->last()['amount'], 'Incorrect tax exclusive amount in entity financial trxn');
  }

  /**
   * Test to check if amount is proportionally assigned for PI change.
   */
  public function testProportionallyAssignedForPIChange(): void {
    [$contribution] = $this->createContributionWithTax();
    $params = [
      'id' => $contribution['id'],
      'payment_instrument_id' => 3,
    ];
    $this->callAPISuccess('Contribution', 'create', $params);
    $lastFinancialTrxnId = CRM_Core_BAO_FinancialTrxn::getFinancialTrxnId($contribution['id'], 'DESC');
    $eftParams = [
      'entity_table' => 'civicrm_financial_item',
      'financial_trxn_id' => $lastFinancialTrxnId['financialTrxnId'],
    ];
    $entityFinancialTrxn = $this->callAPISuccess('EntityFinancialTrxn', 'Get', $eftParams);
    $this->assertEquals(2, $entityFinancialTrxn['count'], 'Invalid count.');
    $testAmount = [10, 100];
    foreach ($entityFinancialTrxn['values'] as $value) {
      $this->assertEquals($value['amount'], array_pop($testAmount), 'Invalid amount stored in civicrm_entity_financial_trxn.');
    }
  }

  /**
   * Function to create contribution with tax.
   */
  public function createContributionWithTax($params = [], $isCompleted = TRUE): array {
    if (!isset($params['total_amount'])) {
      $params['total_amount'] = 100;
    }
    $contactId = $this->individualCreate();
    $this->enableTaxAndInvoicing();
    $financialType = $this->createFinancialType();
    $financialAccount = $this->addTaxAccountToFinancialType($financialType['id']);
    /** @var CRM_Contribute_Form_Contribution $form */
    $form = $this->getFormObject('CRM_Contribute_Form_Contribution', [
      'total_amount' => $params['total_amount'],
      'financial_type_id' => $financialType['id'],
      'contact_id' => $contactId,
      'contribution_status_id' => $isCompleted ? 1 : 2,
      'price_set_id' => 0,
    ]);
    $form->buildForm();
    $form->postProcess();
    $contribution = $this->callAPISuccessGetSingle('Contribution',
      [
        'contact_id' => $contactId,
        'return' => ['tax_amount', 'total_amount'],
      ]
    );
    return [$contribution, $financialAccount];
  }

  /**
   * Test processOnBehalfOrganization() function.
   *
   * @throws \CRM_Core_Exception
   */
  public function testProcessOnBehalfOrganization(): void {
    $orgInfo = [
      'phone' => '11111111',
      'email' => 'testorg@gmail.com',
      'street_address' => 'test Street',
      'city' => 'test City',
      'state_province' => 'AA',
      'postal_code' => '222222',
      'country' => 'United States',
    ];
    $originalContactId = $contactID = $this->individualCreate();
    $orgId = $this->organizationCreate(['organization_name' => 'test organization 1']);
    $orgCount = $this->callAPISuccessGetCount('Contact', [
      'contact_type' => 'Organization',
      'organization_name' => 'test organization 1',
    ]);
    $this->assertEquals(1, $orgCount);

    $values = $params = [];
    $originalBehalfOrganization = $behalfOrganization = [
      'organization_name' => 'test organization 1',
      'phone' => [
        1 => [
          'phone' => $orgInfo['phone'],
          'is_primary' => 1,
        ],
      ],
      'email' => [
        1 => [
          'email' => $orgInfo['email'],
          'is_primary' => 1,
        ],
      ],
      'address' => [
        3 => [
          'street_address' => $orgInfo['street_address'],
          'city' => $orgInfo['city'],
          'location_type_id' => 3,
          'postal_code' => $orgInfo['postal_code'],
          'country' => 'US',
          'state_province' => 'AA',
          'is_primary' => 1,
        ],
      ],
    ];
    $fields = [
      'organization_name' => 1,
      'phone-3-1' => 1,
      'email-3' => 1,
      'street_address-3' => 1,
      'city-3' => 1,
      'postal_code-3' => 1,
      'country-3' => 1,
      'state_province-3' => 1,
    ];
    $empty = [];
    CRM_Contribute_Form_Contribution_Confirm::processOnBehalfOrganization($behalfOrganization, $contactID, $empty, $empty, $empty);

    //Check whether new organisation is created.
    $result = $this->callAPISuccess('Contact', 'get', [
      'contact_type' => 'Organization',
      'organization_name' => 'test organization 1',
    ]);
    $this->assertEquals(1, $result['count']);

    //Assert all org values are updated.
    foreach ($orgInfo as $key => $val) {
      $this->assertEquals($result['values'][$orgId][$key], $val);
    }

    //Check if alert is assigned to params if more than 1 dupe exists.
    $this->organizationCreate(['organization_name' => 'test organization 1', 'email' => 'testorg@gmail.com']);
    CRM_Contribute_Form_Contribution_Confirm::processOnBehalfOrganization($originalBehalfOrganization, $originalContactId, $values, $params, $fields);
    $this->assertEquals(1, $params['onbehalf_dupe_alert']);
  }

  /**
   * Test for contribution with deferred revenue.
   */
  public function testContributionWithDeferredRevenue(): void {
    $contactId = $this->individualCreate();
    Civi::settings()->set('deferred_revenue_enabled', TRUE);
    $params = [
      'contact_id' => $contactId,
      'receive_date' => '20120511',
      'total_amount' => 100.00,
      'financial_type_id' => 'Event Fee',
      'trxn_id' => 12345,
      'invoice_id' => 67890,
      'source' => 'SSF',
      'contribution_status_id' => 'Completed',
      'revenue_recognition_date' => date('Ymd', strtotime('+3 month')),
    ];
    $contribution = $this->callAPISuccess('contribution', 'create', $params);

    $this->callAPISuccessGetCount('EntityFinancialTrxn', [
      'entity_table' => 'civicrm_contribution',
      'entity_id' => $contribution['id'],
    ], 2);

    $checkAgainst = [
      'financial_trxn_id.to_financial_account_id.name' => 'Deferred Revenue - Event Fee',
      'financial_trxn_id.from_financial_account_id.name' => 'Event Fee',
      'financial_trxn_id' => '2',
    ];
    $result = $this->callAPISuccessGetSingle('EntityFinancialTrxn', [
      'return' => [
        'financial_trxn_id.from_financial_account_id.name',
        'financial_trxn_id.to_financial_account_id.name',
        'financial_trxn_id',
      ],
      'entity_table' => 'civicrm_contribution',
      'entity_id' => $contribution['id'],
      'financial_trxn_id.is_payment' => 0,
    ], $checkAgainst);

    $result = $this->callAPISuccessGetSingle('EntityFinancialTrxn', [
      'entity_table' => 'civicrm_financial_item',
      'financial_trxn_id' => $result['financial_trxn_id'],
      'return' => ['entity_id'],
    ]);

    $checkAgainst = [
      'financial_account_id.name' => 'Deferred Revenue - Event Fee',
      'id' => $result['entity_id'],
    ];
    $this->callAPISuccessGetSingle('FinancialItem', [
      'id' => $result['entity_id'],
      'return' => ['financial_account_id.name'],
    ], $checkAgainst);
  }

  /**
   *  https://lab.civicrm.org/dev/financial/issues/56
   * Changing financial type on a contribution records correct financial items
   */
  public function testChangingFinancialTypeWithoutTax(): void {
    $contactId = $this->individualCreate();
    $params = [
      'contact_id' => $contactId,
      'receive_date' => date('YmdHis'),
      'total_amount' => 100.00,
      'financial_type_id' => 'Donation',
      'contribution_status_id' => 'Completed',
    ];
    /* first test the scenario when sending an email */
    $contributionId = $this->callAPISuccess(
      'contribution',
      'create',
      $params
    )['id'];

    // Update Financial Type.
    $this->callAPISuccess('contribution', 'create', [
      'id' => $contributionId,
      'financial_type_id' => 'Event Fee',
    ]);

    // Get line item
    $lineItem = $this->callAPISuccessGetSingle('LineItem', [
      'contribution_id' => $contributionId,
      'return' => ['financial_type_id.name', 'line_total'],
    ]);

    $this->assertEquals(
      100.00,
      $lineItem['line_total'],
      'Invalid line amount.'
    );

    $this->assertEquals(
      'Event Fee',
      $lineItem['financial_type_id.name'],
      'Invalid Financial Type stored.'
    );

    // Get Financial Items.
    $financialItems = $this->callAPISuccess('FinancialItem', 'get', [
      'entity_id' => $lineItem['id'],
      'sequential' => 1,
      'entity_table' => 'civicrm_line_item',
      'options' => ['sort' => 'id'],
      'return' => ['financial_account_id.name', 'amount', 'description'],
    ]);

    $this->assertEquals(3, $financialItems['count'], 'Count mismatch.');

    $toCheck = [
      ['Donation', 100.00],
      ['Donation', -100.00],
      ['Event Fee', 100.00],
    ];

    foreach ($financialItems['values'] as $key => $values) {
      $this->assertEquals(
        $values['financial_account_id.name'],
        $toCheck[$key][0],
        'Invalid Financial Account stored.'
      );
      $this->assertEquals(
        $values['amount'],
        $toCheck[$key][1],
        'Amount mismatch.'
      );
      $this->assertEquals(
        'Contribution Amount',
        $values['description'],
        'Description mismatch.'
      );
    }

    // Check transactions.
    $financialTransactions = $this->callAPISuccess('EntityFinancialTrxn', 'get', [
      'return' => ['financial_trxn_id'],
      'entity_table' => 'civicrm_contribution',
      'entity_id' => $contributionId,
      'sequential' => 1,
    ]);
    $this->assertEquals(3, $financialTransactions['count'], 'Count mismatch.');

    foreach ($financialTransactions['values'] as $key => $values) {
      $this->callAPISuccessGetCount('EntityFinancialTrxn', [
        'financial_trxn_id' => $values['financial_trxn_id'],
        'amount' => $toCheck[$key][1],
        'financial_trxn_id.total_amount' => $toCheck[$key][1],
      ], 2);
    }
  }

  /**
   *  CRM-21424 Check if the receipt update is set after composing the receipt
   * message
   *
   * @throws \CRM_Core_Exception
   */
  public function testSendMailUpdateReceiptDate(): void {
    $ids = $values = [];
    $contactId = $this->individualCreate();
    $params = [
      'contact_id' => $contactId,
      'receive_date' => '20120511',
      'total_amount' => 100.00,
      'financial_type_id' => 'Donation',
      'source' => 'SSF',
      'contribution_status_id' => 'Completed',
    ];
    /* first test the scenario when sending an email */
    $contribution = $this->callAPISuccess('contribution', 'create', $params);
    $contributionId = $contribution['id'];
    $this->assertDBNull('CRM_Contribute_BAO_Contribution', $contributionId, 'receipt_date', 'id', 'After creating receipt date must be null');
    $input = ['receipt_update' => 0];
    CRM_Contribute_BAO_Contribution::sendMail($input, $ids, $contributionId, $values);
    $this->assertDBNull('CRM_Contribute_BAO_Contribution', $contributionId, 'receipt_date', 'id', 'After sendMail, with the explicit instruction not to update receipt date stays null');
    $input = ['receipt_update' => 1];
    CRM_Contribute_BAO_Contribution::sendMail($input, $ids, $contributionId, $values);
    $this->assertDBNotNull('CRM_Contribute_BAO_Contribution', $contributionId, 'receipt_date', 'id', 'After sendMail with the permission to allow update receipt date must be set');

    /* repeat the same scenario for downloading a pdf */
    $contributionID = $this->callAPISuccess('Contribution', 'create', $params)['id'];

    $this->assertDBNull('CRM_Contribute_BAO_Contribution', $contributionID, 'receipt_date', 'id', 'After creating receipt date must be null');
    $input = ['receipt_update' => 0];
    /* setting the last parameter (returnMessageText) to TRUE is done by the download of the pdf */
    CRM_Contribute_BAO_Contribution::sendMail($input, $ids, $contributionID, $values, TRUE);
    $this->assertDBNull('CRM_Contribute_BAO_Contribution', $contributionID, 'receipt_date', 'id', 'After sendMail, with the explicit instruction not to update receipt date stays null');
    $input = ['receipt_update' => 1];
    CRM_Contribute_BAO_Contribution::sendMail($input, $ids, $contributionID, $values, TRUE);
    $this->assertDBNotNull('CRM_Contribute_BAO_Contribution', $contributionID, 'receipt_date', 'id', 'After sendMail with the permission to allow update receipt date must be set');
  }

  /**
   * Test cancel order api when a pledge is linked.
   *
   * The pledge status should be updated. I believe the contribution should
   * also be unlinked but the goal at this point is no change.
   *
   * @throws \CRM_Core_Exception
   */
  public function testCancelOrderWithPledge(): void {
    $this->ids['contact'][0] = $this->individualCreate();
    $pledgeID = (int) $this->callAPISuccess('Pledge', 'create', ['contact_id' => $this->ids['contact'][0], 'amount' => 4, 'installments' => 2, 'frequency_unit' => 'month', 'original_installment_amount' => 2, 'create_date' => 'now', 'financial_type_id' => 'Donation', 'start_date' => '+5 days'])['id'];
    $orderID = (int) $this->callAPISuccess('Order', 'create', ['contact_id' => $this->ids['contact'][0], 'total_amount' => 2, 'financial_type_id' => 'Donation', 'api.Payment.create' => ['total_amount' => 2]])['id'];
    $pledgePayments = $this->callAPISuccess('PledgePayment', 'get')['values'];
    $this->callAPISuccess('PledgePayment', 'create', ['id' => key($pledgePayments), 'pledge_id' => $pledgeID, 'contribution_id' => $orderID, 'status_id' => 'Completed', 'actual_amount' => 2]);
    $beforePledge = $this->callAPISuccessGetSingle('Pledge', ['id' => $pledgeID]);
    $this->assertEquals(2, $beforePledge['pledge_total_paid']);
    $this->callAPISuccess('Order', 'cancel', ['contribution_id' => $orderID]);

    $this->callAPISuccessGetSingle('Contribution', ['contribution_status_id' => 'Cancelled']);
    $afterPledge = $this->callAPISuccessGetSingle('Pledge', ['id' => $pledgeID]);
    $this->assertEquals('', $afterPledge['pledge_total_paid']);
    $payments = PledgePayment::get(FALSE)->addWhere('contribution_id', 'IS NOT NULL')->execute();
    $this->assertCount(0, $payments);
  }

  /**
   * Test contribution update when more than one quick
   * config line item is linked to contribution.
   *
   * @throws \CRM_Core_Exception
   */
  public function testContributionQuickConfigTwoLineItems(): void {
    $contactId1 = $this->individualCreate();
    $contactId2 = $this->individualCreate();
    $membershipOrganizationId = $this->organizationCreate();

    // Created new contribution to bypass the deprecated error
    // 'Per https://lab.civicrm.org/dev/core/issues/15 this data fix should not be required.'
    // in CRM_Price_BAO_LineItem::processPriceSet();
    $this->callAPISuccess('Contribution', 'create', [
      'contact_id' => $contactId1,
      'receive_date' => '2010-01-20',
      'financial_type_id' => 'Member Dues',
      'contribution_status_id' => 'Completed',
      'total_amount' => 150,
    ]);
    $this->callAPISuccess('Contribution', 'create', [
      'contact_id' => $contactId1,
      'receive_date' => '2010-01-20',
      'financial_type_id' => 'Member Dues',
      'contribution_status_id' => 'Completed',
      'total_amount' => 150,
    ]);

    // create membership type
    $membershipTypeId1 = $this->callAPISuccess('MembershipType', 'create', [
      'domain_id' => 1,
      'member_of_contact_id' => $membershipOrganizationId,
      'financial_type_id' => 'Member Dues',
      'duration_unit' => 'month',
      'duration_interval' => 1,
      'period_type' => 'rolling',
      'minimum_fee' => 100,
      'name' => 'Parent',
    ])['id'];

    $membershipTypeId2 = $this->callAPISuccess('MembershipType', 'create', [
      'domain_id' => 1,
      'member_of_contact_id' => $membershipOrganizationId,
      'financial_type_id' => 'Member Dues',
      'duration_unit' => 'month',
      'duration_interval' => 1,
      'period_type' => 'rolling',
      'minimum_fee' => 50,
      'name' => 'Child',
    ])['id'];

    $contactIds = [
      $contactId1 => $membershipTypeId1,
      $contactId2 => $membershipTypeId2,
    ];

    $priceFields = CRM_Price_BAO_PriceSet::getDefaultPriceSet('membership');

    // prepare order api params.
    $p = [
      'contact_id' => $contactId1,
      'receive_date' => '2010-01-20',
      'financial_type_id' => 'Member Dues',
      'contribution_status_id' => 'Pending',
      'total_amount' => 150,
      'api.Payment.create' => ['total_amount' => 150],
    ];

    foreach ($priceFields as $priceField) {
      $lineItems = [];
      $contactId = array_search($priceField['membership_type_id'], $contactIds);
      $lineItems[1] = [
        'price_field_id' => $priceField['priceFieldID'],
        'price_field_value_id' => $priceField['priceFieldValueID'],
        'label' => $priceField['label'],
        'field_title' => $priceField['label'],
        'qty' => 1,
        'unit_price' => $priceField['amount'],
        'line_total' => $priceField['amount'],
        'financial_type_id' => $priceField['financial_type_id'],
        'entity_table' => 'civicrm_membership',
        'membership_type_id' => $priceField['membership_type_id'],
      ];
      $p['line_items'][] = [
        'line_item' => $lineItems,
        'params' => [
          'contact_id' => $contactId,
          'membership_type_id' => $priceField['membership_type_id'],
          'source' => 'Payment',
          'join_date' => '2020-04-28',
          'start_date' => '2020-04-28',
          'status_id' => 'Pending',
          'is_override' => 1,
        ],
      ];
    }
    $order = $this->callAPISuccess('order', 'create', $p);
    $contributionId = $order['id'];

    $count = CRM_Core_DAO::singleValueQuery('
      SELECT count(*), total_amount
      FROM civicrm_contribution cc
        INNER JOIN civicrm_line_item cli
          ON cli.contribution_id = cc.id
           AND cc.id = %1
      GROUP BY cc.id, total_amount
      HAVING SUM(cli.line_total) != total_amount
    ', [1 => [$contributionId, 'Integer']]);

    $this->assertEquals(0, $count);

    $this->callAPISuccess('Contribution', 'create', [
      'id' => $contributionId,
      'total_amount' => 150,
    ]);
    $count = CRM_Core_DAO::singleValueQuery('
      SELECT count(*), total_amount
      FROM civicrm_contribution cc
        INNER JOIN civicrm_line_item cli
          ON cli.contribution_id = cc.id
           AND cc.id = %1
      GROUP BY cc.id, total_amount
      HAVING SUM(cli.line_total) != total_amount
    ', [1 => [$contributionId, 'Integer']]);

    $this->assertEquals(0, $count);
  }

  /**
   * Test activity contact is updated when contribution contact is changed
   */
  public function testUpdateActivityContactOnContributionContactChange(): void {
    $contactId_1 = $this->individualCreate();
    $contactId_2 = $this->individualCreate();
    $contactId_3 = $this->individualCreate();

    $contributionParams = [
      'financial_type_id' => 'Donation',
      'receive_date' => date('Y-m-d H:i:s'),
      'sequential' => TRUE,
      'total_amount' => 50,
    ];

    // Case 1: Only source contact, no target contact

    $contribution = $this->callAPISuccess('Contribution', 'create', array_merge(
      $contributionParams,
      ['contact_id' => $contactId_1]
    ))['values'][0];

    $activity = $this->callAPISuccessGetSingle('Activity', ['source_record_id' => $contribution['id']]);

    $activityContactParams = [
      'activity_id' => $activity['id'],
      'record_type_id' => 'Activity Source',
    ];

    $activityContact = $this->callAPISuccessGetSingle('ActivityContact', $activityContactParams);

    $this->assertEquals($activityContact['contact_id'], $contactId_1, 'Check source contact ID matches the first contact');

    $this->callAPISuccess('Contribution', 'create', array_merge(
      $contributionParams,
      [
        'id' => $contribution['id'],
        'contact_id' => $contactId_2,
      ]
    ))['values'][0];

    $activityContact = $this->callAPISuccessGetSingle('ActivityContact', $activityContactParams);

    $this->assertEquals($activityContact['contact_id'], $contactId_2, 'Check source contact ID matches the second contact');

    // Case 2: Source and target contact

    $contribution = $this->callAPISuccess('Contribution', 'create', array_merge(
      $contributionParams,
      [
        'contact_id' => $contactId_1,
        'source_contact_id' => $contactId_3,
      ]
    ))['values'][0];

    $activity = $this->callAPISuccessGetSingle('Activity', ['source_record_id' => $contribution['id']]);

    $activityContactParams = [
      'activity_id' => $activity['id'],
      'record_type_id' => 'Activity Targets',
    ];

    $activityContact = $this->callAPISuccessGetSingle('ActivityContact', $activityContactParams);

    $this->assertEquals($activityContact['contact_id'], $contactId_1, 'Check target contact ID matches first contact');

    $this->callAPISuccess('Contribution', 'create', array_merge(
      $contributionParams,
      [
        'id' => $contribution['id'],
        'contact_id' => $contactId_2,
      ]
    ))['values'][0];

    $activityContact = $this->callAPISuccessGetSingle('ActivityContact', $activityContactParams);

    $this->assertEquals($activityContact['contact_id'], $contactId_2, 'Check target contact ID matches the second contact');
  }

  /**
   * Test status updates triggering activity creation and value propagation
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testContributionStatusUpdateActivityPropagation(): void {
    $contactId = $this->individualCreate();
    $campaignId = $this->campaignCreate();
    $contribution = Contribution::create()
      ->addValue('contact_id', $contactId)
      ->addValue('campaign_id', $campaignId)
      ->addValue('financial_type_id:name', 'Donation')
      ->addValue('total_amount', 50)
      ->addValue('contribution_status_id:name', 'Pending')
      ->execute()
      ->first();
    $activityWhere = [
      ['source_record_id', '=', $contribution['id']],
      ['activity_type_id:name', '=', 'Contribution'],
    ];
    $activity = Activity::get()->setWhere($activityWhere)->execute()->first();
    $this->assertNull($activity, 'Should not create contribution activity for pending contribution');

    Contribution::update()
      ->addWhere('id', '=', $contribution['id'])
      ->addValue('contribution_status_id:name', 'Completed')
      ->execute();

    $activity = Activity::get()->setWhere($activityWhere)->execute()->first();
    $this->assertEquals($campaignId, $activity['campaign_id'], 'Should have created contribution activity with campaign');

    $newCampaignId = $this->campaignCreate();
    Contribution::update()
      ->addWhere('id', '=', $contribution['id'])
      ->addValue('campaign_id', $newCampaignId)
      ->execute();

    $activity = Activity::get()->setWhere($activityWhere)->execute()->first();
    $this->assertEquals($newCampaignId, $activity['campaign_id'], 'Should have updated contribution activity to new campaign');

    Contribution::update()
      ->addWhere('id', '=', $contribution['id'])
      ->addValue('campaign_id', NULL)
      ->execute();

    $activity = Activity::get()->setWhere($activityWhere)->execute()->first();
    $this->assertNull($activity['campaign_id'], 'Should have removed campaign from contribution activity');
  }

  /**
   * @param int $expectedCount
   * @param string $transactionID
   * @param bool $isPayment
   */
  protected function assertFinancialTransactionCount(int $expectedCount, string $transactionID, bool $isPayment): void {
    try {
      $this->assertCount($expectedCount, FinancialTrxn::get()
        ->addWhere('trxn_id', '=', $transactionID)
        ->addWhere('is_payment', '=', $isPayment)
        ->execute(), 'Financial transaction count is incorrect');
    }
    catch (CRM_Core_Exception $e) {
      $this->fail($e->getMessage());
    }
  }

}
