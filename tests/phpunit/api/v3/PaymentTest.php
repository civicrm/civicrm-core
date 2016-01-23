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
  protected $_financialTypeId = 1;
  protected $_apiversion;
  public $debug = 0;

  /**
   * Setup function.
   */
  public function setUp() {
    parent::setUp();

    $this->_apiversion = 3;
    $this->_individualId = $this->individualCreate();
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
    $expectedResult = array(
      'total_amount' => 100,
      'trxn_id' => 23456,
      'trxn_date' => '2010-01-20 00:00:00',
      'contribution_id' => $contribution['id'],
      'is_payment' => 1,
    );
    $this->checkPaymentResult($payment, $expectedResult);
    $this->callAPISuccess('Contribution', 'Delete', array(
      'id' => $contribution['id'],
    ));
  }

  /**
   * Test create payment api with no line item in params
   */
  public function testCreatePaymentNoLineItems() {
    list($lineItems, $contribution) = $this->createParticipantWithContribution();

    //Create partial payment
    $params = array(
      'contribution_id' => $contribution['id'],
      'total_amount' => 50,
    );
    $payment = $this->callAPIAndDocument('payment', 'create', $params, __FUNCTION__, __FILE__);
    $expectedResult = array(
      'from_financial_account_id' => 7,
      'to_financial_account_id' => 6,
      'total_amount' => 50,
      'status_id' => 1,
      'is_payment' => 1,
    );
    $this->checkPaymentResult($payment, $expectedResult);

    // Check entity financial trxn created properly
    $params = array(
      'entity_id' => $contribution['id'],
      'entity_table' => 'civicrm_contribution',
      'financial_trxn_id' => $payment['id'],
    );

    $eft = $this->callAPISuccess('EntityFinancialTrxn', 'get', $params);

    $this->assertEquals($eft['values'][$eft['id']]['amount'], 50);

    $params = array(
      'entity_table' => 'civicrm_financial_item',
      'financial_trxn_id' => $payment['id'],
    );
    $eft = $this->callAPISuccess('EntityFinancialTrxn', 'get', $params);
    $amounts = array(33.33, 16.67);
    foreach ($eft['values'] as $value) {
      $this->assertEquals($value['amount'], array_pop($amounts));
    }

    // Now create payment to complete total amount of contribution
    $params = array(
      'contribution_id' => $contribution['id'],
      'total_amount' => 100,
    );
    $payment = $this->callAPIAndDocument('payment', 'create', $params, __FUNCTION__, __FILE__);
    $expectedResult = array(
      'from_financial_account_id' => 7,
      'to_financial_account_id' => 6,
      'total_amount' => 100,
      'status_id' => 1,
      'is_payment' => 1,
    );
    $this->checkPaymentResult($payment, $expectedResult);
    $params = array(
      'entity_table' => 'civicrm_financial_item',
      'financial_trxn_id' => $payment['id'],
    );
    $eft = $this->callAPISuccess('EntityFinancialTrxn', 'get', $params);
    $amounts = array(66.67, 33.33);
    foreach ($eft['values'] as $value) {
      $this->assertEquals($value['amount'], array_pop($amounts));
    }
    // Check contribution for completed status
    $contribution = $this->callAPISuccess('contribution', 'get', array('id' => $contribution['id']));

    $this->assertEquals($contribution['values'][$contribution['id']]['contribution_status'], 'Completed');
    $this->assertEquals($contribution['values'][$contribution['id']]['total_amount'], 300.00);
    $paymentParticipant = array(
      'contribution_id' => $contribution['id'],
    );
    $participantPayment = $this->callAPISuccess('ParticipantPayment', 'getsingle', $paymentParticipant);
    $participant = $this->callAPISuccess('participant', 'get', array('id' => $participantPayment['participant_id']));
    $this->assertEquals($participant['values'][$participant['id']]['participant_status'], 'Registered');
    $this->callAPISuccess('Contribution', 'Delete', array(
      'id' => $contribution['id'],
    ));
  }

  /**
   * Function to assert db values
   */
  public function checkPaymentResult($payment, $expectedResult) {
    foreach ($expectedResult as $key => $value) {
      $this->assertEquals($payment['values'][$payment['id']][$key], $value);
    }
  }

  /**
   * Test create payment api with line item in params
   */
  public function testCreatePaymentLineItems() {
    list($lineItems, $contribution) = $this->createParticipantWithContribution();
    $lineItems = $this->callAPISuccess('LineItem', 'get', array('contribution_id' => $contribution['id']));

    //Create partial payment by passing line item array is params
    $params = array(
      'contribution_id' => $contribution['id'],
      'total_amount' => 50,
    );
    $amounts = array(40, 10);
    foreach ($lineItems['values'] as $id => $ignore) {
      $params['line_item'][] = array($id => array_pop($amounts));
    }
    $payment = $this->callAPIAndDocument('payment', 'create', $params, __FUNCTION__, __FILE__);
    $expectedResult = array(
      'from_financial_account_id' => 7,
      'to_financial_account_id' => 6,
      'total_amount' => 50,
      'status_id' => 1,
      'is_payment' => 1,
    );
    $this->checkPaymentResult($payment, $expectedResult);

    // Check entity financial trxn created properly
    $params = array(
      'entity_id' => $contribution['id'],
      'entity_table' => 'civicrm_contribution',
      'financial_trxn_id' => $payment['id'],
    );

    $eft = $this->callAPISuccess('EntityFinancialTrxn', 'get', $params);

    $this->assertEquals($eft['values'][$eft['id']]['amount'], 50);

    $params = array(
      'entity_table' => 'civicrm_financial_item',
      'financial_trxn_id' => $payment['id'],
    );
    $eft = $this->callAPISuccess('EntityFinancialTrxn', 'get', $params);
    $amounts = array(40, 10);
    foreach ($eft['values'] as $value) {
      $this->assertEquals($value['amount'], array_pop($amounts));
    }

    // Now create payment to complete total amount of contribution
    $params = array(
      'contribution_id' => $contribution['id'],
      'total_amount' => 100,
    );
    $amounts = array(80, 20);
    foreach ($lineItems['values'] as $id => $ignore) {
      $params['line_item'][] = array($id => array_pop($amounts));
    }
    $payment = $this->callAPIAndDocument('payment', 'create', $params, __FUNCTION__, __FILE__);
    $expectedResult = array(
      'from_financial_account_id' => 7,
      'to_financial_account_id' => 6,
      'total_amount' => 100,
      'status_id' => 1,
      'is_payment' => 1,
    );
    $this->checkPaymentResult($payment, $expectedResult);
    $params = array(
      'entity_table' => 'civicrm_financial_item',
      'financial_trxn_id' => $payment['id'],
    );
    $eft = $this->callAPISuccess('EntityFinancialTrxn', 'get', $params);
    $amounts = array(80, 20);
    foreach ($eft['values'] as $value) {
      $this->assertEquals($value['amount'], array_pop($amounts));
    }
    // Check contribution for completed status
    $contribution = $this->callAPISuccess('contribution', 'get', array('id' => $contribution['id']));

    $this->assertEquals($contribution['values'][$contribution['id']]['contribution_status'], 'Completed');
    $this->assertEquals($contribution['values'][$contribution['id']]['total_amount'], 300.00);
    $paymentParticipant = array(
      'contribution_id' => $contribution['id'],
    );
    $participantPayment = $this->callAPISuccess('ParticipantPayment', 'getsingle', $paymentParticipant);
    $participant = $this->callAPISuccess('participant', 'get', array('id' => $participantPayment['participant_id']));
    $this->assertEquals($participant['values'][$participant['id']]['participant_status'], 'Registered');
    $this->callAPISuccess('Contribution', 'Delete', array(
      'id' => $contribution['id'],
    ));
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

  /**
   * Test delete payment api
   */
  public function testDeletePayment() {
    list($lineItems, $contribution) = $this->createParticipantWithContribution();

    $params = array(
      'contribution_id' => $contribution['id'],
    );

    $payment = $this->callAPIAndDocument('payment', 'get', $params, __FUNCTION__, __FILE__);
    $this->assertEquals(1, $payment['count']);

    $cancelParams = array(
      'id' => $payment['id'],
    );
    $this->callAPIAndDocument('payment', 'delete', $cancelParams, __FUNCTION__, __FILE__);

    $payment = $this->callAPIAndDocument('payment', 'get', $params, __FUNCTION__, __FILE__);
    $this->assertEquals(0, $payment['count']);

    $this->callAPISuccess('Contribution', 'Delete', array(
      'id' => $contribution['id'],
    ));
  }

  /**
   * Test update payment api
   */
  public function testUpdatePayment() {
    list($lineItems, $contribution) = $this->createParticipantWithContribution();

    //Create partial payment by passing line item array is params
    $params = array(
      'contribution_id' => $contribution['id'],
      'total_amount' => 50,
    );

    $payment = $this->callAPIAndDocument('payment', 'create', $params, __FUNCTION__, __FILE__);
    $expectedResult = array(
      'from_financial_account_id' => 7,
      'to_financial_account_id' => 6,
      'total_amount' => 50,
      'status_id' => 1,
      'is_payment' => 1,
    );
    $this->checkPaymentResult($payment, $expectedResult);

    $params = array(
      'entity_table' => 'civicrm_financial_item',
      'financial_trxn_id' => $payment['id'],
    );
    $eft = $this->callAPISuccess('EntityFinancialTrxn', 'get', $params);
    $amounts = array(33.33, 16.67);
    foreach ($eft['values'] as $value) {
      $this->assertEquals($value['amount'], array_pop($amounts));
    }

    // update the amount for payment
    $params = array(
      'contribution_id' => $contribution['id'],
      'total_amount' => 100,
      'id' => $payment['id'],
    );
    $payment = $this->callAPIAndDocument('payment', 'create', $params, __FUNCTION__, __FILE__);

    $params = array(
      'entity_table' => 'civicrm_financial_item',
      'financial_trxn_id' => $payment['id'],
    );
    $eft = $this->callAPISuccess('EntityFinancialTrxn', 'get', $params);
    $amounts = array(66.67, 33.33);
    foreach ($eft['values'] as $value) {
      $this->assertEquals($value['amount'], array_pop($amounts));
    }

    $params = array(
      'contribution_id' => $contribution['id'],
    );
    $payment = $this->callAPIAndDocument('payment', 'get', $params, __FUNCTION__, __FILE__);
    $amounts = array(100.00, -50.00, 50.00, 150.00);
    foreach ($payment['values'] as $value) {
      $amount = array_pop($amounts);
      $this->assertEquals($value['total_amount'], $amount, 'Mismatch total amount');

      // Check entity financial trxn created properly
      $params = array(
        'entity_id' => $contribution['id'],
        'entity_table' => 'civicrm_contribution',
        'financial_trxn_id' => $value['id'],
      );
      $eft = $this->callAPISuccess('EntityFinancialTrxn', 'get', $params);
      $this->assertEquals($eft['values'][$eft['id']]['amount'], $amount);
    }

    $this->callAPISuccess('Contribution', 'Delete', array(
      'id' => $contribution['id'],
    ));
  }

  /**
   * Test create payment api for paylater contribution
   */
  public function testCreatePaymentPayLater() {
    $contributionParams = array(
      'total_amount' => 100,
      'currency' => 'USD',
      'contact_id' => $this->_individualId,
      'financial_type_id' => 1,
      'contribution_status_id' => 2,
      'is_pay_later' => 1,
    );
    $contribution = $this->callAPISuccess('Contribution', 'create', $contributionParams);

    //Create partial payment
    $params = array(
      'contribution_id' => $contribution['id'],
      'total_amount' => 100,
    );
    $payment = $this->callAPIAndDocument('Payment', 'create', $params, __FUNCTION__, __FILE__);
    $expectedResult = array(
      'from_financial_account_id' => 7,
      'to_financial_account_id' => 6,
      'total_amount' => 100,
      'status_id' => 1,
      'is_payment' => 1,
    );
    $this->checkPaymentResult($payment, $expectedResult);

    // Check entity financial trxn created properly
    $params = array(
      'entity_id' => $contribution['id'],
      'entity_table' => 'civicrm_contribution',
      'financial_trxn_id' => $payment['id'],
    );

    $eft = $this->callAPISuccess('EntityFinancialTrxn', 'get', $params);

    $this->assertEquals($eft['values'][$eft['id']]['amount'], 100);

    $params = array(
      'entity_table' => 'civicrm_financial_item',
      'financial_trxn_id' => $payment['id'],
    );
    $eft = $this->callAPISuccess('EntityFinancialTrxn', 'get', $params);
    $this->assertEquals($eft['values'][$eft['id']]['amount'], 100);

    // Check contribution for completed status
    $contribution = $this->callAPISuccess('contribution', 'get', array('id' => $contribution['id']));

    $this->assertEquals($contribution['values'][$contribution['id']]['contribution_status'], 'Completed');
    $this->assertEquals($contribution['values'][$contribution['id']]['total_amount'], 100.00);
    $this->callAPISuccess('Contribution', 'Delete', array(
      'id' => $contribution['id'],
    ));
  }

}
