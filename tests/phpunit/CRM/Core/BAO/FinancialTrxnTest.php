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
 * Class CRM_Core_BAO_FinancialTrxnTest
 * @group headless
 */
class CRM_Core_BAO_FinancialTrxnTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
  }

  /**
   * Check method create().
   */
  public function testCreate() {
    $contactId = $this->individualCreate();
    $financialTypeId = 1;
    $this->contributionCreate(array(
      'contact_id' => $contactId,
      'financial_type_id' => $financialTypeId,
    ));
    $params = array(
      'contribution_id' => $financialTypeId,
      'to_financial_account_id' => 1,
      'trxn_date' => 20091021184930,
      'trxn_type' => 'Debit',
      'total_amount' => 10,
      'net_amount' => 90.00,
      'currency' => 'USD',
      'payment_processor' => 'Dummy',
      'trxn_id' => 'test_01014000',
    );
    $FinancialTrxn = CRM_Core_BAO_FinancialTrxn::create($params);

    $result = $this->assertDBNotNull('CRM_Core_BAO_FinancialTrxn', $FinancialTrxn->id,
      'total_amount', 'id',
      'Database check on updated financial trxn record.'
    );

    $this->assertEquals($result, 10, 'Verify financial trxn total_amount.');
  }

  /**
   * Test getTotalPayments function.
   */
  public function testGetTotalPayments() {
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
      'trxn_id' => '22ereerwwe4444yy',
      'invoice_id' => '86ed39e9e9yy6ef6541621ce0eafe7eb81',
      'thankyou_date' => '20080522',
    );

    $contribution = $this->callAPISuccess('Contribution', 'create', $params);
    $contribution = $contribution['values'][$contribution['id']];

    $totalPaymentAmount = CRM_Core_BAO_FinancialTrxn::getTotalPayments($contribution['id']);
    $this->assertEquals(0, $totalPaymentAmount, 'Amount not matching.');

    $params['id'] = $contribution['id'];
    $params['contribution_status_id'] = 1;

    $contribution = $this->callAPISuccess('Contribution', 'create', $params);

    $totalPaymentAmount = CRM_Core_BAO_FinancialTrxn::getTotalPayments($contribution['id']);
    $this->assertEquals('200.00', $totalPaymentAmount, 'Amount not matching.');
  }

  /**
   * Tests the lines of code that used to be in the getPartialPaymentTrxn fn.
   */
  public function testGetExPartialPaymentTrxn() {
    $contributionTest = new CRM_Contribute_BAO_ContributionTest();
    list($lineItems, $contribution) = $contributionTest->addParticipantWithContribution();
    $contribution = (array) $contribution;
    $params = array(
      'contribution_id' => $contribution['id'],
      'total_amount' => 100.00,
    );
    $trxn = CRM_Contribute_BAO_Contribution::recordPartialPayment($contribution, $params);
    $paid = CRM_Core_BAO_FinancialTrxn::getTotalPayments($params['contribution_id']);
    $total = CRM_Core_DAO::getFieldValue('CRM_Contribute_DAO_Contribution', $params['contribution_id'], 'total_amount');
    $cmp = bccomp($total, $paid, 5);
    // If paid amount is greater or equal to total amount
    if ($cmp == 0 || $cmp == -1) {
      civicrm_api3('Contribution', 'completetransaction', array('id' => $contribution['id']));
    }

    $this->assertEquals('100.00', $trxn->total_amount, 'Amount does not match.');

    $totalPaymentAmount = CRM_Core_BAO_FinancialTrxn::getTotalPayments($contribution['id']);
    $this->assertEquals('250.00', $totalPaymentAmount, 'Amount does not match.');
  }

  /**
   * Test for createDeferredTrxn().
   */
  public function testCreateDeferredTrxn() {
    Civi::settings()->set('contribution_invoice_settings', array('deferred_revenue_enabled' => '1'));
    $cid = $this->individualCreate();
    $params = array(
      'contact_id' => $cid,
      'receive_date' => '2016-01-20',
      'total_amount' => 622,
      'financial_type_id' => 4,
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
              'financial_type_id' => 4,
            ),
          ),
          'params' => array(),
        ),
      ),
    );
    $contribution = $this->callAPISuccess('Contribution', 'create', $params);
    $lineItems[1] = CRM_Price_BAO_LineItem::getLineItemsByContributionID($contribution['id']);
    $lineItemId = key($lineItems[1]);
    $lineItems[1][$lineItemId]['financial_item_id'] = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_financial_item WHERE entity_table = 'civicrm_line_item' AND entity_id = {$lineItemId}");
    // Get financial trxns for contribution
    $trxn = $this->callAPISuccess("FinancialTrxn", "get", array('total_amount' => 622));
    $this->assertEquals(date('Ymd', strtotime($trxn['values'][$trxn['id']]['trxn_date'])), date('Ymd', strtotime('2016-01-20')));
    $contributionObj = $this->getContributionObject($contribution['id']);
    $contributionObj->revenue_recognition_date = date('Ymd', strtotime("+1 month"));
    CRM_Core_BAO_FinancialTrxn::createDeferredTrxn($lineItems, $contributionObj);
    $trxn = $this->callAPISuccess("FinancialTrxn", "get", array('total_amount' => 622, 'id' => array("NOT IN" => array($trxn['id']))));
    $this->assertEquals(date('Ymd', strtotime($trxn['values'][$trxn['id']]['trxn_date'])), date('Ymd', strtotime("+1 month")));
  }

  /**
   * Test for updateCreditCardDetails().
   */
  public function testUpdateCreditCardDetailsUsingContributionAPI() {
    $cid = $this->individualCreate();
    $params = array(
      'contact_id' => $cid,
      'receive_date' => '2016-01-20',
      'total_amount' => 100,
      'financial_type_id' => 1,
    );
    $contribution = $this->callAPISuccess('Contribution', 'create', $params);
    $lastFinancialTrxnId = CRM_Core_BAO_FinancialTrxn::getFinancialTrxnId($contribution['id'], 'DESC');
    $financialTrxn = $this->callAPISuccessGetSingle(
      'FinancialTrxn',
      array(
        'id' => $lastFinancialTrxnId['financialTrxnId'],
        'return' => array('card_type_id', 'pan_truncation'),
      )
    );
    $this->assertEquals(CRM_Utils_Array::value('card_type_id', $financialTrxn), NULL);
    $this->assertEquals(CRM_Utils_Array::value('pan_truncation', $financialTrxn), NULL);
    $params = array(
      'card_type_id' => 2,
      'pan_truncation' => 4567,
      'id' => $contribution['id'],
    );
    $this->callAPISuccess("Contribution", "create", $params);
    $financialTrxn = $this->callAPISuccessGetSingle(
      'FinancialTrxn',
      array(
        'id' => $lastFinancialTrxnId['financialTrxnId'],
        'return' => array('card_type_id', 'pan_truncation'),
      )
    );
    $this->assertEquals($financialTrxn['card_type_id'], 2);
    $this->assertEquals($financialTrxn['pan_truncation'], 4567);
  }

  /**
   * Test for updateCreditCardDetails().
   */
  public function testUpdateCreditCardDetails() {
    $cid = $this->individualCreate();
    $params = array(
      'contact_id' => $cid,
      'receive_date' => '2016-01-20',
      'total_amount' => 100,
      'financial_type_id' => 1,
    );
    $contribution = $this->callAPISuccess('Contribution', 'create', $params);
    $lastFinancialTrxnId = CRM_Core_BAO_FinancialTrxn::getFinancialTrxnId($contribution['id'], 'DESC');
    $financialTrxn = $this->callAPISuccessGetSingle(
      'FinancialTrxn',
      array(
        'id' => $lastFinancialTrxnId['financialTrxnId'],
        'return' => array('card_type_id', 'pan_truncation'),
      )
    );
    $this->assertEquals(CRM_Utils_Array::value('card_type_id', $financialTrxn), NULL);
    $this->assertEquals(CRM_Utils_Array::value('pan_truncation', $financialTrxn), NULL);
    CRM_Core_BAO_FinancialTrxn::updateCreditCardDetails($contribution['id'], 4567, 2);
    $financialTrxn = $this->callAPISuccessGetSingle(
      'FinancialTrxn',
      array(
        'id' => $lastFinancialTrxnId['financialTrxnId'],
        'return' => array('card_type_id', 'pan_truncation'),
      )
    );
    $this->assertEquals($financialTrxn['card_type_id'], 2);
    $this->assertEquals($financialTrxn['pan_truncation'], 4567);
  }

  /**
   * Test getPartialPaymentWithType function.
   */
  public function testGetPartialPaymentWithType() {
    //create the contribution that isn't paid yet
    $contactId = $this->individualCreate();
    $params = array(
      'contact_id' => $contactId,
      'currency' => 'USD',
      'financial_type_id' => 1,
      'contribution_status_id' => 8,
      'payment_instrument_id' => 4,
      'total_amount' => 300.00,
      'fee_amount' => 0.00,
      'net_amount' => 300.00,
    );
    $contribution = $this->callAPISuccess('Contribution', 'create', $params)['values'][7];
    //make a payment one cent short
    $params = array(
      'contribution_id' => $contribution['id'],
      'total_amount' => 299.99,
    );
    $this->callAPISuccess('Payment', 'create', $params);
    //amount owed should be one cent
    $amountOwed = CRM_Core_BAO_FinancialTrxn::getPartialPaymentWithType($contribution['id'], 'contribution')['amount_owed'];
    $this->assertTrue(0.01 == $amountOwed, 'Amount does not match');
  }

}
