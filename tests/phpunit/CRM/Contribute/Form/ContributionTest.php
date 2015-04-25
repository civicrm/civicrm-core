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
   * Setup function.
   */
  public function setUp() {
    parent::setUp();

    $this->_apiversion = 3;
    $this->_individualId = $this->individualCreate();
    $paymentProcessor = $this->processorCreate();
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
    $this->_pageParams = array(
      'title' => 'Test Contribution Page',
      'financial_type_id' => 1,
      'currency' => 'USD',
      'financial_account_id' => 1,
      'payment_processor' => $paymentProcessor->id,
      'is_active' => 1,
      'is_allow_other_amount' => 1,
      'min_amount' => 10,
      'max_amount' => 1000,
    );
    $instruments = $this->callAPISuccess('contribution', 'getoptions', array('field' => 'payment_instrument_id'));
    $this->paymentInstruments = $instruments['values'];
  }

  /**
   * Clean up after each test.
   */
  public function tearDown() {
    $this->quickCleanUpFinancialEntities();
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
    ));
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
    ));
    $this->callAPISuccessGetCount('Contribution', array('contact_id' => $this->_individualId), 1);
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
    ));
    $this->callAPISuccessGetCount('Contribution', array('contact_id' => $this->_individualId), 1);
    $mut->checkMailLog(array(
        '<p>Please print this receipt for your records.</p>',
      )
    );
    $mut->stop();
  }
}
