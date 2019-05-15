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
 | at info'AT'civicrm'DOT'org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

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
  public function tearDown() {
    $this->quickCleanUpFinancialEntities();
    parent::tearDown();
  }

  /**
   * Test create method (create and update modes).
   */
  public function testCreate() {
    $contactId = $this->individualCreate();

    $params = array(
      'contact_id' => $contactId,
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
      'trxn_id' => '22ereerwww444444',
      'invoice_id' => '86ed39c9e9ee6ef6031621ce0eafe7eb81',
      'thankyou_date' => '20080522',
      'sequential' => TRUE,
    );

    $contribution = $this->callAPISuccess('Contribution', 'create', $params)['values'][0];

    $this->assertEquals($params['trxn_id'], $contribution['trxn_id'], 'Check for transaction id creation.');
    $this->assertEquals($contactId, $contribution['contact_id'], 'Check for contact id  creation.');

    //update contribution amount
    $params['id'] = $contribution['id'];
    $params['fee_amount'] = 10;
    $params['net_amount'] = 190;

    $contribution = $this->callAPISuccess('Contribution', 'create', $params)['values'][0];

    $this->assertEquals($params['trxn_id'], $contribution['trxn_id'], 'Check for transcation id .');
    $this->assertEquals($params['net_amount'], $contribution['net_amount'], 'Check for Amount updation.');
  }

  /**
   * Create() method with custom data.
   */
  public function testCreateWithCustomData() {
    $contactId = $this->individualCreate();

    //create custom data
    $customGroup = $this->customGroupCreate(array('extends' => 'Contribution'));
    $customGroupID = $customGroup['id'];
    $customGroup = $customGroup['values'][$customGroupID];

    $fields = array(
      'label' => 'testFld',
      'data_type' => 'String',
      'html_type' => 'Text',
      'is_active' => 1,
      'custom_group_id' => $customGroupID,
    );
    $customField = CRM_Core_BAO_CustomField::create($fields);

    $params = array(
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
      'trxn_id' => '22ereerwww322323',
      'invoice_id' => '22ed39c9e9ee6ef6031621ce0eafe6da70',
      'thankyou_date' => '20080522',
      'skipCleanMoney' => TRUE,
    );

    $params['custom'] = array(
      $customField->id => array(
        -1 => array(
          'value' => 'Test custom value',
          'type' => 'String',
          'custom_field_id' => $customField->id,
          'custom_group_id' => $customGroupID,
          'table_name' => $customGroup['table_name'],
          'column_name' => $customField->column_name,
          'file_id' => NULL,
        ),
      ),
    );

    $contribution = CRM_Contribute_BAO_Contribution::create($params);

    // Check that the custom field value is saved
    $customValueParams = array(
      'entityID' => $contribution->id,
      'custom_' . $customField->id => 1,
    );
    $values = CRM_Core_BAO_CustomValueTable::getValues($customValueParams);
    $this->assertEquals('Test custom value', $values['custom_' . $customField->id], 'Check the custom field value');

    $this->assertEquals($params['trxn_id'], $contribution->trxn_id, 'Check for transcation id creation.');
    $this->assertEquals($contactId, $contribution->contact_id, 'Check for contact id for Conribution.');
  }

  /**
   * CRM-21026 Test ContributionCount after contribution created with disabled FT
   */
  public function testContributionCountDisabledFinancialType() {
    $contactId = $this->individualCreate();
    $financialType = array(
      'name' => 'grassvariety1' . substr(sha1(rand()), 0, 7),
      'is_reserved' => 0,
      'is_active' => 0,
    );
    $finType = $this->callAPISuccess('financial_type', 'create', $financialType);
    $params = array(
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
      'trxn_id' => '22ereerwww322323',
      'invoice_id' => '22ed39c9e9ee6ef6031621ce0eafe6da70',
      'thankyou_date' => '20080522',
    );
    $this->callAPISuccess('Contribution', 'create', $params);
    $this->callAPISuccess('financial_type', 'create', array('is_active' => 0, 'id' => $finType['id']));
    $contributionCount = CRM_Contribute_BAO_Contribution::contributionCount($contactId);
    $this->assertEquals(1, $contributionCount);
  }

  /**
   * DeleteContribution() method
   */
  public function testDeleteContribution() {
    $contactId = $this->individualCreate();

    $params = array(
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
      'trxn_id' => '33ereerwww322323',
      'invoice_id' => '33ed39c9e9ee6ef6031621ce0eafe6da70',
      'thankyou_date' => '20080522',
      'sequential' => TRUE,
    );

    $contribution = $this->callAPISuccess('Contribution', 'create', $params)['values'][0];

    CRM_Contribute_BAO_Contribution::deleteContribution($contribution['id']);

    $this->assertDBNull('CRM_Contribute_DAO_Contribution', $contribution['trxn_id'],
      'id', 'trxn_id', 'Database check for deleted Contribution.'
    );
  }

  /**
   * Create honor-contact method.
   */
  public function testCreateAndGetHonorContact() {
    $firstName = 'John_' . substr(sha1(rand()), 0, 7);
    $lastName = 'Smith_' . substr(sha1(rand()), 0, 7);
    $email = "{$firstName}.{$lastName}@example.com";

    //Get profile id of name honoree_individual used to create profileContact
    $honoreeProfileId = NULL;
    $ufGroupDAO = new CRM_Core_DAO_UFGroup();
    $ufGroupDAO->name = 'honoree_individual';
    if ($ufGroupDAO->find(TRUE)) {
      $honoreeProfileId = $ufGroupDAO->id;
    }

    $params = array(
      'prefix_id' => 3,
      'first_name' => $firstName,
      'last_name' => $lastName,
      'email-1' => $email,
    );
    $softParam = array('soft_credit_type_id' => 1);

    $honoreeContactId = CRM_Contact_BAO_Contact::createProfileContact($params, CRM_Core_DAO::$_nullArray,
      NULL, NULL, $honoreeProfileId
    );

    $this->assertDBCompareValue('CRM_Contact_DAO_Contact', $honoreeContactId, 'first_name', 'id', $firstName,
      'Database check for created honor contact record.'
    );
    //create contribution on behalf of honary.

    $contactId = $this->individualCreate(array('first_name' => 'John', 'last_name' => 'Doe'));

    $param = array(
      'contact_id' => $contactId,
      'currency' => 'USD',
      'financial_type_id' => 4,
      'contribution_status_id' => 1,
      'receive_date' => date('Ymd'),
      'total_amount' => 66,
      'sequential' => 1,
    );

    $contribution = $this->callAPISuccess('Contribution', 'create', $param)['values'][0];
    $id = $contribution['id'];
    $softParam['contact_id'] = $honoreeContactId;
    $softParam['contribution_id'] = $id;
    $softParam['currency'] = $contribution['currency'];
    $softParam['amount'] = $contribution['total_amount'];

    //Create Soft Contribution for honoree contact
    CRM_Contribute_BAO_ContributionSoft::add($softParam);

    $this->assertDBCompareValue('CRM_Contribute_DAO_ContributionSoft', $id, 'contact_id',
      'contribution_id', $honoreeContactId, 'Check DB for honor contact of the contribution'
    );
    //get honorary information
    $getHonorContact = CRM_Contribute_BAO_Contribution::getHonorContacts($honoreeContactId);
    $this->assertEquals(array(
      $id => array(
        'honor_type' => 'In Honor of',
        'honorId' => $contactId,
        'display_name' => 'Mr. John Doe II',
        'type' => 'Event Fee',
        'type_id' => '4',
        'amount' => '$ 66.00',
        'source' => NULL,
        'receive_date' => date('Y-m-d 00:00:00'),
        'contribution_status' => 'Completed',
      ),
    ), $getHonorContact);

    $this->assertDBCompareValue('CRM_Contact_DAO_Contact', $honoreeContactId, 'first_name', 'id', $firstName,
      'Database check for created honor contact record.'
    );

    //get annual contribution information
    $annual = CRM_Contribute_BAO_Contribution::annual($contactId);

    $currencySymbol = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_Currency', CRM_Core_Config::singleton()->defaultCurrency, 'symbol', 'name');
    $this->assertDBCompareValue('CRM_Contribute_DAO_Contribution', $id, 'total_amount',
      'id', ltrim($annual[2], $currencySymbol), 'Check DB for total amount of the contribution'
    );
  }

  /**
   * Test that financial type data is not added to the annual query if acls not enabled.
   */
  public function testAnnualQueryWithFinancialACLsEnabled() {
    $this->enableFinancialACLs();
    $this->createLoggedInUserWithFinancialACL();
    $permittedFinancialType = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'financial_type_id', 'Donation');
    $sql = CRM_Contribute_BAO_Contribution::getAnnualQuery([1, 2, 3]);
    $this->assertContains('SUM(total_amount) as amount,', $sql);
    $this->assertContains('WHERE b.contact_id IN (1,2,3)', $sql);
    $this->assertContains('b.financial_type_id IN (' . $permittedFinancialType . ')', $sql);

    // Run it to make sure it's not bad sql.
    CRM_Core_DAO::executeQuery($sql);
    $this->disableFinancialACLs();
  }

  /**
   * Test the annual query returns a correct result when multiple line items are present.
   */
  public function testAnnualWithMultipleLineItems() {
    $contactID = $this->createLoggedInUserWithFinancialACL();
    $this->createContributionWithTwoLineItemsAgainstPriceSet([
      'contact_id' => $contactID,
    ]
    );
    $this->enableFinancialACLs();
    $sql = CRM_Contribute_BAO_Contribution::getAnnualQuery([$contactID]);
    $result = CRM_Core_DAO::executeQuery($sql);
    $result->fetch();
    $this->assertEquals(300, $result->amount);
    $this->assertEquals(1, $result->count);
    $this->disableFinancialACLs();
  }

  /**
   * Test that financial type data is not added to the annual query if acls not enabled.
   */
  public function testAnnualQueryWithFinancialACLsDisabled() {
    $sql = CRM_Contribute_BAO_Contribution::getAnnualQuery([1, 2, 3]);
    $this->assertContains('SUM(total_amount) as amount,', $sql);
    $this->assertContains('WHERE b.contact_id IN (1,2,3)', $sql);
    $this->assertNotContains('b.financial_type_id', $sql);
    //$this->assertNotContains('line_item', $sql);
    // Run it to make sure it's not bad sql.
    CRM_Core_DAO::executeQuery($sql);
  }

  /**
   * Test that financial type data is not added to the annual query if acls not enabled.
   */
  public function testAnnualQueryWithFinancialHook() {
    $this->hookClass->setHook('civicrm_selectWhereClause', array($this, 'aclIdNoZero'));
    $sql = CRM_Contribute_BAO_Contribution::getAnnualQuery([1, 2, 3]);
    $this->assertContains('SUM(total_amount) as amount,', $sql);
    $this->assertContains('WHERE b.contact_id IN (1,2,3)', $sql);
    $this->assertContains('b.id NOT IN (0)', $sql);
    $this->assertNotContains('b.financial_type_id', $sql);
    CRM_Core_DAO::executeQuery($sql);
  }

  /**
   * Add ACL denying values LIKE '0'.
   *
   * @param string $entity
   * @param string $clauses
   */
  public function aclIdNoZero($entity, &$clauses) {
    if ($entity != 'Contribution') {
      return;
    }
    $clauses['id'] = "NOT IN (0)";
  }

  /**
   * Display sort name during.
   * Update multiple contributions
   * sortName();
   */
  public function testsortName() {
    $params = array(
      'first_name' => 'Shane',
      'last_name' => 'Whatson',
      'contact_type' => 'Individual',
    );

    $contact = CRM_Contact_BAO_Contact::add($params);

    //Now check $contact is object of contact DAO..
    $this->assertInstanceOf('CRM_Contact_DAO_Contact', $contact, 'Check for created object');

    $contactId = $contact->id;
    $param = array(
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
      'trxn_id' => '22ereerwww323',
      'invoice_id' => '22ed39c9e9ee621ce0eafe6da70',
      'thankyou_date' => '20080522',
      'sequential' => TRUE,
    );

    $contribution = $this->callAPISuccess('Contribution', 'create', $param)['values'][0];

    $this->assertEquals($param['trxn_id'], $contribution['trxn_id'], 'Check for transcation id creation.');
    $this->assertEquals($contactId, $contribution['contact_id'], 'Check for contact id  creation.');

    //display sort name during Update multiple contributions
    $sortName = CRM_Contribute_BAO_Contribution::sortName($contribution['id']);

    $this->assertEquals('Whatson, Shane', $sortName, 'Check for sort name.');
  }

  /**
   * Add premium during online Contribution.
   *
   * AddPremium();
   */
  public function testAddPremium() {
    $contactId = $this->individualCreate();

    $params = array(
      'name' => 'TEST Premium',
      'sku' => 111,
      'imageOption' => 'noImage',
      'MAX_FILE_SIZE' => 2097152,
      'price' => 100.00,
      'cost' => 90.00,
      'min_contribution' => 100,
      'is_active' => 1,
    );
    $premium = CRM_Contribute_BAO_Product::create($params);

    $this->assertEquals('TEST Premium', $premium->name, 'Check for premium  name.');

    $contributionParams = array(
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
      'trxn_id' => '33erdfrwvw434',
      'invoice_id' => '98ed34f7u9hh672ce0eafe8fb92',
      'thankyou_date' => '20080522',
      'sequential' => TRUE,
    );
    $contribution = $this->callAPISuccess('Contribution', 'create', $contributionParams)['values'][0];

    $this->assertEquals($contributionParams['trxn_id'], $contribution['trxn_id'], 'Check for transcation id creation.');
    $this->assertEquals($contactId, $contribution['contact_id'], 'Check for contact id  creation.');

    //parameter for adding premium to contribution
    $data = array(
      'product_id' => $premium->id,
      'contribution_id' => $contribution['id'],
      'product_option' => NULL,
      'quantity' => 1,
    );
    $contributionProduct = CRM_Contribute_BAO_Contribution::addPremium($data);
    $this->assertEquals($contributionProduct->product_id, $premium->id, 'Check for Product id .');

    //Delete Product
    CRM_Contribute_BAO_Product::del($premium->id);
    $this->assertDBNull('CRM_Contribute_DAO_Product', $premium->name,
      'id', 'name', 'Database check for deleted Product.'
    );
  }

  /**
   * Check duplicate contribution id.
   * during the contribution import
   * checkDuplicateIds();
   */
  public function testcheckDuplicateIds() {
    $contactId = $this->individualCreate();

    $param = array(
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
      'trxn_id' => '76ereeswww835',
      'invoice_id' => '93ed39a9e9hd621bs0eafe3da82',
      'thankyou_date' => '20080522',
      'sequential' => TRUE,
    );

    $contribution = $this->callAPISuccess('Contribution', 'create', $param)['values'][0];

    $this->assertEquals($param['trxn_id'], $contribution['trxn_id'], 'Check for transcation id creation.');
    $this->assertEquals($contactId, $contribution['contact_id'], 'Check for contact id  creation.');
    $data = array(
      'id' => $contribution['id'],
      'trxn_id' => $contribution['trxn_id'],
      'invoice_id' => $contribution['invoice_id'],
    );
    $contributionID = CRM_Contribute_BAO_Contribution::checkDuplicateIds($data);
    $this->assertEquals($contributionID, $contribution['id'], 'Check for duplicate transcation id .');
  }

  /**
   * Check credit note id creation
   * when a contribution is cancelled or refunded
   * createCreditNoteId();
   */
  public function testCreateCreditNoteId() {
    $contactId = $this->individualCreate();

    $param = array(
      'contact_id' => $contactId,
      'currency' => 'USD',
      'financial_type_id' => 1,
      'contribution_status_id' => 3,
      'payment_instrument_id' => 1,
      'source' => 'STUDENT',
      'receive_date' => '20080522000000',
      'receipt_date' => '20080522000000',
      'id' => NULL,
      'non_deductible_amount' => 0.00,
      'total_amount' => 300.00,
      'fee_amount' => 5,
      'net_amount' => 295,
      'trxn_id' => '76ereeswww835',
      'invoice_id' => '93ed39a9e9hd621bs0eafe3da82',
      'thankyou_date' => '20080522',
      'sequential' => TRUE,
    );

    $creditNoteId = CRM_Contribute_BAO_Contribution::createCreditNoteId();
    $contribution = $this->callAPISuccess('Contribution', 'create', $param)['values'][0];
    $this->assertEquals($contactId, $contribution['contact_id'], 'Check for contact id  creation.');
    $this->assertEquals($creditNoteId, $contribution['creditnote_id'], 'Check if credit note id is created correctly.');
  }

  /**
   * Create() method (create and update modes).
   */
  public function testIsPaymentFlag() {
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
      'non_deductible_amount' => 0.00,
      'total_amount' => 200.00,
      'fee_amount' => 5,
      'net_amount' => 195,
      'trxn_id' => '22ereerwww4444xx',
      'invoice_id' => '86ed39c9e9ee6ef6541621ce0eafe7eb81',
      'thankyou_date' => '20080522',
      'sequential' => TRUE,
    ];
    $contribution = $this->callAPISuccess('Contribution', 'create', $params)['values'][0];

    $this->assertEquals($params['trxn_id'], $contribution['trxn_id'], 'Check for transcation id creation.');
    $this->assertEquals($contactId, $contribution['contact_id'], 'Check for contact id  creation.');

    $trxnArray = array(
      'trxn_id' => $params['trxn_id'],
      'is_payment' => 1,
    );
    $defaults = array();
    $financialTrxn = CRM_Core_BAO_FinancialTrxn::retrieve($trxnArray, $defaults);
    $this->assertEquals(1, $financialTrxn->N, 'Mismatch count for is payment flag.');
    //update contribution amount
    $params['id'] = $contribution['id'];
    $params['total_amount'] = 150;
    $contribution = $this->callAPISuccess('Contribution', 'create', $params)['values'][0];

    $this->assertEquals($params['trxn_id'], $contribution['trxn_id'], 'Check for transcation id .');
    $this->assertEquals($params['total_amount'], $contribution['total_amount'], 'Check for Amount updation.');
    $trxnArray = array(
      'trxn_id' => $params['trxn_id'],
      'is_payment' => 1,
    );
    $defaults = array();
    $financialTrxn = CRM_Core_BAO_FinancialTrxn::retrieve($trxnArray, $defaults);
    $this->assertEquals(2, $financialTrxn->N, 'Mismatch count for is payment flag.');
    $trxnArray['is_payment'] = 0;
    $financialTrxn = CRM_Core_BAO_FinancialTrxn::retrieve($trxnArray, $defaults);
    $this->assertEquals(1, $financialTrxn->N, 'Mismatch count for is payment flag.');
  }

  /**
   * Create() method (create and update modes).
   */
  public function testIsPaymentFlagForPending() {
    $contactId = $this->individualCreate();

    $params = array(
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
      'trxn_id' => '22ereerwww4444yy',
      'invoice_id' => '86ed39c9e9yy6ef6541621ce0eafe7eb81',
      'thankyou_date' => '20080522',
      'sequential' => TRUE,
    );

    $contribution = $this->callAPISuccess('Contribution', 'create', $params)['values'][0];

    $this->assertEquals($params['trxn_id'], $contribution['trxn_id'], 'Check for transaction id creation.');
    $this->assertEquals($contactId, $contribution['contact_id'], 'Check for contact id  creation.');

    $trxnArray = array(
      'trxn_id' => $params['trxn_id'],
      'is_payment' => 0,
    );
    $defaults = array();
    $financialTrxn = CRM_Core_BAO_FinancialTrxn::retrieve($trxnArray, $defaults);
    $this->assertEquals(2, $financialTrxn->N, 'Mismatch count for is payment flag.');
    $trxnArray['is_payment'] = 1;
    $financialTrxn = CRM_Core_BAO_FinancialTrxn::retrieve($trxnArray, $defaults);
    $this->assertEquals(NULL, $financialTrxn, 'Mismatch count for is payment flag.');
    //update contribution amount
    $params['id'] = $contribution['id'];
    $params['contribution_status_id'] = 1;

    $contribution = $this->callAPISuccess('Contribution', 'create', $params)['values'][0];

    $this->assertEquals($params['trxn_id'], $contribution['trxn_id'], 'Check for transcation id .');
    $this->assertEquals($params['contribution_status_id'], $contribution['contribution_status_id'], 'Check for status updation.');
    $trxnArray = array(
      'trxn_id' => $params['trxn_id'],
      'is_payment' => 1,
    );
    $defaults = array();
    $financialTrxn = CRM_Core_BAO_FinancialTrxn::retrieve($trxnArray, $defaults);
    $this->assertEquals(1, $financialTrxn->N, 'Mismatch count for is payment flag.');
    $trxnArray['is_payment'] = 0;
    $financialTrxn = CRM_Core_BAO_FinancialTrxn::retrieve($trxnArray, $defaults);
    $this->assertEquals(2, $financialTrxn->N, 'Mismatch count for is payment flag.');
  }

  /**
   * addPayments() method (add and edit modes of participant)
   */
  public function testAddPayments() {
    list($lineItems, $contribution) = $this->addParticipantWithContribution();
    CRM_Contribute_BAO_Contribution::addPayments([$contribution]);
    $this->checkItemValues($contribution);
  }

  /**
   * checks db values for financial item
   */
  public function checkItemValues($contribution) {
    $toFinancialAccount = CRM_Contribute_PseudoConstant::getRelationalFinancialAccount(4, 'Accounts Receivable Account is');
    $query = "SELECT eft1.entity_id, ft.total_amount, eft1.amount FROM civicrm_financial_trxn ft INNER JOIN civicrm_entity_financial_trxn eft ON (eft.financial_trxn_id = ft.id AND eft.entity_table = 'civicrm_contribution')
INNER JOIN civicrm_entity_financial_trxn eft1 ON (eft1.financial_trxn_id = eft.financial_trxn_id AND eft1.entity_table = 'civicrm_financial_item')
WHERE eft.entity_id = %1 AND ft.to_financial_account_id <> %2";

    $queryParams[1] = array($contribution->id, 'Integer');
    $queryParams[2] = array($toFinancialAccount, 'Integer');

    $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
    $amounts = array(100.00, 50.00);
    while ($dao->fetch()) {
      $this->assertEquals(150.00, $dao->total_amount, 'Mismatch of total amount paid.');
      $this->assertEquals($dao->amount, array_pop($amounts), 'Mismatch of amount proportionally assigned to financial item');
    }
  }

  /**
   * assignProportionalLineItems() method (add and edit modes of participant)
   */
  public function testAssignProportionalLineItems() {
    list($lineItems, $contribution) = $this->addParticipantWithContribution();
    $params = array(
      'contribution_id' => $contribution->id,
      'total_amount' => 150.00,
    );
    $trxn = new CRM_Financial_DAO_FinancialTrxn();
    $trxn->orderBy('id DESC');
    $trxn->find(TRUE);
    CRM_Contribute_BAO_Contribution::assignProportionalLineItems($params, $trxn->id, $contribution->total_amount);
    $this->checkItemValues($contribution);
  }

  /**
   * Add participant with contribution
   *
   * @return array
   */
  public function addParticipantWithContribution() {
    // creating price set, price field
    $this->_contactId = $this->individualCreate();
    $event = $this->eventCreate();
    $this->_eventId = $event['id'];
    $paramsSet['title'] = 'Price Set' . substr(sha1(rand()), 0, 4);
    $paramsSet['name'] = CRM_Utils_String::titleToVar($paramsSet['title']);
    $paramsSet['is_active'] = TRUE;
    $paramsSet['financial_type_id'] = 4;
    $paramsSet['extends'] = 1;

    $priceset = CRM_Price_BAO_PriceSet::create($paramsSet);
    $priceSetId = $priceset->id;

    //Checking for priceset added in the table.
    $this->assertDBCompareValue('CRM_Price_BAO_PriceSet', $priceSetId, 'title',
      'id', $paramsSet['title'], 'Check DB for created priceset'
    );
    $paramsField = array(
      'label' => 'Price Field',
      'name' => CRM_Utils_String::titleToVar('Price Field'),
      'html_type' => 'CheckBox',
      'option_label' => array('1' => 'Price Field 1', '2' => 'Price Field 2'),
      'option_value' => array('1' => 100, '2' => 200),
      'option_name' => array('1' => 'Price Field 1', '2' => 'Price Field 2'),
      'option_weight' => array('1' => 1, '2' => 2),
      'option_amount' => array('1' => 100, '2' => 200),
      'is_display_amounts' => 1,
      'weight' => 1,
      'options_per_line' => 1,
      'is_active' => array('1' => 1, '2' => 1),
      'price_set_id' => $priceset->id,
      'is_enter_qty' => 1,
      'financial_type_id' => CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialType', 'Event Fee', 'id', 'name'),
    );
    $priceField = CRM_Price_BAO_PriceField::create($paramsField);
    $eventParams = array(
      'id' => $this->_eventId,
      'financial_type_id' => 4,
      'is_monetary' => 1,
    );
    CRM_Event_BAO_Event::create($eventParams);
    CRM_Price_BAO_PriceSet::addTo('civicrm_event', $this->_eventId, $priceSetId);

    $priceFields = $this->callAPISuccess('PriceFieldValue', 'get', array('price_field_id' => $priceField->id));
    $participantParams = array(
      'financial_type_id' => 4,
      'event_id' => $this->_eventId,
      'role_id' => 1,
      'status_id' => 14,
      'fee_currency' => 'USD',
      'contact_id' => $this->_contactId,
    );
    $participant = CRM_Event_BAO_Participant::add($participantParams);
    $contributionParams = array(
      'total_amount' => 150,
      'currency' => 'USD',
      'contact_id' => $this->_contactId,
      'financial_type_id' => 4,
      'contribution_status_id' => 1,
      'partial_payment_total' => 300.00,
      'partial_amount_to_pay' => 150,
      'contribution_mode' => 'participant',
      'participant_id' => $participant->id,
      'sequential' => TRUE,
    );

    foreach ($priceFields['values'] as $key => $priceField) {
      $lineItems[1][$key] = array(
        'price_field_id' => $priceField['price_field_id'],
        'price_field_value_id' => $priceField['id'],
        'label' => $priceField['label'],
        'field_title' => $priceField['label'],
        'qty' => 1,
        'unit_price' => $priceField['amount'],
        'line_total' => $priceField['amount'],
        'financial_type_id' => $priceField['financial_type_id'],
      );
    }
    $contributionParams['line_item'] = $lineItems;
    $contribution = $this->callAPISuccess('Contribution', 'create', $contributionParams)['values'][0];

    $paymentParticipant = array(
      'participant_id' => $participant->id,
      'contribution_id' => $contribution['id'],
    );
    CRM_Event_BAO_ParticipantPayment::create($paymentParticipant);

    $contributionObject = new CRM_Contribute_BAO_Contribution();
    $contributionObject->id = $contribution['id'];
    $contributionObject->find(TRUE);

    return array($lineItems, $contributionObject);
  }

  /**
   * checkLineItems() check if total amount matches the sum of line total
   */
  public function testcheckLineItems() {
    $params = array(
      'contact_id' => 202,
      'receive_date' => '2010-01-20',
      'total_amount' => 100,
      'financial_type_id' => 3,
      'line_items' => array(
        array(
          'line_item' => array(
            array(
              'entity_table' => 'civicrm_contribution',
              'price_field_id' => 8,
              'price_field_value_id' => 16,
              'label' => 'test 1',
              'qty' => 1,
              'unit_price' => 100,
              'line_total' => 100,
            ),
            array(
              'entity_table' => 'civicrm_contribution',
              'price_field_id' => 8,
              'price_field_value_id' => 17,
              'label' => 'Test 2',
              'qty' => 1,
              'unit_price' => 200,
              'line_total' => 200,
              'financial_type_id' => 1,
            ),
          ),
          'params' => array(),
        ),
      ),
    );

    try {
      CRM_Contribute_BAO_Contribution::checkLineItems($params);
      $this->fail("Missed expected exception");
    }
    catch (CRM_Contribute_Exception_CheckLineItemsException $e) {
      $this->assertEquals(
        CRM_Contribute_Exception_CheckLineItemsException::LINE_ITEM_DIFFERRING_TOTAL_EXCEPTON_MSG,
        $e->getMessage()
      );
    }

    $this->assertEquals(3, $params['line_items'][0]['line_item'][0]['financial_type_id']);
    $params['total_amount'] = 300;

    CRM_Contribute_BAO_Contribution::checkLineItems($params);
  }

  /**
   * Tests CRM_Contribute_BAO_Contribution::checkLineItems() method works with
   * floating point values.
   */
  public function testCheckLineItemsWithFloatingPointValues() {
    $params = array(
      'contact_id' => 202,
      'receive_date' => date('Y-m-d'),
      'total_amount' => 16.67,
      'financial_type_id' => 3,
      'line_items' => array(
        array(
          'line_item' => array(
            array(
              'entity_table' => 'civicrm_contribution',
              'price_field_id' => 8,
              'price_field_value_id' => 16,
              'label' => 'test 1',
              'qty' => 1,
              'unit_price' => 14.85,
              'line_total' => 14.85,
            ),
            array(
              'entity_table' => 'civicrm_contribution',
              'price_field_id' => 8,
              'price_field_value_id' => 17,
              'label' => 'Test 2',
              'qty' => 1,
              'unit_price' => 1.66,
              'line_total' => 1.66,
              'financial_type_id' => 1,
            ),
            array(
              'entity_table' => 'civicrm_contribution',
              'price_field_id' => 8,
              'price_field_value_id' => 17,
              'label' => 'Test 2',
              'qty' => 1,
              'unit_price' => 0.16,
              'line_total' => 0.16,
              'financial_type_id' => 1,
            ),
          ),
          'params' => array(),
        ),
      ),
    );

    $foundException = FALSE;

    try {
      CRM_Contribute_BAO_Contribution::checkLineItems($params);
    }
    catch (CRM_Contribute_Exception_CheckLineItemsException $e) {
      $foundException = TRUE;
    }

    $this->assertFalse($foundException);
  }

  /**
   * Test activity amount updation.
   */
  public function testActivityCreate() {
    $contactId = $this->individualCreate();
    $defaults = array();

    $params = array(
      'contact_id' => $contactId,
      'currency' => 'USD',
      'financial_type_id' => 1,
      'contribution_status_id' => 1,
      'payment_instrument_id' => 1,
      'source' => 'STUDENT',
      'receive_date' => '20080522000000',
      'receipt_date' => '20080522000000',
      'non_deductible_amount' => 0.00,
      'total_amount' => 100.00,
      'trxn_id' => '22ereerwww444444',
      'invoice_id' => '86ed39c9e9ee6ef6031621ce0eafe7eb81',
      'thankyou_date' => '20160519',
      'sequential' => 1,
    );

    $contribution = $this->callAPISuccess('Contribution', 'create', $params)['values'][0];

    $this->assertEquals($params['total_amount'], $contribution['total_amount'], 'Check for total amount in contribution.');
    $this->assertEquals($contactId, $contribution['contact_id'], 'Check for contact id  creation.');

    // Check amount in activity.
    $activityParams = array(
      'source_record_id' => $contribution['id'],
      'activity_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Contribution'),
    );
    // @todo use api instead.
    $activity = CRM_Activity_BAO_Activity::retrieve($activityParams, $defaults);

    $this->assertEquals($contribution['id'], $activity->source_record_id, 'Check for activity associated with contribution.');
    $this->assertEquals("$ 100.00 - STUDENT", $activity->subject, 'Check for total amount in activity.');

    $params['id'] = $contribution['id'];
    $params['total_amount'] = 200;

    $contribution = $this->callAPISuccess('Contribution', 'create', $params)['values'][0];

    $this->assertEquals($params['total_amount'], $contribution['total_amount'], 'Check for total amount in contribution.');
    $this->assertEquals($contactId, $contribution['contact_id'], 'Check for contact id  creation.');

    // Retrieve activity again.
    $activity = CRM_Activity_BAO_Activity::retrieve($activityParams, $defaults);

    $this->assertEquals($contribution['id'], $activity->source_record_id, 'Check for activity associated with contribution.');
    $this->assertEquals("$ 200.00 - STUDENT", $activity->subject, 'Check for total amount in activity.');
  }

  /**
   * Test checkContributeSettings.
   */
  public function testCheckContributeSettings() {
    $settings = CRM_Contribute_BAO_Contribution::checkContributeSettings('deferred_revenue_enabled');
    $this->assertNull($settings);
    $params = array(
      'contribution_invoice_settings' => array(
        'deferred_revenue_enabled' => '1',
      ),
    );
    $this->callAPISuccess('Setting', 'create', $params);
    $settings = CRM_Contribute_BAO_Contribution::checkContributeSettings('deferred_revenue_enabled');
    $this->assertEquals($settings, 1, 'Check for settings has failed');
  }

  /**
   * Test allowUpdateRevenueRecognitionDate.
   */
  public function testAllowUpdateRevenueRecognitionDate() {
    $contactId = $this->individualCreate();
    $params = array(
      'contact_id' => $contactId,
      'receive_date' => '2010-01-20',
      'total_amount' => 100,
      'financial_type_id' => 4,
    );
    $order = $this->callAPISuccess('order', 'create', $params);
    $allowUpdate = CRM_Contribute_BAO_Contribution::allowUpdateRevenueRecognitionDate($order['id']);
    $this->assertTrue($allowUpdate);

    $event = $this->eventCreate();
    $params = array(
      'contact_id' => $contactId,
      'receive_date' => '2010-01-20',
      'total_amount' => 300,
      'financial_type_id' => $this->getFinancialTypeId('Event Fee'),
      'contribution_status_id' => 'Completed',
    );
    $priceFields = $this->createPriceSet('event', $event['id']);
    foreach ($priceFields['values'] as $key => $priceField) {
      $lineItems[$key] = array(
        'price_field_id' => $priceField['price_field_id'],
        'price_field_value_id' => $priceField['id'],
        'label' => $priceField['label'],
        'field_title' => $priceField['label'],
        'qty' => 1,
        'unit_price' => $priceField['amount'],
        'line_total' => $priceField['amount'],
        'financial_type_id' => $priceField['financial_type_id'],
        'entity_table' => 'civicrm_participant',
      );
    }
    $params['line_items'][] = array(
      'line_item' => $lineItems,
      'params' => array(
        'contact_id' => $contactId,
        'event_id' => $event['id'],
        'status_id' => 1,
        'role_id' => 1,
        'register_date' => '2007-07-21 00:00:00',
        'source' => 'Online Event Registration: API Testing',
      ),
    );
    $order = $this->callAPISuccess('order', 'create', $params);
    $allowUpdate = CRM_Contribute_BAO_Contribution::allowUpdateRevenueRecognitionDate($order['id']);
    $this->assertFalse($allowUpdate);

    $params = array(
      'contact_id' => $contactId,
      'receive_date' => '2010-01-20',
      'total_amount' => 200,
      'financial_type_id' => $this->getFinancialTypeId('Member Dues'),
      'contribution_status_id' => 'Completed',
    );
    $membershipType = $this->membershipTypeCreate();
    $priceFields = $this->createPriceSet();
    $lineItems = array();
    foreach ($priceFields['values'] as $key => $priceField) {
      $lineItems[$key] = array(
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
      );
    }
    $params['line_items'][] = array(
      'line_item' => array(array_pop($lineItems)),
      'params' => array(
        'contact_id' => $contactId,
        'membership_type_id' => $membershipType,
        'join_date' => '2006-01-21',
        'start_date' => '2006-01-21',
        'end_date' => '2006-12-21',
        'source' => 'Payment',
        'is_override' => 1,
        'status_id' => 1,
      ),
    );
    $order = $this->callAPISuccess('order', 'create', $params);
    $allowUpdate = CRM_Contribute_BAO_Contribution::allowUpdateRevenueRecognitionDate($order['id']);
    $this->assertFalse($allowUpdate);
  }

  /**
   * Test calculateFinancialItemAmount().
   */
  public function testcalculateFinancialItemAmount() {
    $testParams = array(
      array(
        'params' => array(),
        'amountParams' => array(
          'line_total' => 100,
          'previous_line_total' => 300,
          'diff' => 1,
        ),
        'context' => 'changedAmount',
        'expectedItemAmount' => -200,
      ),
      array(
        'params' => array(),
        'amountParams' => array(
          'line_total' => 100,
          'previous_line_total' => 100,
          'diff' => -1,
        ),
        'context' => 'changePaymentInstrument',
        'expectedItemAmount' => -100,
      ),
      array(
        'params' => array(
          'is_quick_config' => TRUE,
          'total_amount' => 110,
          'tax_amount' => 10,
        ),
        'amountParams' => array(
          'item_amount' => 100,
        ),
        'context' => 'changedAmount',
        'expectedItemAmount' => 100,
      ),
      array(
        'params' => array(
          'is_quick_config' => TRUE,
          'total_amount' => 110,
          'tax_amount' => 10,
        ),
        'amountParams' => array(
          'item_amount' => NULL,
        ),
        'context' => 'changedAmount',
        'expectedItemAmount' => 110,
      ),
      array(
        'params' => array(
          'is_quick_config' => TRUE,
          'total_amount' => 110,
          'tax_amount' => 10,
        ),
        'amountParams' => array(
          'item_amount' => NULL,
        ),
        'context' => NULL,
        'expectedItemAmount' => 100,
      ),
    );
    foreach ($testParams as $params) {
      $itemAmount = CRM_Contribute_BAO_Contribution::calculateFinancialItemAmount($params['params'], $params['amountParams'], $params['context']);
      $this->assertEquals($itemAmount, $params['expectedItemAmount'], 'Invalid Financial Item amount.');
    }
  }

  /**
   * Test recording of amount with comma separator.
   */
  public function testCommaSeparatorAmount() {
    $contactId = $this->individualCreate();

    $params = array(
      'contact_id' => $contactId,
      'currency' => 'USD',
      'financial_type_id' => 1,
      'contribution_status_id' => 8,
      'payment_instrument_id' => 1,
      'receive_date' => '20080522000000',
      'receipt_date' => '20080522000000',
      'total_amount' => '20000.00',
      'partial_payment_total' => '20,000.00',
      'partial_amount_to_pay' => '8,000.00',
    );

    $contribution = $this->callAPISuccess('Contribution', 'create', $params);
    $lastFinancialTrxnId = CRM_Core_BAO_FinancialTrxn::getFinancialTrxnId($contribution['id'], 'DESC');
    $financialTrxn = $this->callAPISuccessGetSingle(
      'FinancialTrxn',
      array(
        'id' => $lastFinancialTrxnId['financialTrxnId'],
        'return' => array('total_amount'),
      )
    );
    $this->assertEquals($financialTrxn['total_amount'], 8000, 'Invalid Tax amount.');
  }

  /**
   * Test for function getSalesTaxFinancialAccounts().
   */
  public function testgetSalesTaxFinancialAccounts() {
    $this->enableTaxAndInvoicing();
    $financialType = $this->createFinancialType();
    $financialAccount = $this->relationForFinancialTypeWithFinancialAccount($financialType['id']);
    $expectedResult = array($financialAccount->financial_account_id => $financialAccount->financial_account_id);
    $financialType = $this->createFinancialType();
    $financialAccount = $this->relationForFinancialTypeWithFinancialAccount($financialType['id']);
    $expectedResult[$financialAccount->financial_account_id] = $financialAccount->financial_account_id;
    $salesTaxFinancialAccount = CRM_Contribute_BAO_Contribution::getSalesTaxFinancialAccounts();
    $this->assertTrue(($salesTaxFinancialAccount == $expectedResult), 'Function returned wrong values.');
  }

  /**
   * Test for function createProportionalEntry().
   *
   * @param string $thousandSeparator
   *   punctuation used to refer to thousands.
   *
   * @dataProvider getThousandSeparators
   */
  public function testCreateProportionalEntry($thousandSeparator) {
    $this->setCurrencySeparators($thousandSeparator);
    list($contribution, $financialAccount) = $this->createContributionWithTax();
    $params = array(
      'total_amount' => 55,
      'to_financial_account_id' => $financialAccount->financial_account_id,
      'payment_instrument_id' => 1,
      'trxn_date' => date('Ymd'),
      'status_id' => 1,
      'entity_id' => $contribution['id'],
    );
    $financialTrxn = $this->callAPISuccess('FinancialTrxn', 'create', $params);
    $entityParams = array(
      'contribution_total_amount' => $contribution['total_amount'],
      'trxn_total_amount' => 55,
      'line_item_amount' => 100,
    );
    $previousLineItem = CRM_Financial_BAO_FinancialItem::getPreviousFinancialItem($contribution['id']);
    $eftParams = array(
      'entity_table' => 'civicrm_financial_item',
      'entity_id' => $previousLineItem['id'],
      'financial_trxn_id' => (string) $financialTrxn['id'],
    );
    CRM_Contribute_BAO_Contribution::createProportionalEntry($entityParams, $eftParams);
    $trxnTestArray = array_merge($eftParams, array(
      'amount' => '50.00',
    ));
    $this->callAPISuccessGetSingle('EntityFinancialTrxn', $eftParams, $trxnTestArray);
  }

  /**
   * Test for function createProportionalEntry with zero amount().
   *
   * @param string $thousandSeparator
   *   punctuation used to refer to thousands.
   *
   * @dataProvider getThousandSeparators
   */
  public function testCreateProportionalEntryZeroAmount($thousandSeparator) {
    $this->setCurrencySeparators($thousandSeparator);
    list($contribution, $financialAccount) = $this->createContributionWithTax(array('total_amount' => 0));
    $params = array(
      'total_amount' => 0,
      'to_financial_account_id' => $financialAccount->financial_account_id,
      'payment_instrument_id' => 1,
      'trxn_date' => date('Ymd'),
      'status_id' => 1,
      'entity_id' => $contribution['id'],
    );
    $financialTrxn = $this->callAPISuccess('FinancialTrxn', 'create', $params);
    $entityParams = array(
      'contribution_total_amount' => $contribution['total_amount'],
      'trxn_total_amount' => 0,
      'line_item_amount' => 0,
    );
    $previousLineItem = CRM_Financial_BAO_FinancialItem::getPreviousFinancialItem($contribution['id']);
    $eftParams = array(
      'entity_table' => 'civicrm_financial_item',
      'entity_id' => $previousLineItem['id'],
      'financial_trxn_id' => (string) $financialTrxn['id'],
    );
    CRM_Contribute_BAO_Contribution::createProportionalEntry($entityParams, $eftParams);
    $trxnTestArray = array_merge($eftParams, array(
      'amount' => '0.00',
    ));
    $this->callAPISuccessGetSingle('EntityFinancialTrxn', $eftParams, $trxnTestArray);
  }

  /**
   * Test for function getLastFinancialItemIds().
   */
  public function testgetLastFinancialItemIds() {
    list($contribution, $financialAccount) = $this->createContributionWithTax();
    list($ftIds, $taxItems) = CRM_Contribute_BAO_Contribution::getLastFinancialItemIds($contribution['id']);
    $this->assertEquals(count($ftIds), 1, 'Invalid count.');
    $this->assertEquals(count($taxItems), 1, 'Invalid count.');
    foreach ($taxItems as $value) {
      $this->assertEquals($value['amount'], 10, 'Invalid tax amount.');
    }
  }

  /**
   * Test for function createProportionalFinancialEntries().
   */
  public function testcreateProportionalFinancialEntries() {
    list($contribution, $financialAccount) = $this->createContributionWithTax();
    $params = array(
      'total_amount' => 50,
      'to_financial_account_id' => $financialAccount->financial_account_id,
      'payment_instrument_id' => 1,
      'trxn_date' => date('Ymd'),
      'status_id' => 1,
      'entity_id' => $contribution['id'],
    );
    $financialTrxn = $this->callAPISuccess('FinancialTrxn', 'create', $params);
    $entityParams = array(
      'contribution_total_amount' => $contribution['total_amount'],
      'trxn_total_amount' => 55,
      'trxn_id' => $financialTrxn['id'],
    );
    $lineItems = CRM_Price_BAO_LineItem::getLineItemsByContributionID($contribution['id']);
    list($ftIds, $taxItems) = CRM_Contribute_BAO_Contribution::getLastFinancialItemIds($contribution['id']);
    CRM_Contribute_BAO_Contribution::createProportionalFinancialEntries($entityParams, $lineItems, $ftIds, $taxItems);
    $eftParams = array(
      'entity_table' => 'civicrm_financial_item',
      'financial_trxn_id' => $financialTrxn['id'],
    );
    $entityFinancialTrxn = $this->callAPISuccess('EntityFinancialTrxn', 'Get', $eftParams);
    $this->assertEquals($entityFinancialTrxn['count'], 2, 'Invalid count.');
    $testAmount = array(5, 50);
    foreach ($entityFinancialTrxn['values'] as $value) {
      $this->assertEquals($value['amount'], array_pop($testAmount), 'Invalid amount stored in civicrm_entity_financial_trxn.');
    }
  }

  /**
   * Test to check if amount is proportionally asigned for PI change.
   */
  public function testProportionallyAssignedForPIChange() {
    list($contribution, $financialAccount) = $this->createContributionWithTax();
    $params = array(
      'id' => $contribution['id'],
      'payment_instrument_id' => 3,
    );
    $this->callAPISuccess('Contribution', 'create', $params);
    $lastFinancialTrxnId = CRM_Core_BAO_FinancialTrxn::getFinancialTrxnId($contribution['id'], 'DESC');
    $eftParams = array(
      'entity_table' => 'civicrm_financial_item',
      'financial_trxn_id' => $lastFinancialTrxnId['financialTrxnId'],
    );
    $entityFinancialTrxn = $this->callAPISuccess('EntityFinancialTrxn', 'Get', $eftParams);
    $this->assertEquals($entityFinancialTrxn['count'], 2, 'Invalid count.');
    $testAmount = array(10, 100);
    foreach ($entityFinancialTrxn['values'] as $value) {
      $this->assertEquals($value['amount'], array_pop($testAmount), 'Invalid amount stored in civicrm_entity_financial_trxn.');
    }
  }

  /**
   * Function to create contribution with tax.
   */
  public function createContributionWithTax($params = array()) {
    if (!isset($params['total_amount'])) {
      $params['total_amount'] = 100;
    }
    $contactId = $this->individualCreate();
    $this->enableTaxAndInvoicing();
    $financialType = $this->createFinancialType();
    $financialAccount = $this->relationForFinancialTypeWithFinancialAccount($financialType['id']);
    $form = new CRM_Contribute_Form_Contribution();

    $form->testSubmit(array(
      'total_amount' => $params['total_amount'],
      'financial_type_id' => $financialType['id'],
      'contact_id' => $contactId,
      'contribution_status_id' => 1,
      'price_set_id' => 0,
    ), CRM_Core_Action::ADD);
    $contribution = $this->callAPISuccessGetSingle('Contribution',
      array(
        'contact_id' => $contactId,
        'return' => array('tax_amount', 'total_amount'),
      )
    );
    return array($contribution, $financialAccount);
  }

  /**
   * Test processOnBehalfOrganization() function.
   */
  public function testProcessOnBehalfOrganization() {
    $orgInfo = array(
      'phone' => '11111111',
      'email' => 'testorg@gmail.com',
      'street_address' => 'test Street',
      'city' => 'test City',
      'state_province' => 'AA',
      'postal_code' => '222222',
      'country' => 'United States',
    );
    $contactID = $this->individualCreate();
    $orgId = $this->organizationCreate(array('organization_name' => 'testorg1'));
    $orgCount = $this->callAPISuccessGetCount('Contact', array(
      'contact_type' => "Organization",
      'organization_name' => "testorg1",
    ));
    $this->assertEquals($orgCount, 1);

    $values = $params = array();
    $behalfOrganization = array(
      'organization_name' => 'testorg1',
      'phone' => array(
        1 => array(
          'phone' => $orgInfo['phone'],
          'is_primary' => 1,
        ),
      ),
      'email' => array(
        1 => array(
          'email' => $orgInfo['email'],
          'is_primary' => 1,
        ),
      ),
      'address' => array(
        3 => array(
          'street_address' => $orgInfo['street_address'],
          'city' => $orgInfo['city'],
          'location_type_id' => 3,
          'postal_code' => $orgInfo['postal_code'],
          'country' => 'US',
          'state_province' => 'AA',
          'is_primary' => 1,
        ),
      ),
    );
    $fields = array(
      'organization_name' => 1,
      'phone-3-1' => 1,
      'email-3' => 1,
      'street_address-3' => 1,
      'city-3' => 1,
      'postal_code-3' => 1,
      'country-3' => 1,
      'state_province-3' => 1,
    );
    CRM_Contribute_Form_Contribution_Confirm::processOnBehalfOrganization($behalfOrganization, $contactID, $values, $params, $fields);

    //Check whether new organisation is not created.
    $result = $this->callAPISuccess('Contact', 'get', array(
      'contact_type' => "Organization",
      'organization_name' => "testorg1",
    ));
    $this->assertEquals($result['count'], 1);

    //Assert all org values are updated.
    foreach ($orgInfo as $key => $val) {
      $this->assertEquals($result['values'][$orgId][$key], $val);
    }

    //Check if alert is assigned to params if more than 1 dupe exists.
    $orgId = $this->organizationCreate(array('organization_name' => 'testorg1', 'email' => 'testorg@gmail.com'));
    CRM_Contribute_Form_Contribution_Confirm::processOnBehalfOrganization($behalfOrganization, $contactID, $values, $params, $fields);
    $this->assertEquals($params['onbehalf_dupe_alert'], 1);
  }

  /**
   * Test for replaceContributionTokens.
   *   This function tests whether the contribution tokens are replaced with values from contribution.
   */
  public function testReplaceContributionTokens() {
    $contactId1 = $this->individualCreate();
    $params = array(
      'contact_id' => $contactId1,
      'receive_date' => '20120511',
      'total_amount' => 100.00,
      'financial_type_id' => 1,
      'trxn_id' => 12345,
      'invoice_id' => 67890,
      'source' => 'SSF',
      'contribution_status_id' => 2,
    );
    $contribution1 = $this->contributionCreate($params);
    $contactId2 = $this->individualCreate();
    $params = array(
      'contact_id' => $contactId2,
      'receive_date' => '20150511',
      'total_amount' => 200.00,
      'financial_type_id' => 1,
      'trxn_id' => 6789,
      'invoice_id' => 12345,
      'source' => 'ABC',
      'contribution_status_id' => 1,
    );
    $contribution2 = $this->contributionCreate($params);
    $ids = array($contribution1, $contribution2);

    $subject = "This is a test for contribution ID: {contribution.contribution_id}";
    $text = "Contribution Amount: {contribution.total_amount}";
    $html = "<p>Contribution Source: {contribution.contribution_source}</p></br>
      <p>Contribution Invoice ID: {contribution.invoice_id}</p></br>
      <p>Contribution Receive Date: {contribution.receive_date}</p></br>";

    $subjectToken = CRM_Utils_Token::getTokens($subject);
    $messageToken = CRM_Utils_Token::getTokens($text);
    $messageToken = array_merge($messageToken, CRM_Utils_Token::getTokens($html));

    $contributionDetails = CRM_Contribute_BAO_Contribution::replaceContributionTokens(
      $ids,
      $subject,
      $subjectToken,
      $text,
      $html,
      $messageToken,
      TRUE
    );

    $this->assertEquals("Contribution Amount: $ 100.00", $contributionDetails[$contactId1]['text'], "The text does not match");
    $this->assertEquals("<p>Contribution Source: ABC</p></br>
      <p>Contribution Invoice ID: 12345</p></br>
      <p>Contribution Receive Date: May 11th, 2015</p></br>", $contributionDetails[$contactId2]['html'], "The html does not match");
  }

  /**
   * Test for contribution with deferred revenue.
   */
  public function testContributionWithDeferredRevenue() {
    $contactId = $this->individualCreate();
    Civi::settings()->set('deferred_revenue_enabled', TRUE);
    $params = array(
      'contact_id' => $contactId,
      'receive_date' => '20120511',
      'total_amount' => 100.00,
      'financial_type_id' => 'Event Fee',
      'trxn_id' => 12345,
      'invoice_id' => 67890,
      'source' => 'SSF',
      'contribution_status_id' => 'Completed',
      'revenue_recognition_date' => date('Ymd', strtotime("+3 month")),
    );
    $contribution = $this->callAPISuccess('contribution', 'create', $params);

    $this->callAPISuccessGetCount('EntityFinancialTrxn', array(
      'entity_table' => "civicrm_contribution",
      'entity_id' => $contribution['id'],
    ), 2);

    $checkAgainst = array(
      'financial_trxn_id.to_financial_account_id.name' => 'Deferred Revenue - Event Fee',
      'financial_trxn_id.from_financial_account_id.name' => 'Event Fee',
      'financial_trxn_id' => '2',
    );
    $result = $this->callAPISuccessGetSingle('EntityFinancialTrxn', array(
      'return' => array(
        "financial_trxn_id.from_financial_account_id.name",
        "financial_trxn_id.to_financial_account_id.name",
        "financial_trxn_id",
      ),
      'entity_table' => "civicrm_contribution",
      'entity_id' => $contribution['id'],
      'financial_trxn_id.is_payment' => 0,
    ), $checkAgainst);

    $result = $this->callAPISuccessGetSingle('EntityFinancialTrxn', array(
      'entity_table' => "civicrm_financial_item",
      'financial_trxn_id' => $result['financial_trxn_id'],
      'return' => array('entity_id'),
    ));

    $checkAgainst = array(
      'financial_account_id.name' => 'Deferred Revenue - Event Fee',
      'id' => $result['entity_id'],
    );
    $result = $this->callAPISuccessGetSingle('FinancialItem', array(
      'id' => $result['entity_id'],
      'return' => array("financial_account_id.name"),
    ), $checkAgainst);
  }

  /**
   *  CRM-21424 Check if the receipt update is set after composing the receipt message
   */
  public function testSendMailUpdateReceiptDate() {
    $ids = $values = array();
    $contactId = $this->individualCreate();
    $params = array(
      'contact_id' => $contactId,
      'receive_date' => '20120511',
      'total_amount' => 100.00,
      'financial_type_id' => 'Donation',
      'source' => 'SSF',
      'contribution_status_id' => 'Completed',
    );
    /* first test the scenario when sending an email */
    $contribution = $this->callAPISuccess('contribution', 'create', $params);
    $contributionId = $contribution['id'];
    $this->assertDBNull('CRM_Contribute_BAO_Contribution', $contributionId, 'receipt_date', 'id', 'After creating receipt date must be null');
    $input = array('receipt_update' => 0);
    CRM_Contribute_BAO_Contribution::sendMail($input, $ids, $contributionId, $values);
    $this->assertDBNull('CRM_Contribute_BAO_Contribution', $contributionId, 'receipt_date', 'id', 'After sendMail, with the explicit instruction not to update receipt date stays null');
    $input = array('receipt_update' => 1);
    CRM_Contribute_BAO_Contribution::sendMail($input, $ids, $contributionId, $values);
    $this->assertDBNotNull('CRM_Contribute_BAO_Contribution', $contributionId, 'receipt_date', 'id', 'After sendMail with the permission to allow update receipt date must be set');

    /* repeat the same scenario for downloading a pdf */
    $contribution = $this->callAPISuccess('contribution', 'create', $params);
    $contributionId = $contribution['id'];
    $this->assertDBNull('CRM_Contribute_BAO_Contribution', $contributionId, 'receipt_date', 'id', 'After creating receipt date must be null');
    $input = array('receipt_update' => 0);
    /* setting the lasast parameter (returnmessagetext) to TRUE is done by the download of the pdf */
    CRM_Contribute_BAO_Contribution::sendMail($input, $ids, $contributionId, $values, TRUE);
    $this->assertDBNull('CRM_Contribute_BAO_Contribution', $contributionId, 'receipt_date', 'id', 'After sendMail, with the explicit instruction not to update receipt date stays null');
    $input = array('receipt_update' => 1);
    CRM_Contribute_BAO_Contribution::sendMail($input, $ids, $contributionId, $values, TRUE);
    $this->assertDBNotNull('CRM_Contribute_BAO_Contribution', $contributionId, 'receipt_date', 'id', 'After sendMail with the permission to allow update receipt date must be set');
  }

}
