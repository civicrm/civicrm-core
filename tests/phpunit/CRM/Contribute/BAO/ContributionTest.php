<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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

  /**
   * Clean up after tests.
   */
  public function tearDown() {
    $this->quickCleanUpFinancialEntities();
    $this->quickCleanUpFinancialEntities(array('civicrm_event'));
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
    );

    $contribution = CRM_Contribute_BAO_Contribution::create($params);

    $this->assertEquals($params['trxn_id'], $contribution->trxn_id, 'Check for transaction id creation.');
    $this->assertEquals($contactId, $contribution->contact_id, 'Check for contact id  creation.');

    //update contribution amount
    $ids = array('contribution' => $contribution->id);
    $params['fee_amount'] = 10;
    $params['net_amount'] = 190;

    $contribution = CRM_Contribute_BAO_Contribution::create($params, $ids);

    $this->assertEquals($params['trxn_id'], $contribution->trxn_id, 'Check for transcation id .');
    $this->assertEquals($params['net_amount'], $contribution->net_amount, 'Check for Amount updation.');
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
    );

    $contribution = CRM_Contribute_BAO_Contribution::create($params);

    $this->assertEquals($params['trxn_id'], $contribution->trxn_id, 'Check for transcation id creation.');
    $this->assertEquals($contactId, $contribution->contact_id, 'Check for contact id  creation.');

    CRM_Contribute_BAO_Contribution::deleteContribution($contribution->id);

    $this->assertDBNull('CRM_Contribute_DAO_Contribution', $contribution->trxn_id,
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
    );

    $contribution = CRM_Contribute_BAO_Contribution::create($param);
    $id = $contribution->id;
    $softParam['contact_id'] = $honoreeContactId;
    $softParam['contribution_id'] = $id;
    $softParam['currency'] = $contribution->currency;
    $softParam['amount'] = $contribution->total_amount;

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

    $config = CRM_Core_Config::singleton();
    $currencySymbol = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_Currency', $config->defaultCurrency, 'symbol', 'name');
    $this->assertDBCompareValue('CRM_Contribute_DAO_Contribution', $id, 'total_amount',
      'id', ltrim($annual[2], $currencySymbol), 'Check DB for total amount of the contribution'
    );
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

    $ids = array('contribution' => NULL);

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
    );

    $contribution = CRM_Contribute_BAO_Contribution::create($param, $ids);

    $this->assertEquals($param['trxn_id'], $contribution->trxn_id, 'Check for transcation id creation.');
    $this->assertEquals($contactId, $contribution->contact_id, 'Check for contact id  creation.');

    //display sort name during Update multiple contributions
    $sortName = CRM_Contribute_BAO_Contribution::sortName($contribution->id);

    $this->assertEquals('Whatson, Shane', $sortName, 'Check for sort name.');
  }

  /**
   * Add premium during online Contribution.
   *
   * AddPremium();
   */
  public function testAddPremium() {
    $contactId = $this->individualCreate();

    $ids = array(
      'premium' => NULL,
    );

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
    $premium = CRM_Contribute_BAO_ManagePremiums::add($params, $ids);

    $this->assertEquals('TEST Premium', $premium->name, 'Check for premium  name.');

    $ids = array('contribution' => NULL);

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
      'trxn_id' => '33erdfrwvw434',
      'invoice_id' => '98ed34f7u9hh672ce0eafe8fb92',
      'thankyou_date' => '20080522',
    );

    $contribution = CRM_Contribute_BAO_Contribution::create($param, $ids);

    $this->assertEquals($param['trxn_id'], $contribution->trxn_id, 'Check for transcation id creation.');
    $this->assertEquals($contactId, $contribution->contact_id, 'Check for contact id  creation.');

    //parameter for adding premium to contribution
    $data = array(
      'product_id' => $premium->id,
      'contribution_id' => $contribution->id,
      'product_option' => NULL,
      'quantity' => 1,
    );
    $contributionProduct = CRM_Contribute_BAO_Contribution::addPremium($data);
    $this->assertEquals($contributionProduct->product_id, $premium->id, 'Check for Product id .');

    //Delete Product
    CRM_Contribute_BAO_ManagePremiums::del($premium->id);
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
    );

    $contribution = CRM_Contribute_BAO_Contribution::create($param);

    $this->assertEquals($param['trxn_id'], $contribution->trxn_id, 'Check for transcation id creation.');
    $this->assertEquals($contactId, $contribution->contact_id, 'Check for contact id  creation.');
    $data = array(
      'id' => $contribution->id,
      'trxn_id' => $contribution->trxn_id,
      'invoice_id' => $contribution->invoice_id,
    );
    $contributionID = CRM_Contribute_BAO_Contribution::checkDuplicateIds($data);
    $this->assertEquals($contributionID, $contribution->id, 'Check for duplicate transcation id .');
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
    );

    $creditNoteId = CRM_Contribute_BAO_Contribution::createCreditNoteId();
    $contribution = CRM_Contribute_BAO_Contribution::create($param);
    $this->assertEquals($contactId, $contribution->contact_id, 'Check for contact id  creation.');
    $this->assertEquals($creditNoteId, $contribution->creditnote_id, 'Check if credit note id is created correctly.');
  }

  /**
   * Create() method (create and update modes).
   */
  public function testIsPaymentFlag() {
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
      'trxn_id' => '22ereerwww4444xx',
      'invoice_id' => '86ed39c9e9ee6ef6541621ce0eafe7eb81',
      'thankyou_date' => '20080522',
    );

    $contribution = CRM_Contribute_BAO_Contribution::create($params);

    $this->assertEquals($params['trxn_id'], $contribution->trxn_id, 'Check for transcation id creation.');
    $this->assertEquals($contactId, $contribution->contact_id, 'Check for contact id  creation.');

    $trxnArray = array(
      'trxn_id' => $params['trxn_id'],
      'is_payment' => 1,
    );
    $defaults = array();
    $financialTrxn = CRM_Core_BAO_FinancialTrxn::retrieve($trxnArray, $defaults);
    $this->assertEquals(2, $financialTrxn->N, 'Mismatch count for is payment flag.');
    //update contribution amount
    $ids = array('contribution' => $contribution->id);
    $params['total_amount'] = 150;

    $contribution = CRM_Contribute_BAO_Contribution::create($params, $ids);

    $this->assertEquals($params['trxn_id'], $contribution->trxn_id, 'Check for transcation id .');
    $this->assertEquals($params['total_amount'], $contribution->total_amount, 'Check for Amount updation.');
    $trxnArray = array(
      'trxn_id' => $params['trxn_id'],
      'is_payment' => 1,
    );
    $defaults = array();
    $financialTrxn = CRM_Core_BAO_FinancialTrxn::retrieve($trxnArray, $defaults);
    $this->assertEquals(3, $financialTrxn->N, 'Mismatch count for is payment flag.');
    $trxnArray['is_payment'] = 0;
    $financialTrxn = CRM_Core_BAO_FinancialTrxn::retrieve($trxnArray, $defaults);
    $this->assertEquals(NULL, $financialTrxn, 'Mismatch count for is payment flag.');
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
    );

    $contribution = CRM_Contribute_BAO_Contribution::create($params);

    $this->assertEquals($params['trxn_id'], $contribution->trxn_id, 'Check for transcation id creation.');
    $this->assertEquals($contactId, $contribution->contact_id, 'Check for contact id  creation.');

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
    $ids = array('contribution' => $contribution->id);
    $params['contribution_status_id'] = 1;

    $contribution = CRM_Contribute_BAO_Contribution::create($params, $ids);

    $this->assertEquals($params['trxn_id'], $contribution->trxn_id, 'Check for transcation id .');
    $this->assertEquals($params['contribution_status_id'], $contribution->contribution_status_id, 'Check for status updation.');
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
    CRM_Contribute_BAO_Contribution::addPayments(array($contribution));
    $this->checkItemValues($contribution);
  }

  /**
   * checks db values for financial item
   */
  public function checkItemValues($contribution) {
    $relationTypeId = key(CRM_Core_PseudoConstant::accountOptionValues('account_relationship', NULL, " AND v.name LIKE 'Accounts Receivable Account is' "));
    $toFinancialAccount = CRM_Contribute_PseudoConstant::financialAccountType(4, $relationTypeId);
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
      'partial_amount_pay' => 150,
      'contribution_mode' => 'participant',
      'participant_id' => $participant->id,
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
    $contributions = CRM_Contribute_BAO_Contribution::create($contributionParams);

    $paymentParticipant = array(
      'participant_id' => $participant->id,
      'contribution_id' => $contributions->id,
    );
    $ids = array();
    CRM_Event_BAO_ParticipantPayment::create($paymentParticipant, $ids);

    return array($lineItems, $contributions);
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
    catch (Exception $e) {
      $this->assertEquals("Line item total doesn't match with total amount.", $e->getMessage());
    }
    $this->assertEquals(3, $params['line_items'][0]['line_item'][0]['financial_type_id']);
    $params['total_amount'] = 300;
    CRM_Contribute_BAO_Contribution::checkLineItems($params);
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
    );

    $contribution = CRM_Contribute_BAO_Contribution::create($params);

    $this->assertEquals($params['total_amount'], $contribution->total_amount, 'Check for total amount in contribution.');
    $this->assertEquals($contactId, $contribution->contact_id, 'Check for contact id  creation.');

    // Check amount in activity.
    $activityParams = array(
      'source_record_id' => $contribution->id,
      'activity_type_id' => CRM_Core_OptionGroup::getValue('activity_type',
        'Contribution',
        'name'
      ),
    );
    $activity = CRM_Activity_BAO_Activity::retrieve($activityParams, $defaults);

    $this->assertEquals($contribution->id, $activity->source_record_id, 'Check for activity associated with contribution.');
    $this->assertEquals("$ 100.00 - STUDENT", $activity->subject, 'Check for total amount in activity.');

    // Update contribution amount.
    $ids = array('contribution' => $contribution->id);
    $params['total_amount'] = 200;

    $contribution = CRM_Contribute_BAO_Contribution::create($params, $ids);

    $this->assertEquals($params['total_amount'], $contribution->total_amount, 'Check for total amount in contribution.');
    $this->assertEquals($contactId, $contribution->contact_id, 'Check for contact id  creation.');

    // Retrieve activity again.
    $activity = CRM_Activity_BAO_Activity::retrieve($activityParams, $defaults);

    $this->assertEquals($contribution->id, $activity->source_record_id, 'Check for activity associated with contribution.');
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

}
