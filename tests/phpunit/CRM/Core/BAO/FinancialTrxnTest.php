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

    $contribution = CRM_Contribute_BAO_Contribution::create($params);

    $this->assertEquals($params['trxn_id'], $contribution->trxn_id);
    $this->assertEquals($contactId, $contribution->contact_id);

    $totalPaymentAmount = CRM_Core_BAO_FinancialTrxn::getTotalPayments($contribution->id);
    $this->assertEquals(0, $totalPaymentAmount, 'Amount not matching.');
    //update contribution amount
    $params['id'] = $contribution->id;
    $params['contribution_status_id'] = 1;

    $contribution = CRM_Contribute_BAO_Contribution::create($params);

    $this->assertEquals($params['trxn_id'], $contribution->trxn_id);
    $this->assertEquals($params['contribution_status_id'], $contribution->contribution_status_id);

    $totalPaymentAmount = CRM_Core_BAO_FinancialTrxn::getTotalPayments($contribution->id);
    $this->assertEquals('200.00', $totalPaymentAmount, 'Amount not matching.');
  }

  /**
   * Test getPartialPaymentTrxn function.
   */
  public function testGetPartialPaymentTrxn() {
    $contributionTest = new CRM_Contribute_BAO_ContributionTest();
    list($lineItems, $contribution) = $contributionTest->addParticipantWithContribution();
    $contribution = (array) $contribution;
    $params = array(
      'contribution_id' => $contribution['id'],
      'total_amount' => 100.00,
    );
    $trxn = CRM_Core_BAO_FinancialTrxn::getPartialPaymentTrxn($contribution, $params);

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
    $financialTypeId = 4;
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
              'financial_type_id' => $financialTypeId,
            ),
          ),
          'params' => array(),
        ),
      ),
    );
    $contribution = CRM_Contribute_BAO_Contribution::create($params);
    $lineItems[1] = CRM_Price_BAO_LineItem::getLineItemsByContributionID($contribution->id);
    $lineItemId = key($lineItems[1]);
    $lineItems[1][$lineItemId]['financial_item_id'] = CRM_Core_DAO::singleValueQuery("SELECT id FROM civicrm_financial_item WHERE entity_table = 'civicrm_line_item' AND entity_id = {$lineItemId}");
    // Get the Deferred financial trxns for contribution
    $toDeferredFinancialAccountID = CRM_Contribute_PseudoConstant::getRelationalFinancialAccount($financialTypeId, 'Deferred Revenue Account is');
    $trxn = $this->callAPISuccess("FinancialTrxn", "get", array(
      'total_amount' => 622,
      'to_financial_account_id' => $toDeferredFinancialAccountID,
    ));
    $this->assertEquals(date('Ymd', strtotime($trxn['values'][$trxn['id']]['trxn_date'])), date('Ymd', strtotime('2016-01-20')));
    $contribution->revenue_recognition_date = date('Ymd', strtotime("+1 month"));
    CRM_Core_BAO_FinancialTrxn::createDeferredTrxn($lineItems, $contribution);
    $trxn = $this->callAPISuccess("FinancialTrxn", "get", array(
      'total_amount' => 622,
      'id' => array("NOT IN" => array($trxn['id'])),
      'to_financial_account_id' => $toDeferredFinancialAccountID,
    ));
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
    $contribution = CRM_Contribute_BAO_Contribution::create($params);
    $lastFinancialTrxnId = CRM_Core_BAO_FinancialTrxn::getFinancialTrxnId($contribution->id, 'DESC');
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
      'id' => $contribution->id,
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
    $contribution = CRM_Contribute_BAO_Contribution::create($params);
    $lastFinancialTrxnId = CRM_Core_BAO_FinancialTrxn::getFinancialTrxnId($contribution->id, 'DESC');
    $financialTrxn = $this->callAPISuccessGetSingle(
      'FinancialTrxn',
      array(
        'id' => $lastFinancialTrxnId['financialTrxnId'],
        'return' => array('card_type_id', 'pan_truncation'),
      )
    );
    $this->assertEquals(CRM_Utils_Array::value('card_type_id', $financialTrxn), NULL);
    $this->assertEquals(CRM_Utils_Array::value('pan_truncation', $financialTrxn), NULL);
    CRM_Core_BAO_FinancialTrxn::updateCreditCardDetails($contribution->id, 4567, 2);
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

}
