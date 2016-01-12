<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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


require_once 'CiviTest/CiviUnitTestCase.php';
require_once 'CiviTest/Contact.php';
require_once 'CiviTest/Custom.php';

/**
 * Class CRM_Contribute_BAO_ContributionTest
 */
class CRM_Contribute_BAO_ContributionTest extends CiviUnitTestCase {

  /**
   * Create() method (create and update modes).
   */
  public function testCreate() {
    $contactId = Contact::createIndividual();
    $ids = array('contribution' => NULL);

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

    $contribution = CRM_Contribute_BAO_Contribution::create($params, $ids);

    $this->assertEquals($params['trxn_id'], $contribution->trxn_id, 'Check for transcation id creation.');
    $this->assertEquals($contactId, $contribution->contact_id, 'Check for contact id  creation.');

    //update contribution amount
    $ids = array('contribution' => $contribution->id);
    $params['fee_amount'] = 10;
    $params['net_amount'] = 190;

    $contribution = CRM_Contribute_BAO_Contribution::create($params, $ids);

    $this->assertEquals($params['trxn_id'], $contribution->trxn_id, 'Check for transcation id .');
    $this->assertEquals($params['net_amount'], $contribution->net_amount, 'Check for Amount updation.');

    //Delete Contribution
    $this->contributionDelete($contribution->id);

    //Delete Contact
    Contact::delete($contactId);
  }

  /**
   * Create() method with custom data.
   */
  public function testCreateWithCustomData() {
    $contactId = Contact::createIndividual();
    $ids = array('contribution' => NULL);

    //create custom data
    $customGroup = Custom::createGroup(array(), 'Contribution');
    $fields = array(
      'label' => 'testFld',
      'data_type' => 'String',
      'html_type' => 'Text',
      'is_active' => 1,
      'custom_group_id' => $customGroup->id,
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
          'custom_group_id' => $customGroup->id,
          'table_name' => $customGroup->table_name,
          'column_name' => $customField->column_name,
          'file_id' => NULL,
        ),
      ),
    );

    $contribution = CRM_Contribute_BAO_Contribution::create($params, $ids);

    // Check that the custom field value is saved
    $customValueParams = array(
      'entityID' => $contribution->id,
      'custom_' . $customField->id => 1,
    );
    $values = CRM_Core_BAO_CustomValueTable::getValues($customValueParams);
    $this->assertEquals('Test custom value', $values['custom_' . $customField->id], 'Check the custom field value');

    $this->assertEquals($params['trxn_id'], $contribution->trxn_id, 'Check for transcation id creation.');
    $this->assertEquals($contactId, $contribution->contact_id, 'Check for contact id for Conribution.');

    $this->contributionDelete($contribution->id);
    Custom::deleteField($customField);
    Custom::deleteGroup($customGroup);
    Contact::delete($contactId);
  }

  /**
   * DeleteContribution() method
   */
  public function testDeleteContribution() {
    $contactId = Contact::createIndividual();
    $ids = array('contribution' => NULL);

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

    $contribution = CRM_Contribute_BAO_Contribution::create($params, $ids);

    $this->assertEquals($params['trxn_id'], $contribution->trxn_id, 'Check for transcation id creation.');
    $this->assertEquals($contactId, $contribution->contact_id, 'Check for contact id  creation.');

    $contributiondelete = CRM_Contribute_BAO_Contribution::deleteContribution($contribution->id);

    $this->assertDBNull('CRM_Contribute_DAO_Contribution', $contribution->trxn_id,
      'id', 'trxn_id', 'Database check for deleted Contribution.'
    );
    Contact::delete($contactId);
  }

  /**
   * Create honor-contact method
   */
  public function testcreateAndGetHonorContact() {
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

    $contactId = Contact::createIndividual();
    $softParam['contact_id'] = $honoreeContactId;

    $ids = array('contribution' => NULL);
    $param = array(
      'contact_id' => $contactId,
      'currency' => 'USD',
      'financial_type_id' => 4,
      'contribution_status_id' => 1,
      'receive_date' => date('Ymd'),
      'total_amount' => 66,
    );

    $contribution = CRM_Contribute_BAO_Contribution::create($param, $ids);
    $id = $contribution->id;
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
        'honorId' => $id,
        'display_name' => 'John Doe',
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

    //Delete honor contact
    Contact::delete($honoreeContactId);

    //Delete Contribution record
    $this->contributionDelete($contribution->id);

    //Delete contributor contact
    Contact::delete($contactId);
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

    //Delete Contribution
    $this->contributionDelete($contribution->id);
    //Delete Contact
    Contact::delete($contactId);
  }

  /**
   * Add premium during online Contribution.
   *
   * AddPremium();
   */
  public function testAddPremium() {
    $contactId = Contact::createIndividual();

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

    //Delete Contribution
    $this->contributionDelete($contribution->id);
    //Delete Contact
    Contact::delete($contactId);
  }

  /**
   * Check duplicate contribution id.
   * during the contribution import
   * checkDuplicateIds();
   */
  public function testcheckDuplicateIds() {
    $contactId = Contact::createIndividual();

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
      'trxn_id' => '76ereeswww835',
      'invoice_id' => '93ed39a9e9hd621bs0eafe3da82',
      'thankyou_date' => '20080522',
    );

    $contribution = CRM_Contribute_BAO_Contribution::create($param, $ids);

    $this->assertEquals($param['trxn_id'], $contribution->trxn_id, 'Check for transcation id creation.');
    $this->assertEquals($contactId, $contribution->contact_id, 'Check for contact id  creation.');
    $data = array(
      'id' => $contribution->id,
      'trxn_id' => $contribution->trxn_id,
      'invoice_id' => $contribution->invoice_id,
    );
    $contributionID = CRM_Contribute_BAO_Contribution::checkDuplicateIds($data);
    $this->assertEquals($contributionID, $contribution->id, 'Check for duplicate transcation id .');

    // Delete Contribution
    $this->contributionDelete($contribution->id);
    // Delete Contact
    Contact::delete($contactId);
  }

  /**
   * Check credit note id creation
   * when a contribution is cancelled or refunded
   * createCreditNoteId();
   */
  public function testCreateCreditNoteId() {
    $contactId = Contact::createIndividual();

    $ids = array('contribution' => NULL);

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
    $contribution = CRM_Contribute_BAO_Contribution::create($param, $ids);
    $this->assertEquals($contactId, $contribution->contact_id, 'Check for contact id  creation.');
    $this->assertEquals($creditNoteId, $contribution->creditnote_id, 'Check if credit note id is created correctly.');

    // Delete Contribution
    $this->contributionDelete($contribution->id);
    // Delete Contact
    Contact::delete($contactId);
  }

  /**
   * Create() method (create and update modes).
   */
  public function testIsPaymentFlag() {
    $contactId = Contact::createIndividual();
    $ids = array('contribution' => NULL);

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

    $contribution = CRM_Contribute_BAO_Contribution::create($params, $ids);

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

    //Delete Contribution
    $this->contributionDelete($contribution->id);

    //Delete Contact
    Contact::delete($contactId);
  }

  /**
   * Create() method (create and update modes).
   */
  public function testIsPaymentFlagForPending() {
    $contactId = Contact::createIndividual();
    $ids = array('contribution' => NULL);

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

    $contribution = CRM_Contribute_BAO_Contribution::create($params, $ids);

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

    //Delete Contribution
    $this->contributionDelete($contribution->id);

    //Delete Contact
    Contact::delete($contactId);
  }

  /**
   * addPayments() method (add and edit modes of participant)
   */
  public function testAddPayments() {
    list($lineItems, $contribution) = $this->addParticipantWithContribution();
    foreach ($lineItems as $value) {
      CRM_Contribute_BAO_Contribution::addPayments($value, array($contribution));
    }
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
    $amounts = array(1 => 50.00, 2 => 100.00);
    while ($dao->fetch()) {
      $this->assertEquals(150.00, $dao->total_amount, 'Mismatch of total amount paid.');
      $this->assertEquals($dao->amount, $amounts[$dao->entity_id], 'Mismatch of amount proportionally assigned to financial item');
    }

    Contact::delete($this->_contactId);
    Event::delete($this->_eventId);
  }

  /**
   * assignProportionalLineItems() method (add and edit modes of participant)
   */
  public function testAssignProportionalLineItems() {
    list($lineItems, $contribution) = $this->addParticipantWithContribution();
    $contributions['total_amount'] = $contribution->total_amount;
    $params = array(
      'contribution_id' => $contribution->id,
      'total_amount' => 150.00,
    );
    $trxn = new CRM_Financial_DAO_FinancialTrxn();
    $trxn->orderBy('id DESC');
    $trxn->find(TRUE);
    CRM_Contribute_BAO_Contribution::assignProportionalLineItems($params, $trxn, $contributions);
    $this->checkItemValues($contribution);
  }

  /**
   * Add participant with contribution
   *
   * @return array
   */
  protected function addParticipantWithContribution() {
    // creating price set, price field
    require_once 'CiviTest/Event.php';
    $this->_contactId = Contact::createIndividual();
    $this->_eventId = Event::create($this->_contactId);
    $paramsSet['title'] = 'Price Set';
    $paramsSet['name'] = CRM_Utils_String::titleToVar('Price Set');
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

}
