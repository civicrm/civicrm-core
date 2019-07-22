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
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 *  Test PaymentEdit form submission
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
    $this->quickCleanup(['civicrm_note', 'civicrm_uf_match', 'civicrm_address']);
  }

  /**
   * Test the submit function of payment edit form.
   */
  public function testSubmitOnPaymentInstrumentChange() {
    // First create a contribution using 'Check' as payment instrument
    $form = new CRM_Contribute_Form_Contribution();
    $form->testSubmit([
      'total_amount' => 50,
      'receive_date' => '2015-04-21 23:27:00',
      'financial_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'financial_type_id', 'Donation'),
      'contact_id' => $this->_individualID,
      'payment_instrument_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'payment_instrument_id', 'Check'),
      'check_number' => '123XA',
      'contribution_status_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed'),
    ],
      CRM_Core_Action::ADD);
    // fetch the financial trxn record later used in setting default values of payment edit form
    $contribution = $this->callAPISuccessGetSingle('Contribution', ['contact_id' => $this->_individualID]);
    $payments = CRM_Contribute_BAO_Contribution::getPaymentInfo($contribution['id'], 'contribute', TRUE);
    $financialTrxnInfo = $payments['transaction'][0];

    // build parameters which changed payment instrument and tran date values
    $params = [
      'id' => $financialTrxnInfo['id'],
      'payment_instrument_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'payment_instrument_id', 'Credit Card'),
      'card_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Financial_DAO_FinancialTrxn', 'card_type_id', 'Visa'),
      'pan_truncation' => 1111,
      'trnx_id' => 'txn_12AAAA',
      'trxn_date' => date('Y-m-d H:i:s'),
      'contribution_id' => $contribution['id'],
    ];
    $form = new CRM_Financial_Form_PaymentEdit();
    $form->testSubmit($params);
    $payments = CRM_Contribute_BAO_Contribution::getPaymentInfo($contribution['id'], 'contribute', TRUE);
    $expectedPaymentParams = [
      [
        'total_amount' => 50.00,
        'financial_type' => 'Donation',
        'payment_instrument' => 'Check',
        'status' => 'Completed',
        'receive_date' => '2015-04-21 23:27:00',
        'check_number' => '123XA',
      ],
      [
        'total_amount' => -50.00,
        'financial_type' => 'Donation',
        'payment_instrument' => 'Check',
        'status' => 'Completed',
        'receive_date' => $params['trxn_date'],
        'check_number' => '123XA',
      ],
      [
        'total_amount' => 50.00,
        'financial_type' => 'Donation',
        'payment_instrument' => sprintf('Credit Card (Visa: %s)', $params['pan_truncation']),
        'status' => 'Completed',
        'receive_date' => $params['trxn_date'],
      ],
    ];
    $this->assertEquals(3, count($payments['transaction']));
    foreach ($expectedPaymentParams as $key => $paymentParams) {
      foreach ($paymentParams as $fieldName => $expectedValue) {
        $this->assertEquals($expectedPaymentParams[$key][$fieldName], $payments['transaction'][$key][$fieldName]);
      }
    }
  }

  /**
   * Test to ensure that multiple check_numbers are concatenated
   *  and stored in related contribution's check_number
   */
  public function testSubmitOnCheckNumberChange() {
    // CASE 1: Submit contribution using Check as payment instrument and check_number as '123XA'
    $checkNumber1 = '123XA';
    $checkPaymentInstrumentID = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'payment_instrument_id', 'Check');
    // First create a contribution using 'Check' as payment instrument
    $form = new CRM_Contribute_Form_Contribution();
    $form->testSubmit([
      'total_amount' => 50,
      'financial_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'financial_type_id', 'Donation'),
      'contact_id' => $this->_individualID,
      'payment_instrument_id' => $checkPaymentInstrumentID,
      'check_number' => $checkNumber1,
      'contribution_status_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed'),
    ],
      CRM_Core_Action::ADD);
    $contribution = $this->callAPISuccessGetSingle('Contribution', ['contact_id' => $this->_individualID]);
    $payments = CRM_Contribute_BAO_Contribution::getPaymentInfo($contribution['id'], 'contribute', TRUE);
    $financialTrxnInfo = $payments['transaction'][0];

    // CASE 2: Submit payment details via edit form and changed check_number to '456XA',
    //  ensure that contribution's check_number has concatenated check-numbers
    $checkNumber2 = '456XA';
    // build parameters which changed payment instrument and tran date values
    $params = [
      'id' => $financialTrxnInfo['id'],
      'payment_instrument_id' => $checkPaymentInstrumentID,
      'check_number' => $checkNumber2,
      'trxn_date' => date('Y-m-d H:i:s'),
      'contribution_id' => $contribution['id'],
    ];
    $form = new CRM_Financial_Form_PaymentEdit();
    $form->testSubmit($params);
    $contribution = $this->callAPISuccessGetSingle('Contribution', ['id' => $contribution['id']]);
    $expectedConcatanatedCheckNumbers = implode(',', [$checkNumber1, $checkNumber2]);
    $this->assertEquals($expectedConcatanatedCheckNumbers, $contribution['check_number']);

    // CASE 3: Submit payment details via edit form without any change,
    //  ensure that contribution's check_number concatenated value isn't changed
    $form->testSubmit($params);
    $contribution = $this->callAPISuccessGetSingle('Contribution', ['id' => $contribution['id']]);
    $this->assertEquals($expectedConcatanatedCheckNumbers, $contribution['check_number']);
  }

}
