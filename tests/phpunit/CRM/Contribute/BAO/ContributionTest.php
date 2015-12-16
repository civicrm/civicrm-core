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

}
