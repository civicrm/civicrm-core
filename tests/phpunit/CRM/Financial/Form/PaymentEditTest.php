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

class CRM_Financial_Form_PaymentEditTest extends CiviUnitTestCase {

  protected $_individualID;

  /**
   * Setup function.
   */
  public function setUp() {
    parent::setUp();
    $this->createLoggedInUser();

    $this->_individualID = $this->individualCreate();
  }

  /**
   * Clean up after each test.
   */
  public function tearDown() {
    $this->quickCleanUpFinancialEntities();
    $this->quickCleanup(array('civicrm_note', 'civicrm_uf_match', 'civicrm_address'));
  }

  /**
   * Test the submit function of payment edit form.
   */
  public function testSubmitOnPaymentInstrumentChange() {
    // First create a contribution using 'Check' as payment instrument
    $form = new CRM_Contribute_Form_Contribution();
    $form->testSubmit(array(
      'total_amount' => 50,
      'financial_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'financial_type_id', 'Donation'),
      'receive_date' => '04/21/2015',
      'receive_date_time' => '11:27PM',
      'contact_id' => $this->_individualID,
      'payment_instrument_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'financial_type_id', 'Check'),
      'check_number' => '123XA',
      'contribution_status_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed'),
    ),
      CRM_Core_Action::ADD);
    // fetch the financial trxn record later used in setting default values of payment edit form
    $contribution = $this->callAPISuccessGetSingle('Contribution', array('contact_id' => $this->_individualID));
    $payments = CRM_Contribute_BAO_Contribution::getPaymentInfo($contribution['id'], 'contribute', TRUE);
    $financialTrxnInfo = $payments['transaction'][0];

    // build parameters which changed payment instrument and tran date values
    $params = array(
      'id' => $financialTrxnInfo['id'],
      'payment_instrument_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'payment_instrument_id', 'Credit Card'),
      'card_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Financial_DAO_FinancialTrxn', 'card_type_id', 'Visa'),
      'pan_truncation' => 1111,
      'trnx_id' => 'txn_12AAAA',
      'trxn_date' => date('Y-m-d H:i:s'),
      'contribution_id' => $contribution['id'],
    );
    $form = new CRM_Financial_Form_PaymentEdit();
    $form->testSubmit($params);
    $payments = CRM_Contribute_BAO_Contribution::getPaymentInfo($contribution['id'], 'contribute', TRUE);
    $expectedPaymentParams = array(
      array(
        'total_amount' => 50.00,
        'financial_type' => 'Donation,Donation,Donation',
        'payment_instrument' => 'Check',
        'status' => 'Completed',
        'receive_date' => '2015-04-21 23:27:00',
        'check_number' => '123XA',
      ),
      array(
        'total_amount' => -50.00,
        'financial_type' => NULL,
        'payment_instrument' => 'Check',
        'status' => 'Completed',
        'receive_date' => $params['trxn_date'],
        'check_number' => '123XA',
      ),
      array(
        'total_amount' => 50.00,
        'financial_type' => NULL,
        'payment_instrument' => sprintf('Credit Card (Visa: %s)', $params['pan_truncation']),
        'status' => 'Completed',
        'receive_date' => $params['trxn_date'],
      ),
    );
    //$this->assertEquals(3, count($payments['transaction']));
    foreach ($expectedPaymentParams as $key => $paymentParams) {
      foreach($paymentParams as $fieldName => $expectedValue) {
        $this->assertEquals($expectedPaymentParams[$key][$fieldName], $payments['transaction'][$key][$fieldName]);
      }
    }
  }

}
