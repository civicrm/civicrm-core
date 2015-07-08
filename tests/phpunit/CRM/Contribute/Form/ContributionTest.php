<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
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
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

require_once 'CiviTest/CiviUnitTestCase.php';
require_once 'CiviTest/CiviMailUtils.php';


/**
 *  Test APIv3 civicrm_contribute_* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Contribution
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
    $this->paymentProcessor = $this->processorCreate();

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
    $this->callAPISuccessGetCount('Contribution', array('contact_id' => $this->_individualId), 1);
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
        'payment_processor_id' => $this->paymentProcessor->id,
        'credit_card_exp_date' => array('M' => 5, 'Y' => 2012),
        'credit_card_number' => '411111111111111',
      ), CRM_Core_Action::ADD,
        'live'
      );
    }
    catch (\Civi\Payment\Exception\PaymentProcessorException $e) {
      $this->callAPISuccessGetCount('Contribution', array(
        'contact_id' => $this->_individualId,
        'contribution_status_id' => 'Pending',
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
      'payment_processor_id' => $this->paymentProcessor->id,
      'credit_card_exp_date' => array('M' => 5, 'Y' => 2025),
      'credit_card_number' => '411111111111111',
      'billing_city-5' => 'Vancouver',
    ), CRM_Core_Action::ADD,
      'live'
    );
    $contribution = $this->callAPISuccessGetSingle('Contribution', array('return' => 'address_id'));
    $this->assertNotEmpty($contribution['address_id']);
    $this->callAPISuccessGetSingle('Address', array(
      'city' => 'Vancouver',
      'location_type_id' => 5,
      'id' => $contribution['address_id'],
    ));

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
      'payment_processor_id' => $this->paymentProcessor->id,
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
    require_once 'CiviTest/CiviMailUtils.php';
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
      'payment_processor_id' => $this->paymentProcessor->id,
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
    $this->callAPISuccessGetCount('Contribution', array('contact_id' => $this->_individualId), 1);
    $financialTransactions = $this->callAPISuccess('FinancialTrxn', 'get', array('sequential' => TRUE));
    $this->assertEquals(2, $financialTransactions['count']);
    $this->assertEquals(50, $financialTransactions['values'][0]['total_amount']);
    $this->assertEquals(45, $financialTransactions['values'][1]['total_amount']);
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
      'payment_processor_id' => $this->paymentProcessor->id,
      'credit_card_exp_date' => array('M' => 5, 'Y' => 2012),
      'credit_card_number' => '411111111111111',
    );
  }

}
