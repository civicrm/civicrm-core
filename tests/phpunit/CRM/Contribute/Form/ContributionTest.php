<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
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
 *  Test APIv3 civicrm_contribute_* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Contribution
 * @group headless
 */
class CRM_Contribute_Form_ContributionTest extends CiviUnitTestCase {

  /**
   * Assume empty database with just civicrm_data.
   */
  protected $_individualId;
  protected $_contribution;
  protected $_financialTypeId = 1;
  protected $_apiversion;
  protected $_entity = 'Contribution';
  protected $_params;
  protected $_ids = array();
  protected $_pageParams = array();

  /**
   * Parameters to create payment processor.
   *
   * @var array
   */
  protected $_processorParams = array();

  /**
   * ID of created event.
   *
   * @var int
   */
  protected $_eventID;

  /**
   * Payment instrument mapping.
   *
   * @var array
   */
  protected $paymentInstruments = array();

  /**
   * Products.
   *
   * @var array
   */
  protected $products = array();

  /**
   * Dummy payment processor.
   *
   * @var CRM_Core_Payment_Dummy
   */
  protected $paymentProcessor;

  /**
   * Payment processor ID.
   *
   * @var int
   */
  protected $paymentProcessorID;

  /**
   * Setup function.
   */
  public function setUp() {
    $this->_apiversion = 3;
    parent::setUp();
    $this->createLoggedInUser();

    $this->_individualId = $this->individualCreate();
    $this->_params = array(
      'contact_id' => $this->_individualId,
      'receive_date' => '20120511',
      'total_amount' => 100.00,
      'financial_type_id' => $this->_financialTypeId,
      'non_deductible_amount' => 10.00,
      'fee_amount' => 5.00,
      'net_amount' => 95.00,
      'source' => 'SSF',
      'contribution_status_id' => 1,
    );
    $this->_processorParams = array(
      'domain_id' => 1,
      'name' => 'Dummy',
      'payment_processor_type_id' => 10,
      'financial_account_id' => 12,
      'is_active' => 1,
      'user_name' => '',
      'url_site' => 'http://dummy.com',
      'url_recur' => 'http://dummy.com',
      'billing_mode' => 1,
    );

    $instruments = $this->callAPISuccess('contribution', 'getoptions', array('field' => 'payment_instrument_id'));
    $this->paymentInstruments = $instruments['values'];
    $product1 = $this->callAPISuccess('product', 'create', array(
      'name' => 'Smurf',
      'options' => 'brainy smurf, clumsy smurf, papa smurf',
    ));

    $this->products[] = $product1['values'][$product1['id']];
    $this->paymentProcessor = $this->dummyProcessorCreate();
    $processor = $this->paymentProcessor->getPaymentProcessor();
    $this->paymentProcessorID = $processor['id'];
  }

  /**
   * Clean up after each test.
   */
  public function tearDown() {
    $this->quickCleanUpFinancialEntities();
    $this->quickCleanup(array('civicrm_note', 'civicrm_uf_match', 'civicrm_address'));
  }

  /**
   * Test the submit function on the contribution page.
   */
  public function testSubmit() {
    $form = new CRM_Contribute_Form_Contribution();
    $form->testSubmit(array(
      'total_amount' => 50,
      'financial_type_id' => 1,
      'receive_date' => '04/21/2015',
      'receive_date_time' => '11:27PM',
      'contact_id' => $this->_individualId,
      'payment_instrument_id' => array_search('Check', $this->paymentInstruments),
      'contribution_status_id' => 1,
    ),
      CRM_Core_Action::ADD);
    $contribution = $this->callAPISuccessGetSingle('Contribution', array('contact_id' => $this->_individualId));
    $this->assertEmpty($contribution['amount_level']);
  }

  /**
   * Test the submit function on the contribution page.
   */
  public function testSubmitCreditCard() {
    $form = new CRM_Contribute_Form_Contribution();
    $form->testSubmit(array(
      'total_amount' => 50,
      'financial_type_id' => 1,
      'receive_date' => '04/21/2015',
      'receive_date_time' => '11:27PM',
      'contact_id' => $this->_individualId,
      'payment_instrument_id' => array_search('Credit Card', $this->paymentInstruments),
      'contribution_status_id' => 1,
    ), CRM_Core_Action::ADD);
    $this->callAPISuccessGetCount('Contribution', array(
      'contact_id' => $this->_individualId,
      'contribution_status_id' => 'Completed',
      ),
    1);
  }

  /**
   * Test the submit function on the contribution page.
   */
  public function testSubmitCreditCardPayPal() {
    $mut = new CiviMailUtils($this, TRUE);
    $mut->clearMessages();
    $form = new CRM_Contribute_Form_Contribution();
    $paymentProcessorID = $this->paymentProcessorCreate(array('is_test' => 0));
    $form->_mode = 'Live';
    $error = FALSE;
    try {
      $form->testSubmit(array(
        'total_amount' => 50,
        'financial_type_id' => 1,
        'receive_date' => '04/21/2015',
        'receive_date_time' => '11:27PM',
        'contact_id' => $this->_individualId,
        'contribution_status_id' => 1,
        'credit_card_number' => 4444333322221111,
        'cvv2' => 123,
        'credit_card_exp_date' => array(
          'M' => 9,
          'Y' => 2025,
        ),
        'credit_card_type' => 'Visa',
        'billing_first_name' => 'Junko',
        'billing_middle_name' => '',
        'billing_last_name' => 'Adams',
        'billing_street_address-5' => '790L Lincoln St S',
        'billing_city-5' => 'Maryknoll',
        'billing_state_province_id-5' => 1031,
        'billing_postal_code-5' => 10545,
        'billing_country_id-5' => 1228,
        'frequency_interval' => 1,
        'frequency_unit' => 'month',
        'installments' => '',
        'hidden_AdditionalDetail' => 1,
        'hidden_Premium' => 1,
        'from_email_address' => '"civi45" <civi45@civicrm.com>',
        'is_email_receipt' => TRUE,
        'receipt_date' => '',
        'receipt_date_time' => '',
        'payment_processor_id' => $paymentProcessorID,
        'currency' => 'USD',
        'source' => 'bob sled race',
      ), CRM_Core_Action::ADD);
    }
    catch (Civi\Payment\Exception\PaymentProcessorException $e) {
      $error = TRUE;
    }

    $this->callAPISuccessGetCount('Contribution', array(
      'contact_id' => $this->_individualId,
      'contribution_status_id' => $error ? 'Failed' : 'Completed',
      'payment_instrument_id' => $this->callAPISuccessGetValue('PaymentProcessor', array(
        'return' => 'payment_instrument_id',
        'id' => $paymentProcessorID,
       )),
    ), 1);
    $contact = $this->callAPISuccessGetSingle('Contact', array('id' => $this->_individualId));
    $this->assertTrue(empty($contact['source']));
    if (!$error) {
      $msgs = $mut->getAllMessages();
      $this->assertEquals(1, count($msgs));
    }
    $mut->clearMessages();
    $mut->stop();
  }

  /**
   * Test the submit function on the contribution page
   */
  public function testSubmitCreditCardWithEmailReceipt() {
    $mut = new CiviMailUtils($this, TRUE);
    $mut->clearMessages();
    $form = new CRM_Contribute_Form_Contribution();
    $form->_mode = 'Live';
    $error = FALSE;
    try {
      $form->testSubmit(array(
        'total_amount' => 50,
        'financial_type_id' => 1,
        'receive_date' => '04/21/2015',
        'receive_date_time' => '11:27PM',
        'contact_id' => $this->_individualId,
        'contribution_status_id' => 1,
        'credit_card_number' => 4444333322221111,
        'cvv2' => 123,
        'credit_card_exp_date' => array(
          'M' => 9,
          'Y' => 2025,
        ),
        'credit_card_type' => 'Visa',
        'billing_first_name' => 'Junko',
        'billing_middle_name' => '',
        'billing_last_name' => 'Adams',
        'billing_street_address-5' => '790L Lincoln St S',
        'billing_city-5' => 'Maryknoll',
        'billing_state_province_id-5' => 1031,
        'billing_postal_code-5' => 10545,
        'billing_country_id-5' => 1228,
        'frequency_interval' => 1,
        'frequency_unit' => 'month',
        'installments' => '',
        'hidden_AdditionalDetail' => 1,
        'hidden_Premium' => 1,
        'from_email_address' => '"civi45" <civi45@civicrm.com>',
        'is_email_receipt' => TRUE,
        'receipt_date' => '',
        'receipt_date_time' => '',
        'payment_processor_id' => $this->paymentProcessorID,
        'currency' => 'USD',
        'source' => 'bob sled race',
      ), CRM_Core_Action::ADD);
    }
    catch (Civi\Payment\Exception\PaymentProcessorException $e) {
      $error = TRUE;
    }
    $this->callAPISuccessGetCount('Contribution', array(
      'contact_id' => $this->_individualId,
      'contribution_status_id' => $error ? 'Failed' : 'Completed',
      'payment_instrument_id' => $this->callAPISuccessGetValue('PaymentProcessor', array(
        'return' => 'payment_instrument_id',
        'id' => $this->paymentProcessorID,
      )),
    ), 1);
    $contact = $this->callAPISuccessGetSingle('Contact', array('id' => $this->_individualId));
    $this->assertTrue(empty($contact['source']));
    $msgs = $mut->getAllMessages();
    $this->assertEquals(1, count($msgs));
    $mut->stop();
  }


  /**
   * Test the submit function on the contribution page.
   */
  public function testSubmitCreditCardNoReceipt() {
    $mut = new CiviMailUtils($this, TRUE);
    $mut->clearMessages();
    $form = new CRM_Contribute_Form_Contribution();
    $form->_mode = 'Live';
    $error = FALSE;
    try {
      $form->testSubmit(array(
        'total_amount' => 60,
        'financial_type_id' => 1,
        'receive_date' => '04/21/2015',
        'receive_date_time' => '11:27PM',
        'contact_id' => $this->_individualId,
        'contribution_status_id' => 1,
        'credit_card_number' => 4444333322221111,
        'cvv2' => 123,
        'credit_card_exp_date' => array(
          'M' => 9,
          'Y' => 2025,
        ),
        'credit_card_type' => 'Visa',
        'billing_first_name' => 'Junko',
        'billing_middle_name' => '',
        'billing_last_name' => 'Adams',
        'billing_street_address-5' => '790L Lincoln St S',
        'billing_city-5' => 'Maryknoll',
        'billing_state_province_id-5' => 1031,
        'billing_postal_code-5' => 10545,
        'billing_country_id-5' => 1228,
        'frequency_interval' => 1,
        'frequency_unit' => 'month',
        'installments' => '',
        'hidden_AdditionalDetail' => 1,
        'hidden_Premium' => 1,
        'from_email_address' => '"civi45" <civi45@civicrm.com>',
        'is_email_receipt' => FALSE,
        'receipt_date' => '',
        'receipt_date_time' => '',
        'payment_processor_id' => $this->paymentProcessorID,
        'currency' => 'USD',
        'source' => 'bob sled race',
      ), CRM_Core_Action::ADD);
    }
    catch (Civi\Payment\Exception\PaymentProcessorException $e) {
      $error = TRUE;
    }

    $this->callAPISuccessGetCount('Contribution', array(
      'contact_id' => $this->_individualId,
      'contribution_status_id' => $error ? 'Failed' : 'Completed',
      'payment_instrument_id' => $this->callAPISuccessGetValue('PaymentProcessor', array(
        'return' => 'payment_instrument_id',
        'id' => $this->paymentProcessorID,
       )),
    ), 1);
    $contact = $this->callAPISuccessGetSingle('Contact', array('id' => $this->_individualId));
    $this->assertTrue(empty($contact['source']));
    $mut->assertMailLogEmpty();
    $mut->stop();
  }

  /**
   * Test the submit function on the contribution page.
   */
  public function testSubmitCreditCardFee() {
    $form = new CRM_Contribute_Form_Contribution();
    $this->paymentProcessor->setDoDirectPaymentResult(array('is_error' => 0, 'trxn_id' => 'tx', 'fee_amount' => .08));
    $form->_mode = 'Live';
    $form->testSubmit(array(
      'total_amount' => 50,
      'financial_type_id' => 1,
      'receive_date' => '04/21/2015',
      'receive_date_time' => '11:27PM',
      'contact_id' => $this->_individualId,
      'payment_instrument_id' => array_search('Credit Card', $this->paymentInstruments),
      'contribution_status_id' => 1,
      'credit_card_number' => 4444333322221111,
      'cvv2' => 123,
      'credit_card_exp_date' => array(
        'M' => 9,
        'Y' => 2025,
      ),
      'credit_card_type' => 'Visa',
      'billing_first_name' => 'Junko',
      'billing_middle_name' => '',
      'billing_last_name' => 'Adams',
      'billing_street_address-5' => '790L Lincoln St S',
      'billing_city-5' => 'Maryknoll',
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
      'source' => '',
    ), CRM_Core_Action::ADD);

    $contribution = $this->callAPISuccessGetSingle('Contribution', array(
      'contact_id' => $this->_individualId,
      'contribution_status_id' => 'Completed',
    ));
    $this->assertEquals('50', $contribution['total_amount']);
    $this->assertEquals(.08, $contribution['fee_amount']);
    $this->assertEquals(49.92, $contribution['net_amount']);
    $this->assertEquals('tx', $contribution['trxn_id']);
    $this->assertEmpty($contribution['amount_level']);
  }

  /**
   * Test a fully deductible contribution submitted by credit card (CRM-16669).
   */
  public function testSubmitCreditCardFullyDeductible() {
    $form = new CRM_Contribute_Form_Contribution();
    $form->_mode = 'Live';
    $form->testSubmit(array(
      'total_amount' => 50,
      'financial_type_id' => 1,
      'receive_date' => '04/21/2015',
      'receive_date_time' => '11:27PM',
      'contact_id' => $this->_individualId,
      'payment_instrument_id' => array_search('Credit Card', $this->paymentInstruments),
      'contribution_status_id' => 1,
      'credit_card_number' => 4444333322221111,
      'cvv2' => 123,
      'credit_card_exp_date' => array(
        'M' => 9,
        'Y' => 2025,
      ),
      'credit_card_type' => 'Visa',
      'billing_first_name' => 'Junko',
      'billing_middle_name' => '',
      'billing_last_name' => 'Adams',
      'billing_street_address-5' => '790L Lincoln St S',
      'billing_city-5' => 'Maryknoll',
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
      'source' => '',
    ), CRM_Core_Action::ADD);

    $contribution = $this->callAPISuccessGetSingle('Contribution', array(
      'contact_id' => $this->_individualId,
      'contribution_status_id' => 'Completed',
    ));
    $this->assertEquals('50', $contribution['total_amount']);
    $this->assertEquals(0, $contribution['non_deductible_amount']);
  }

  /**
   * Test the submit function with an invalid payment.
   *
   * We expect the contribution to be created but left pending. The payment has failed.
   *
   * Test covers CRM-16417 change to keep failed transactions.
   *
   * We are left with
   *  - 1 Contribution with status = Pending
   *  - 1 Line item
   *  - 1 civicrm_financial_item. This is linked to the line item and has a status of 3
   */
  public function testSubmitCreditCardInvalid() {
    $form = new CRM_Contribute_Form_Contribution();
    $this->paymentProcessor->setDoDirectPaymentResult(array('is_error' => 1));
    try {
      $form->testSubmit(array(
        'total_amount' => 50,
        'financial_type_id' => 1,
        'receive_date' => '04/21/2015',
        'receive_date_time' => '11:27PM',
        'contact_id' => $this->_individualId,
        'payment_instrument_id' => array_search('Credit Card', $this->paymentInstruments),
        'payment_processor_id' => $this->paymentProcessorID,
        'credit_card_exp_date' => array('M' => 5, 'Y' => 2012),
        'credit_card_number' => '411111111111111',
      ), CRM_Core_Action::ADD,
        'live'
      );
    }
    catch (\Civi\Payment\Exception\PaymentProcessorException $e) {
      $this->callAPISuccessGetCount('Contribution', array(
        'contact_id' => $this->_individualId,
        'contribution_status_id' => 'Failed',
      ), 1);
      $lineItem = $this->callAPISuccessGetSingle('line_item', array());
      $this->assertEquals('50.00', $lineItem['unit_price']);
      $this->assertEquals('50.00', $lineItem['line_total']);
      $this->assertEquals(1, $lineItem['qty']);
      $this->assertEquals(1, $lineItem['financial_type_id']);
      $financialItem = $this->callAPISuccessGetSingle('financial_item', array(
        'civicrm_line_item' => $lineItem['id'],
        'entity_id' => $lineItem['id'],
      ));
      $this->assertEquals('50.00', $financialItem['amount']);
      $this->assertEquals(3, $financialItem['status_id']);
      return;
    }
    $this->fail('An expected exception has not been raised.');
  }

  /**
   * Test the submit function creates a billing address if provided.
   */
  public function testSubmitCreditCardWithBillingAddress() {
    $form = new CRM_Contribute_Form_Contribution();
    $form->testSubmit(array(
      'total_amount' => 50,
      'financial_type_id' => 1,
      'receive_date' => '04/21/2015',
      'receive_date_time' => '11:27PM',
      'contact_id' => $this->_individualId,
      'payment_instrument_id' => array_search('Credit Card', $this->paymentInstruments),
      'payment_processor_id' => $this->paymentProcessorID,
      'credit_card_exp_date' => array('M' => 5, 'Y' => 2025),
      'credit_card_number' => '411111111111111',
      'billing_city-5' => 'Vancouver',
    ), CRM_Core_Action::ADD,
      'live'
    );
    $contribution = $this->callAPISuccessGetSingle('Contribution', array('return' => 'address_id'));
    $this->assertNotEmpty($contribution['address_id']);
    // CRM-18490 : There is a unwanted test leakage due to below getsingle Api as it only fails in Jenkin
    // for now we are only fetching address on based on Address ID (removed filter location_type_id and city)
    $this->callAPISuccessGetSingle('Address', array(
      'id' => $contribution['address_id'],
    ));
  }

  /**
   * CRM-20745: Test the submit function correctly sets the
   * receive date for recurring contribution.
   */
  public function testSubmitCreditCardWithRecur() {
    $form = new CRM_Contribute_Form_Contribution();
    $receiveDate = date('m/d/Y', strtotime('+1 month'));
    $form->testSubmit(array(
      'total_amount' => 50,
      'financial_type_id' => 1,
      'is_recur' => 1,
      'frequency_interval' => 2,
      'frequency_unit' => 'month',
      'installments' => 2,
      'receive_date' => $receiveDate,
      'receive_date_time' => '11:27PM',
      'contact_id' => $this->_individualId,
      'payment_instrument_id' => array_search('Credit Card', $this->paymentInstruments),
      'payment_processor_id' => $this->paymentProcessorID,
      'credit_card_exp_date' => array('M' => 5, 'Y' => 2025),
      'credit_card_number' => '411111111111111',
      'billing_city-5' => 'Vancouver',
    ), CRM_Core_Action::ADD,
      'live'
    );
    $contribution = $this->callAPISuccessGetSingle('Contribution', array('return' => 'receive_date'));
    $this->assertEquals(date("m/d/Y", strtotime($contribution['receive_date'])), $receiveDate);
  }

  /**
   * Test the submit function does not create a billing address if no details provided.
   */
  public function testSubmitCreditCardWithNoBillingAddress() {
    $form = new CRM_Contribute_Form_Contribution();
    $form->testSubmit(array(
      'total_amount' => 50,
      'financial_type_id' => 1,
      'receive_date' => '04/21/2015',
      'receive_date_time' => '11:27PM',
      'contact_id' => $this->_individualId,
      'payment_instrument_id' => array_search('Credit Card', $this->paymentInstruments),
      'payment_processor_id' => $this->paymentProcessorID,
      'credit_card_exp_date' => array('M' => 5, 'Y' => 2025),
      'credit_card_number' => '411111111111111',
    ), CRM_Core_Action::ADD,
      'live'
    );
    $contribution = $this->callAPISuccessGetSingle('Contribution', array('return' => 'address_id'));
    $this->assertEmpty($contribution['address_id']);
    $this->callAPISuccessGetCount('Address', array(
      'city' => 'Vancouver',
      'location_type_id' => 5,
    ), 0);
  }

  /**
   * Test the submit function on the contribution page.
   */
  public function testSubmitEmailReceipt() {
    $form = new CRM_Contribute_Form_Contribution();
    $mut = new CiviMailUtils($this, TRUE);
    $form->testSubmit(array(
      'total_amount' => 50,
      'financial_type_id' => 1,
      'receive_date' => '04/21/2015',
      'receive_date_time' => '11:27PM',
      'contact_id' => $this->_individualId,
      'is_email_receipt' => TRUE,
      'from_email_address' => 'test@test.com',
      'contribution_status_id' => 1,
    ), CRM_Core_Action::ADD);
    $this->callAPISuccessGetCount('Contribution', array('contact_id' => $this->_individualId), 1);
    $mut->checkMailLog(array(
        '<p>Please print this receipt for your records.</p>',
      )
    );
    $mut->stop();
  }

  /**
   * Ensure that price field are shown during pay later/pending Contribution
   */
  public function testEmailReceiptOnPayLater() {
    $donationFT = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialType', 'Donation', 'id', 'name');
    $paramsSet = array(
      'title' => 'Price Set' . substr(sha1(rand()), 0, 4),
      'is_active' => TRUE,
      'financial_type_id' => $donationFT,
      'extends' => 2,
    );
    $paramsSet['name'] = CRM_Utils_String::titleToVar($paramsSet['title']);

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
      'financial_type_id' => $donationFT,
    );
    $priceField = CRM_Price_BAO_PriceField::create($paramsField);
    $priceFieldValue = $this->callAPISuccess('PriceFieldValue', 'get', array('price_field_id' => $priceField->id));

    $params = array(
      'total_amount' => 100,
      'financial_type_id' => $donationFT,
      'receive_date' => '04/21/2015',
      'receive_date_time' => '11:27PM',
      'contact_id' => $this->_individualId,
      'is_email_receipt' => TRUE,
      'from_email_address' => 'test@test.com',
      'price_set_id' => $priceSetId,
      'contribution_status_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Pending'),
    );

    foreach ($priceFieldValue['values'] as $id => $price) {
      if ($price['amount'] == 100) {
        $params['price_' . $priceField->id] = array($id => 1);
      }
    }
    $form = new CRM_Contribute_Form_Contribution();
    $mut = new CiviMailUtils($this, TRUE);
    $form->_priceSet = current(CRM_Price_BAO_PriceSet::getSetDetail($priceSetId));
    $form->testSubmit($params, CRM_Core_Action::ADD);

    $mut->checkMailLog(array(
        'Financial Type: Donation
---------------------------------------------------------
Item                             Qty       Each       Total
----------------------------------------------------------
Price Field - Price Field 1        1   $ 100.00      $ 100.00
',
      )
    );
    $mut->stop();
  }

  /**
   * Test that a contribution is assigned against a pledge.
   */
  public function testUpdatePledge() {
    $pledge = $this->callAPISuccess('pledge', 'create', array(
      'contact_id' => $this->_individualId,
      'pledge_create_date' => date('Ymd'),
      'start_date' => date('Ymd'),
      'amount' => 100.00,
      'pledge_status_id' => '2',
      'pledge_financial_type_id' => '1',
      'pledge_original_installment_amount' => 20,
      'frequency_interval' => 5,
      'frequency_unit' => 'year',
      'frequency_day' => 15,
      'installments' => 2,
      'sequential' => 1,
    ));
    $pledgePaymentID = $this->callAPISuccess('pledge_payment', 'getvalue', array(
      'pledge_id' => $pledge['id'],
      'options' => array('limit' => 1),
      'return' => 'id',
    ));
    $form = new CRM_Contribute_Form_Contribution();
    $form->testSubmit(array(
      'total_amount' => 50,
      'financial_type_id' => 1,
      'receive_date' => '04/21/2015',
      'receive_date_time' => '11:27PM',
      'contact_id' => $this->_individualId,
      'payment_instrument_id' => array_search('Check', $this->paymentInstruments),
      'pledge_payment_id' => $pledgePaymentID,
      'contribution_status_id' => 1,
    ), CRM_Core_Action::ADD);
    $pledgePayment = $this->callAPISuccess('pledge_payment', 'getsingle', array('id' => $pledgePaymentID));
    $this->assertNotEmpty($pledgePayment['contribution_id']);
    $this->assertEquals($pledgePayment['actual_amount'], 50);
    $this->assertEquals(1, $pledgePayment['status_id']);
  }

  /**
   * Test functions involving premiums.
   */
  public function testPremiumUpdate() {
    $form = new CRM_Contribute_Form_Contribution();
    $mut = new CiviMailUtils($this, TRUE);
    $form->testSubmit(array(
      'total_amount' => 50,
      'financial_type_id' => 1,
      'receive_date' => '04/21/2015',
      'receive_date_time' => '11:27PM',
      'contact_id' => $this->_individualId,
      'payment_instrument_id' => array_search('Check', $this->paymentInstruments),
      'contribution_status_id' => 1,
      'product_name' => array($this->products[0]['id'], 1),
      'fulfilled_date' => '',
      'is_email_receipt' => TRUE,
      'from_email_address' => 'test@test.com',
    ), CRM_Core_Action::ADD);
    $contributionProduct = $this->callAPISuccess('contribution_product', 'getsingle', array());
    $this->assertEquals('clumsy smurf', $contributionProduct['product_option']);
    $mut->checkMailLog(array(
      'Premium Information',
      'Smurf',
      'clumsy smurf',
    ));
    $mut->stop();
  }

  /**
   * Test functions involving premiums.
   */
  public function testPremiumUpdateCreditCard() {
    $form = new CRM_Contribute_Form_Contribution();
    $mut = new CiviMailUtils($this, TRUE);
    $form->testSubmit(array(
      'total_amount' => 50,
      'financial_type_id' => 1,
      'receive_date' => '04/21/2015',
      'receive_date_time' => '11:27PM',
      'contact_id' => $this->_individualId,
      'payment_instrument_id' => array_search('Check', $this->paymentInstruments),
      'contribution_status_id' => 1,
      'product_name' => array($this->products[0]['id'], 1),
      'fulfilled_date' => '',
      'is_email_receipt' => TRUE,
      'from_email_address' => 'test@test.com',
      'payment_processor_id' => $this->paymentProcessorID,
      'credit_card_exp_date' => array('M' => 5, 'Y' => 2026),
      'credit_card_number' => '411111111111111',
    ), CRM_Core_Action::ADD,
    'live');
    $contributionProduct = $this->callAPISuccess('contribution_product', 'getsingle', array());
    $this->assertEquals('clumsy smurf', $contributionProduct['product_option']);
    $mut->checkMailLog(array(
      'Premium Information',
      'Smurf',
      'clumsy smurf',
    ));
    $mut->stop();
  }

  /**
   * Test the submit function on the contribution page.
   */
  public function testSubmitWithNote() {
    $form = new CRM_Contribute_Form_Contribution();
    $form->testSubmit(array(
      'total_amount' => 50,
      'financial_type_id' => 1,
      'receive_date' => '04/21/2015',
      'receive_date_time' => '11:27PM',
      'contact_id' => $this->_individualId,
      'payment_instrument_id' => array_search('Check', $this->paymentInstruments),
      'contribution_status_id' => 1,
      'note' => 'Super cool and interesting stuff',
    ),
      CRM_Core_Action::ADD);
    $this->callAPISuccessGetCount('Contribution', array('contact_id' => $this->_individualId), 1);
    $note = $this->callAPISuccessGetSingle('note', array('entity_table' => 'civicrm_contribution'));
    $this->assertEquals($note['note'], 'Super cool and interesting stuff');
  }

  /**
   * Test the submit function on the contribution page.
   */
  public function testSubmitWithNoteCreditCard() {
    $form = new CRM_Contribute_Form_Contribution();

    $form->testSubmit(array(
      'total_amount' => 50,
      'financial_type_id' => 1,
      'receive_date' => '04/21/2015',
      'receive_date_time' => '11:27PM',
      'contact_id' => $this->_individualId,
      'payment_instrument_id' => array_search('Check', $this->paymentInstruments),
      'contribution_status_id' => 1,
      'note' => 'Super cool and interesting stuff',
    ) + $this->getCreditCardParams(),
      CRM_Core_Action::ADD);
    $this->callAPISuccessGetCount('Contribution', array('contact_id' => $this->_individualId), 1);
    $note = $this->callAPISuccessGetSingle('note', array('entity_table' => 'civicrm_contribution'));
    $this->assertEquals($note['note'], 'Super cool and interesting stuff');
  }

  /**
   * Test that if a negative contribution is entered it does not get reset to $0.
   *
   * Note that this fails locally for me & I believe there may be an issue for some sites
   * with negative numbers. Grep for CRM-16460 to find the places I think that might
   * be affected if you hit this.
   */
  public function testEnterNegativeContribution() {
    $form = new CRM_Contribute_Form_Contribution();
    $form->testSubmit(array(
      'total_amount' => -5,
      'financial_type_id' => 1,
      'receive_date' => '04/24/2016',
      'receive_date_time' => '11:27PM',
      'contact_id' => $this->_individualId,
      'payment_instrument_id' => array_search('Check', $this->paymentInstruments),
      'contribution_status_id' => 1,
    ),
      CRM_Core_Action::ADD);
    $this->callAPISuccessGetCount('Contribution', array('contact_id' => $this->_individualId), 1);

    $contribution = $this->callAPISuccessGetSingle('Contribution', array(
      'contact_id' => $this->_individualId,
    ));
    $this->assertEquals(-5, $contribution['total_amount']);
  }

  /**
   * Test the submit function on the contribution page.
   */
  public function testSubmitUpdate() {
    $form = new CRM_Contribute_Form_Contribution();

    $form->testSubmit(array(
        'total_amount' => 50,
        'financial_type_id' => 1,
        'receive_date' => '04/21/2015',
        'receive_date_time' => '11:27PM',
        'contact_id' => $this->_individualId,
        'payment_instrument_id' => array_search('Check', $this->paymentInstruments),
        'contribution_status_id' => 1,
        'price_set_id' => 0,
      ),
      CRM_Core_Action::ADD);
    $contribution = $this->callAPISuccessGetSingle('Contribution', array('contact_id' => $this->_individualId));
    $form->testSubmit(array(
      'total_amount' => 45,
      'net_amount' => 45,
      'financial_type_id' => 1,
      'receive_date' => '04/21/2015',
      'receive_date_time' => '11:27PM',
      'contact_id' => $this->_individualId,
      'payment_instrument_id' => array_search('Check', $this->paymentInstruments),
      'contribution_status_id' => 1,
      'price_set_id' => 0,
      'id' => $contribution['id'],
    ),
      CRM_Core_Action::UPDATE);
    $contribution = $this->callAPISuccessGetSingle('Contribution', array('contact_id' => $this->_individualId));
    $this->assertEquals(45, (int) $contribution['total_amount']);

    $financialTransactions = $this->callAPISuccess('FinancialTrxn', 'get', array('sequential' => TRUE));
    $this->assertEquals(2, $financialTransactions['count']);
    $this->assertEquals(50, $financialTransactions['values'][0]['total_amount']);
    $this->assertEquals(-5, $financialTransactions['values'][1]['total_amount']);
    $this->assertEquals(-5, $financialTransactions['values'][1]['net_amount']);
    $lineItem = $this->callAPISuccessGetSingle('LineItem', array());
    $this->assertEquals(45, $lineItem['line_total']);
  }

  /**
   * Get parameters for credit card submit calls.
   *
   * @return array
   *   Credit card specific parameters.
   */
  protected function getCreditCardParams() {
    return array(
      'payment_processor_id' => $this->paymentProcessorID,
      'credit_card_exp_date' => array('M' => 5, 'Y' => 2012),
      'credit_card_number' => '411111111111111',
    );
  }

  /**
   * Test the submit function that completes the partially paid payment using Credit Card
   */
  public function testPartialPaymentWithCreditCard() {
    // create a partially paid contribution by using back-office form
    $form = new CRM_Contribute_Form_Contribution();
    $form->testSubmit(
      array(
        'total_amount' => 50,
        'financial_type_id' => 1,
        'receive_date' => '04/21/2015',
        'receive_date_time' => '11:27PM',
        'contact_id' => $this->_individualId,
        'payment_instrument_id' => array_search('Check', $this->paymentInstruments),
        'check_number' => substr(sha1(rand()), 0, 7),
        'billing_city-5' => 'Vancouver',
        'contribution_status_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Partially paid'),
      ), CRM_Core_Action::ADD
    );

    $contribution = $this->callAPISuccessGetSingle('Contribution', array());
    $this->assertNotEmpty($contribution);
    $this->assertEquals('Partially paid', $contribution['contribution_status']);
    // pay additional amount by using Credit Card
    $form = new CRM_Contribute_Form_AdditionalPayment();
    $form->testSubmit(array(
      'contribution_id' => $contribution['id'],
      'contact_id' => $this->_individualId,
      'total_amount' => 50,
      'currency' => 'USD',
      'financial_type_id' => 1,
      'receive_date' => '04/21/2015',
      'receive_date_time' => '11:27PM',
      'contact_id' => $this->_individualId,
      'payment_instrument_id' => array_search('Credit card', $this->paymentInstruments),
      'payment_processor_id' => $this->paymentProcessorID,
      'credit_card_exp_date' => array('M' => 5, 'Y' => 2025),
      'credit_card_number' => '411111111111111',
      'cvv2' => 234,
      'credit_card_type' => 'Visa',
      'billing_city-5' => 'Vancouver',
      'billing_state_province_id-5' => 1059,
      'billing_postal_code-5' => 1321312,
      'billing_country_id-5' => 1228,
      'trxn_date' => '2017-04-11 13:05:11',
    ), 'live');
    $contribution = $this->callAPISuccessGetSingle('Contribution', array());
    $this->assertNotEmpty($contribution);
    $this->assertEquals('Completed', $contribution['contribution_status']);
  }

  /**
   * Test the submit function for FT with tax.
   */
  public function testSubmitSaleTax() {
    $this->enableTaxAndInvoicing();
    $this->relationForFinancialTypeWithFinancialAccount($this->_financialTypeId);
    $form = new CRM_Contribute_Form_Contribution();

    $form->testSubmit(array(
       'total_amount' => 100,
        'financial_type_id' => $this->_financialTypeId,
        'receive_date' => '04/21/2015',
        'receive_date_time' => '11:27PM',
        'contact_id' => $this->_individualId,
        'payment_instrument_id' => array_search('Check', $this->paymentInstruments),
        'contribution_status_id' => 1,
        'price_set_id' => 0,
      ),
      CRM_Core_Action::ADD
    );
    $contribution = $this->callAPISuccessGetSingle('Contribution',
      array(
        'contact_id' => $this->_individualId,
        'return' => array('tax_amount', 'total_amount'),
      )
    );
    $this->assertEquals(110, $contribution['total_amount']);
    $this->assertEquals(10, $contribution['tax_amount']);
    $this->callAPISuccessGetCount('FinancialTrxn', array(), 1);
    $this->callAPISuccessGetCount('FinancialItem', array(), 2);
    $lineItem = $this->callAPISuccessGetSingle('LineItem', array('contribution_id' => $contribution['id']));
    $this->assertEquals(100, $lineItem['line_total']);
    $this->assertEquals(10, $lineItem['tax_amount']);
  }

  /**
   * Test the submit function for FT without tax.
   */
  public function testSubmitWithOutSaleTax() {
    $this->enableTaxAndInvoicing();
    $this->relationForFinancialTypeWithFinancialAccount($this->_financialTypeId);
    $form = new CRM_Contribute_Form_Contribution();

    $form->testSubmit(array(
       'total_amount' => 100,
        'financial_type_id' => 3,
        'receive_date' => '04/21/2015',
        'receive_date_time' => '11:27PM',
        'contact_id' => $this->_individualId,
        'payment_instrument_id' => array_search('Check', $this->paymentInstruments),
        'contribution_status_id' => 1,
        'price_set_id' => 0,
      ),
      CRM_Core_Action::ADD
    );
    $contribution = $this->callAPISuccessGetSingle('Contribution',
      array(
        'contact_id' => $this->_individualId,
        'return' => array('tax_amount', 'total_amount'),
      )
    );
    $this->assertEquals(100, $contribution['total_amount']);
    $this->assertEquals(NULL, $contribution['tax_amount']);
    $this->callAPISuccessGetCount('FinancialTrxn', array(), 1);
    $this->callAPISuccessGetCount('FinancialItem', array(), 1);
    $lineItem = $this->callAPISuccessGetSingle(
      'LineItem',
      array(
        'contribution_id' => $contribution['id'],
        'return' => array('line_total', 'tax_amount'),
      )
    );
    $this->assertEquals(100, $lineItem['line_total']);
    $this->assertTrue(empty($lineItem['tax_amount']));
  }

  /**
   * Create a contribution & then edit it via backoffice form, checking tax with: default price_set
   *
   * @throws \Exception
   */
  public function testReSubmitSaleTax() {
    $this->enableTaxAndInvoicing();
    $this->relationForFinancialTypeWithFinancialAccount($this->_financialTypeId);
    list($form, $contribution) = $this->doInitialSubmit();
    $this->assertEquals(110, $contribution['total_amount']);
    $this->assertEquals(10, $contribution['tax_amount']);
    $this->assertEquals(110, $contribution['net_amount']);

    $mut = new CiviMailUtils($this, TRUE);
    // Testing here if when we edit something trivial like adding a check_number tax, net, total amount stay the same:
    $form->testSubmit(array(
      'id' => $contribution['id'],
      'tax_amount' => $contribution['tax_amount'],
      'financial_type_id' => $contribution['financial_type_id'],
      'receive_date' => $contribution['receive_date'],
      'payment_instrument_id' => $contribution['payment_instrument_id'],
      'price_set_id' => 0,
      'check_number' => 12345,
      'contribution_status_id' => 1,
      'is_email_receipt' => 1,
      'from_email_address' => 'demo@example.com',
    ),
      CRM_Core_Action::UPDATE
    );
    $contribution = $this->callAPISuccessGetSingle('Contribution',
      array(
        'contribution_id' => 1,
        'return' => array('tax_amount', 'total_amount', 'net_amount', 'financial_type_id', 'receive_date', 'payment_instrument_id'),
      )
    );
    $this->assertEquals(110, $contribution['total_amount']);
    $this->assertEquals(10, $contribution['tax_amount']);
    $this->assertEquals(110, $contribution['net_amount']);

    $strings = array(
      'Total Tax Amount : $ 10.00',
      'Total Amount : $ 110.00',
      'Date Received: April 21st, 2015',
      'Paid By: Check',
      'Check Number: 12345',
    );

    $mut->checkMailLog($strings);
    $this->callAPISuccessGetCount('FinancialTrxn', array(), 3);
    $items = $this->callAPISuccess('FinancialItem', 'get', array('sequential' => 1));
    $this->assertEquals(2, $items['count']);
    $this->assertEquals('Contribution Amount', $items['values'][0]['description']);
    $this->assertEquals('Sales Tax', $items['values'][1]['description']);

    $this->assertEquals(100, $items['values'][0]['amount']);
    $this->assertEquals(10, $items['values'][1]['amount']);
  }

  /**
   * Create a contribution & then edit it via backoffice form, checking tax with: default price_set
   *
   * @throws \Exception
   */
  public function testReSubmitSaleTaxAlteredAmount() {
    $this->enableTaxAndInvoicing();
    $this->relationForFinancialTypeWithFinancialAccount($this->_financialTypeId);
    list($form, $contribution) = $this->doInitialSubmit();

    $mut = new CiviMailUtils($this, TRUE);
    // Testing here if when we edit something trivial like adding a check_number tax, net, total amount stay the same:
    $form->testSubmit(array(
      'id' => $contribution['id'],
      'total_amount' => 200,
      'tax_amount' => 20,
      'financial_type_id' => $contribution['financial_type_id'],
      'receive_date' => $contribution['receive_date'],
      'payment_instrument_id' => $contribution['payment_instrument_id'],
      'price_set_id' => 0,
      'check_number' => 12345,
      'contribution_status_id' => 1,
      'is_email_receipt' => 1,
      'from_email_address' => 'demo@example.com',
    ),
      CRM_Core_Action::UPDATE
    );
    $contribution = $this->callAPISuccessGetSingle('Contribution',
      array(
        'contribution_id' => 1,
        'return' => array('tax_amount', 'total_amount', 'net_amount', 'financial_type_id', 'receive_date', 'payment_instrument_id'),
      )
    );
    $this->assertEquals(220, $contribution['total_amount']);
    $this->assertEquals(20, $contribution['tax_amount']);
    $this->assertEquals(220, $contribution['net_amount']);

    $strings = array(
      'Total Tax Amount : $ 20.00',
      'Total Amount : $ 220.00',
      'Date Received: April 21st, 2015',
      'Paid By: Check',
      'Check Number: 12345',
    );

    $mut->checkMailLog($strings);
    $this->callAPISuccessGetCount('FinancialTrxn', array(), 4);
    $items = $this->callAPISuccess('FinancialItem', 'get', array('sequential' => 1));
    $this->assertEquals(4, $items['count']);
    $this->assertEquals('Contribution Amount', $items['values'][0]['description']);
    $this->assertEquals('Sales Tax', $items['values'][1]['description']);
    $this->assertEquals('Contribution Amount', $items['values'][0]['description']);
    $this->assertEquals('Sales Tax', $items['values'][1]['description']);

    $this->assertEquals(100, $items['values'][0]['amount']);
    $this->assertEquals(10, $items['values'][1]['amount']);
    // @todo currently its $110 which is incorrect, the proper value should be $200
    // $this->assertEquals(200, $items['values'][2]['amount']);
    $this->assertEquals(20, $items['values'][3]['amount']);
  }

  /**
   * Do the first contributions, in preparation for an edit-submit.
   *
   * @return array
   *
   * @throws \Exception
   */
  protected function doInitialSubmit() {
    $form = new CRM_Contribute_Form_Contribution();

    $form->testSubmit(array(
      'total_amount' => 100,
      'financial_type_id' => $this->_financialTypeId,
      'receive_date' => '04/21/2015',
      'receive_date_time' => '11:27PM',
      'contact_id' => $this->_individualId,
      'payment_instrument_id' => array_search('Check', $this->paymentInstruments),
      'contribution_status_id' => 1,
      'price_set_id' => 0,
    ),
      CRM_Core_Action::ADD
    );
    $contribution = $this->callAPISuccessGetSingle('Contribution',
      array(
        'contribution_id' => 1,
        'return' => array(
          'tax_amount',
          'total_amount',
          'net_amount',
          'financial_type_id',
          'receive_date',
          'payment_instrument_id',
        ),
      )
    );
    $this->assertEquals(110, $contribution['total_amount']);
    $this->assertEquals(10, $contribution['tax_amount']);
    $this->assertEquals(110, $contribution['net_amount']);
    return array($form, $contribution);
  }

  /**
   * function to test card_type and pan truncation.
   */
  public function testCardTypeAndPanTruncation() {
    $form = new CRM_Contribute_Form_Contribution();
    $form->testSubmit(
      array(
        'total_amount' => 100,
        'financial_type_id' => 3,
        'receive_date' => '04/21/2015',
        'receive_date_time' => '11:27PM',
        'contact_id' => $this->_individualId,
        'payment_instrument_id' => array_search('Credit Card', $this->paymentInstruments),
        'contribution_status_id' => 1,
        'credit_card_type' => 'Visa',
        'pan_truncation' => 4567,
        'price_set_id' => 0,
      ),
      CRM_Core_Action::ADD
    );
    $contribution = $this->callAPISuccessGetSingle('Contribution',
      array(
        'contact_id' => $this->_individualId,
        'return' => array('id'),
      )
    );
    $lastFinancialTrxnId = CRM_Core_BAO_FinancialTrxn::getFinancialTrxnId($contribution['id'], 'DESC');
    $financialTrxn = $this->callAPISuccessGetSingle(
      'FinancialTrxn',
      array(
        'id' => $lastFinancialTrxnId['financialTrxnId'],
        'return' => array('card_type_id.label', 'pan_truncation'),
      )
    );
    $this->assertEquals(CRM_Utils_Array::value('card_type_id.label', $financialTrxn), 'Visa');
    $this->assertEquals(CRM_Utils_Array::value('pan_truncation', $financialTrxn), 4567);
  }

  /**
   * function to test card_type and pan truncation.
   */
  public function testCardTypeAndPanTruncationLiveMode() {
    $visaID = CRM_Core_PseudoConstant::getKey('CRM_Core_BAO_FinancialTrxn', 'card_type_id', 'Visa');
    $form = new CRM_Contribute_Form_Contribution();
    $form->_mode = 'Live';
    $form->testSubmit(
      array(
        'total_amount' => 50,
        'financial_type_id' => 1,
        'receive_date' => '04/21/2015',
        'receive_date_time' => '11:27PM',
        'contact_id' => $this->_individualId,
        'credit_card_number' => 4444333322221111,
        'payment_instrument_id' => array_search('Credit Card', $this->paymentInstruments),
        'cvv2' => 123,
        'credit_card_exp_date' => array(
          'M' => 9,
          'Y' => date('Y', strtotime('+5 years')),
        ),
        'credit_card_type' => 'Visa',
        'billing_first_name' => 'Junko',
        'billing_middle_name' => '',
        'billing_last_name' => 'Adams',
        'billing_street_address-5' => '790L Lincoln St S',
        'billing_city-5' => 'Maryknoll',
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
        'source' => 'bob sled race',
      ),
      CRM_Core_Action::ADD
    );
    $contribution = $this->callAPISuccessGetSingle('Contribution', array('contact_id' => $this->_individualId));
    $lastFinancialTrxnId = CRM_Core_BAO_FinancialTrxn::getFinancialTrxnId($contribution['id'], 'DESC');
    $financialTrxn = $this->callAPISuccessGetSingle(
      'FinancialTrxn',
      array(
        'id' => $lastFinancialTrxnId['financialTrxnId'],
        'return' => array('card_type_id', 'pan_truncation'),
      )
    );
    $this->assertEquals($visaID, $financialTrxn['card_type_id']);
    $this->assertEquals(1111, $financialTrxn['pan_truncation']);
  }

}
