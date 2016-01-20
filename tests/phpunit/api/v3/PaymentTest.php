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
class api_v3_PaymentTest extends CiviUnitTestCase {

  /**
   * Assume empty database with just civicrm_data.
   */
  protected $_individualId;
  protected $_contribution;
  protected $_financialTypeId = 1;
  protected $_apiversion;
  protected $_entity = 'Contribution';
  public $debug = 0;
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
   * Setup function.
   */
  public function setUp() {
    parent::setUp();

    $this->_apiversion = 3;
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
  }

  /**
   * Clean up after each test.
   */
  public function tearDown() {
    $this->quickCleanUpFinancialEntities();
    $this->quickCleanup(array('civicrm_uf_match'));
  }

  /**
   * Test Get Payment api.
   */
  public function testGetPayment() {
    $p = array(
      'contact_id' => $this->_individualId,
      'receive_date' => '2010-01-20',
      'total_amount' => 100.00,
      'financial_type_id' => $this->_financialTypeId,
      'trxn_id' => 23456,
      'contribution_status_id' => 1,
    );
    $contribution = $this->callAPISuccess('contribution', 'create', $p);

    $params = array(
      'contribution_id' => $contribution['id'],
    );

    $payment = $this->callAPIAndDocument('payment', 'get', $params, __FUNCTION__, __FILE__);

    $this->assertEquals(1, $payment['count']);
    $this->assertEquals($payment['values'][$payment['id']]['total_amount'], 100.00);
    $this->assertEquals($payment['values'][$payment['id']]['trxn_id'], 23456);
    $this->assertEquals($payment['values'][$payment['id']]['trxn_date'], '2010-01-20 00:00:00');
    $this->assertEquals($payment['values'][$payment['id']]['is_payment'], 1);
    $this->assertEquals($payment['values'][$payment['id']]['contribution_id'], $contribution['id']);
    $this->callAPISuccess('Contribution', 'Delete', array(
      'id' => $contribution['id'],
    ));
  }

  /**
   * Test create payment api with no line item in params
   */
  public function testCreatePaymentNoLineItems() {
    $params = array(
      'contact_id' => $this->_individualId,
      'receive_date' => '20120511',
      'total_amount' => 200.00,
      'financial_type_id' => $this->_financialTypeId,
      'payment_instrument_id' => 1,
      'contribution_status_id' => 8,
    );

    $contribution = $this->callAPISuccess('contribution', 'create', $params); //Create partially paid contribution

    //Create partial payment
    $params = array(
      'contribution_id' => $contribution['id'],
      'total_amount' => 150,
    );
    $payment = $this->callAPIAndDocument('payment', 'create', $params, __FUNCTION__, __FILE__);

    $this->assertEquals($payment['values'][$payment['id']]['from_financial_account_id'], 7);
    $this->assertEquals($payment['values'][$payment['id']]['to_financial_account_id'], 6);
    $this->assertEquals($payment['values'][$payment['id']]['total_amount'], 150);
    $this->assertEquals($payment['values'][$payment['id']]['status_id'], 1);
    $this->assertEquals($payment['values'][$payment['id']]['is_payment'], 1);

    // Check entity financial trxn created properly
    $params = array(
      'entity_id' => $contribution['id'],
      'entity_table' => 'civicrm_contribution',
      'financial_trxn_id' => $payment['id'],
    );

    $eft = $this->callAPISuccess('EntityFinancialTrxn', 'get', $params);

    $this->assertEquals($eft['values'][$eft['id']]['amount'], 150);

    // Now create payment to complete total amount of contribution
    $params = array(
      'contribution_id' => $contribution['id'],
      'total_amount' => 50,
    );
    $payment = $this->callAPIAndDocument('payment', 'create', $params, __FUNCTION__, __FILE__);

    $this->assertEquals($payment['values'][$payment['id']]['from_financial_account_id'], 7);
    $this->assertEquals($payment['values'][$payment['id']]['to_financial_account_id'], 6);
    $this->assertEquals($payment['values'][$payment['id']]['total_amount'], 50);
    $this->assertEquals($payment['values'][$payment['id']]['status_id'], 1);
    $this->assertEquals($payment['values'][$payment['id']]['is_payment'], 1);

    // Check contribution for completed status
    $contribution = $this->callAPISuccess('contribution', 'get', array('id' => $contribution['id']));

    $this->assertEquals($contribution['values'][$contribution['id']]['contribution_status'], 'Completed');
    $this->assertEquals($contribution['values'][$contribution['id']]['total_amount'], 200.00);

    $this->callAPISuccess('Contribution', 'Delete', array(
      'id' => $contribution['id'],
    ));
  }

  /**
   * Test create payment api with line item in params
   */
  public function testCreatePaymentLineItems() {
    // Create priceset & price fields
    $this->createPriceSet();

    $params = array(
      'contact_id' => $this->_individualId,
      'receive_date' => '20120511',
      'total_amount' => 200.00,
      'financial_type_id' => $this->_financialTypeId,
      'payment_instrument_id' => 1,
      'contribution_status_id' => 8,
    );

    $contribution = $this->callAPISuccess('contribution', 'create', $params); //Create partially paid contribution

    //Create partial payment
    $params = array(
      'contribution_id' => $contribution['id'],
      'total_amount' => 150,
    );
    $payment = $this->callAPIAndDocument('payment', 'create', $params, __FUNCTION__, __FILE__);

    $this->assertEquals($payment['values'][$payment['id']]['from_financial_account_id'], 7);
    $this->assertEquals($payment['values'][$payment['id']]['to_financial_account_id'], 6);
    $this->assertEquals($payment['values'][$payment['id']]['total_amount'], 150);
    $this->assertEquals($payment['values'][$payment['id']]['status_id'], 1);
    $this->assertEquals($payment['values'][$payment['id']]['is_payment'], 1);

    // Check entity financial trxn created properly
    $params = array(
      'entity_id' => $contribution['id'],
      'entity_table' => 'civicrm_contribution',
      'financial_trxn_id' => $payment['id'],
    );

    $eft = $this->callAPISuccess('EntityFinancialTrxn', 'get', $params);

    $this->assertEquals($eft['values'][$eft['id']]['amount'], 150);

    // Now create payment to complete total amount of contribution
    $params = array(
      'contribution_id' => $contribution['id'],
      'total_amount' => 50,
    );
    $payment = $this->callAPIAndDocument('payment', 'create', $params, __FUNCTION__, __FILE__);

    $this->assertEquals($payment['values'][$payment['id']]['from_financial_account_id'], 7);
    $this->assertEquals($payment['values'][$payment['id']]['to_financial_account_id'], 6);
    $this->assertEquals($payment['values'][$payment['id']]['total_amount'], 50);
    $this->assertEquals($payment['values'][$payment['id']]['status_id'], 1);
    $this->assertEquals($payment['values'][$payment['id']]['is_payment'], 1);

    // Check contribution for completed status
    $contribution = $this->callAPISuccess('contribution', 'get', array('id' => $contribution['id']));

    $this->assertEquals($contribution['values'][$contribution['id']]['contribution_status'], 'Completed');
    $this->assertEquals($contribution['values'][$contribution['id']]['total_amount'], 200.00);

    $this->callAPISuccess('Contribution', 'Delete', array(
      'id' => $contribution['id'],
    ));
  }

  /**
   * function to delete data
   */
  public function cleanUpAfterPriceSets() {
    $this->quickCleanUpFinancialEntities();
    $this->contactDelete($this->_ids['contact']);
  }


  /**
   * Create price set.
   *
   */
  public function createPriceSet() {
    $contributionPageResult = $this->callAPISuccess('contribution_page', 'create', array(
      'title' => "Test Contribution Page",
      'financial_type_id' => 1,
      'currency' => 'NZD',
      'is_pay_later' => 1,
      'is_monetary' => TRUE,
      'is_email_receipt' => FALSE,
    ));
    $priceSet = $this->callAPISuccess('price_set', 'create', array(
      'is_quick_config' => 0,
      'extends' => 'CiviContribute',
      'financial_type_id' => 1,
      'title' => 'My Test Price Set',
    ));
    $priceSetID = $priceSet['id'];
    CRM_Price_BAO_PriceSet::addTo('civicrm_contribution_page', $contributionPageResult['id'], $priceSetID);

    $priceField = $this->callAPISuccess('price_field', 'create', array(
      'price_set_id' => $priceSetID,
      'label' => 'Goat Breed',
      'html_type' => 'Radio',
    ));
    $priceFieldValue = $this->callAPISuccess('price_field_value', 'create', array(
        'price_set_id' => $priceSetID,
        'price_field_id' => $priceField['id'],
        'label' => 'Long Haired Goat',
        'amount' => 50,
        'financial_type_id' => 'Donation',
      )
    );
    $this->_priceIds['price_field_value'] = array($priceFieldValue['id']);
    $priceFieldValue = $this->callAPISuccess('price_field_value', 'create', array(
        'price_set_id' => $priceSetID,
        'price_field_id' => $priceField['id'],
        'label' => 'Shoe-eating Goat',
        'amount' => 150,
        'financial_type_id' => 'Donation',
      )
    );
    $this->_priceIds['price_field_value'][] = $priceFieldValue['id'];
    $this->_priceIds['price_set'] = $priceSetID;
    $this->_priceIds['price_field'] = $priceField['id'];
  }

  /**
   * Test cancel payment api
   */
  public function testCancelPayment() {
    list($lineItems, $contribution) = $this->createParticipantWithContribution();

    $params = array(
      'contribution_id' => $contribution['id'],
    );

    $payment = $this->callAPIAndDocument('payment', 'get', $params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $payment['count']);

    $cancelParams = array(
      'id' => $payment['id'],
    );
    $this->callAPIAndDocument('payment', 'cancel', $cancelParams, __FUNCTION__, __FILE__);

    $payment = $this->callAPIAndDocument('payment', 'get', $params, __FUNCTION__, __FILE__);
    $this->assertEquals(2, $payment['count']);
    $amounts = array(-150.00, 150.00);
    foreach ($payment['values'] as $value) {
      $this->assertEquals($value['total_amount'], array_pop($amounts), 'Mismatch total amount');
    }

    $this->callAPISuccess('Contribution', 'Delete', array(
      'id' => $contribution['id'],
    ));
  }

}
