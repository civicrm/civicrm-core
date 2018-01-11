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
 *  Tests for MembershipRenewal, testing price set based renewals.
 *
 *  (PHP 5)
 *
 * @author Marc Brazeau <marc@scibrazeau.ca>
 */

/**
 *  Test CRM_Member_Form_MembershipRenewal functions.
 *
 * @package   CiviCRM
 * @group headless
 */
class CRM_Member_Form_MembershipPriceSetRenewalTest extends CiviUnitTestCase {

  private $_contactID;
  private $_paymentProcessorID;

  private $_dataset;
  private $_mem_date;

  public function setUp() {
    $this->_apiversion = 3;
    $this->_mem_date = date('Y-m-d');

    parent::setUp();

    $this->_paymentProcessorID = $this->processorCreate();

    // The CIC is the reference use case for this.  So we'll just go ahead and create their data.

    $op = new PHPUnit_Extensions_Database_Operation_Insert();

    $this->_dataset = $this->createFlatXMLDataSet(
      dirname(__FILE__) . '/dataset/price_set_renewal_data.xml'
    );

    $op->execute($this->_dbconn, $this->_dataset);
    // my xml left 0s out to be a bit more concise.  This creates errors, so use proper value.
    CRM_Core_DAO::executeQuery("update civicrm_price_field set is_required = 0 where is_required IS NULL");

  }

  public function testFormFieldsMembershipWithNoApplicablePriceSet() {
    try {
      $this->_contactID = $this->individualCreate();

      $membershipId = $this->cicContactMembershipCreate(150);

      $form = $this->getForm($membershipId);

      // we expect this to be false, because 150, has no price sets (even though there exists price sets in our data set).
      $this->assertNull($form->get_template_vars('show_price_sets'));
      $this->assertNull($form->get_template_vars('hasPriceSets'));
    }
    catch (Exception $e) {
      throw $e;
    }

  }


  public function testFormFieldsMembershipWithMultipleApplicablePriceSetsExpiredMemberships() {
    $this->_mem_date = '2007-01-21'; // noticed that this caused an exception, while running test below so keeping (good catch).
    $this->testFormFieldsMembershipWithMultipleApplicablePriceSets();
  }

  public function testFormFieldsMembershipWithMultipleApplicablePriceSets() {
    $this->_contactID = $this->individualCreate();

    $memberships[0] = $this->cicContactMembershipCreate(165); // ACCN Print (member of contact #8)
    $memberships[1] = $this->cicContactMembershipCreate(199); // CSC Full Fee (member of contact #5)
    $memberships[2] = $this->cicContactMembershipCreate(171); // Member of IUPAC  (member of contact #12).

    for ($i = 0; $i < count($memberships); $i++) {
      // result is the same, regardless of membership that is renewed.
      $form = $this->getForm($memberships[$i]);

      // we expect this to be false, because 150, has no price sets (even though there exists price sets in our data set).
      $hasPriceSets = $form->get_template_vars('hasPriceSets');
      $showPriceSets = $form->get_template_vars('show_price_set');
      $price_set_id = $form->get_template_vars('priceSetId');
      $price_set = $form->get_template_vars('priceSet');
      $this->assertTrue($hasPriceSets);
      $this->assertFalse($showPriceSets);
      $this->assertEquals(32, $price_set_id);
      $this->assertNotEmpty($price_set);
    }
  }

  /**
   * Test the submit function of the membership form.
   *
   * In this test, we start with two memberships (CSC & ACCN).
   *
   * We renew, using a price set, picking four memberships:
   *  767 (CSC Full Fee)
   *  781 (CSChE Additional Member)
   *  782 (CSCT Additiona Member)
   *  783 (ACCN Print & On-Line).
   *
   * Expected result is that we now that the 2 previous memberships have had their end date extended,
   * start & join date remain unchanged.  The 2 new memberships get their join dates set to join_date
   * specified & start date based on membership rules.
   */
  public function testSubmit_RenewPickExistingAndAddNew() {
    $this->_contactID = $this->individualCreate();

    $nowMinus3Months = strtotime('-3 months', time());
    $nowPlus9Months = strtotime('+9 months', time());
    $this->_mem_date = date("m/d/Y", $nowMinus3Months);
    $memberships[0] = $this->cicContactMembershipCreate(199); // CSC Full Fee (member of contact #5)
    $memberships[1] = $this->cicContactMembershipCreate(165); // ACCN Print (member of contact #8)

    $form = $this->getForm($memberships[0]);
    $this->createLoggedInUser();
    $params = array(
      'action' => 'renew',
      'cid' => $this->_contactID,
      'id' => $memberships[0],
      'context' => "membership",
      'selectedChild' => "member",
      'snippet' => "json",

      'price_set_id' => "32",
      'price_364' => "767",
      'price_365' => array(
        '781' => "1",
        '782' => "1",
      ),
      'price_366' => "783",
      'renewal_date' => $this->_mem_date,
      'renewal_date_display_58fd51610b17b' => $this->_mem_date,
      'record_contribution' => "1",
      'soft_credit_type_id' => "11",
      'total_amount' => "206.85",
      'receive_date' => $this->_mem_date,
      'receive_date_display_58fd51610dcac' => $this->_mem_date,
      'receive_date_time' => "09:14PM",
      'financial_type_id' => "2",
      // 'payment_instrument_id' => "4",
      'contribution_status_id' => "1",
      // This format reflects the 23 being the organisation & the 25 being the type.
      // when renewing price set, there won't be a membershiptype.
      'membership_type_id' => array(23, NULL),
      'auto_renew' => '0',
      'is_recur' => 0,
      'max_related' => 0,
      'num_terms' => '1',
      'source' => '',
      //Member dues, see data.xml
      'soft_credit_contact_id' => '',
      'from_email_address' => '"Demonstrators Anonymous" <info@example.org>',
      'receipt_text' => 'Thank you text',
      'payment_processor_id' => $this->_paymentProcessorID,
      'credit_card_number' => '4111111111111111',
      'cvv2' => '123',
      'credit_card_exp_date' => array(
        'M' => '9',
        'Y' => date("Y") + 2,
      ),
      'credit_card_type' => 'Visa',
      'billing_first_name' => 'Test',
      'billing_middlename' => 'Last',
      'billing_street_address-5' => '10 Test St',
      'billing_city-5' => 'Test',
      'billing_state_province_id-5' => '1003',
      'billing_postal_code-5' => '90210',
      'billing_country_id-5' => '1228',
      'send_receipt' => 1,
      'join_date' => '',
      'start_date' => '',
      'end_date' => '',
      'campaign_id' => '',
    );
    $form->_contactID = $this->_contactID;

    $form->testSubmit($params);

    $actualData = $this->getConnection()->createQueryTable('memberships_etc', '
    select
  mem.membership_type_id,
  mem.join_date,
  mem.start_date,
  mem.end_date,
  mem.status_id as mem_status_id,
  con.financial_type_id as con_financial_type_id,
  con.payment_instrument_id,
  con.total_amount,
  con.fee_amount,
  con.net_amount,
  con.currency as con_currency,
  con.contribution_status_id,
  length(con.trxn_id) as trxn_id_ln,
  length(con.invoice_id) as invoice_id_ln,
  lit.qty,
  lit.unit_price,
  lit.line_total,
  lit.financial_type_id as lit_financial_type_id,
  date(fit.created_date) as created_date,
  date(fit.transaction_date) as transaction_date,
  fit.description,
  fit.amount,
  fit.currency as fit_currency,
  fit.financial_account_id,
  fit.status_id as fit_status_id
from civicrm_membership as mem
  left join civicrm_membership_payment pay on pay.membership_id = mem.id
  left join civicrm_contribution con on con.id = pay.contribution_id

  -- Should also be able to get to the financial type for each membership
  left join civicrm_line_item lit on
                                    lit.entity_table = \'civicrm_membership\'
                                    and lit.contribution_id = con.id
                                    and (select pfv.membership_type_id from civicrm_price_field_value pfv where pfv.id = lit.price_field_value_id) = mem.membership_type_id
  left join civicrm_financial_item fit on
                                    fit.entity_table = \'civicrm_line_item\'
                                    and fit.entity_id = lit.id


order by membership_type_id ');

    $expectedData1 = $this->createFlatXmlDataSet(dirname(__FILE__) . "/dataset/price_set_renewal_expected.xml")->getTable("memberships_etc");
    $expectedData2 = new PHPUnit_Extensions_Database_DataSet_ReplacementTable($expectedData1);
    $expectedData2->addSubStrReplacement("special-memyear", date("Y", $nowMinus3Months));
    $expectedData2->addSubStrReplacement("special-now+9m", date("Y-m-d", $nowPlus9Months));
    $expectedData2->addSubStrReplacement("special-now-3m", date("Y-m-d", $nowMinus3Months));
    $expectedData2->addSubStrReplacement("special-now", date("Y-m-d"));
    $this->assertTablesEqual($expectedData2, $actualData);

    // Use this to troubleshoot what's wrong!!!  Probably not required, but in my case, took for ever to realize, that
    // merge added an extra space in an expected result!
    //    $expectedRowCnt = $expectedData1->getRowCount();
    //    $expectedColCnt = sizeof($expectedData1->getRow(0));
    //    $this->assertEquals($expectedRowCnt, $actualData->getRowCount());
    //    for ($i = 0; $i < $expectedRowCnt; $i++) {
    //      $expectedRow = $expectedData1->getRow($i);
    //      $actualRow = $actualData->getRow($i);
    //      foreach ($expectedRow as $col => $expectValAsSimpleXMLElement) {
    //        $actualVal = $actualRow[$col];
    //        $expectVal = $expectValAsSimpleXMLElement->__toString();
    //        if ($expectVal == "special-now") {
    //          $expectVal = date("Y-m-d");
    //        }
    //        try {
    //          $this->assertEquals($expectVal, $actualVal, "Row #$i, column $col");
    //        } catch (Exception $e) {
    //          $this->assertEquals($expectVal, $actualVal, "Row #$i, column $col");
    //        }
    //      }
    //    }
  }

  /**
   * Clean up after each test.
   */
  public function tearDown() {
    try {
      // clear these values set when building form.  Tried doing in finally, but style police wasn't happy with that.
      unset($_SERVER['REQUEST_METHOD']);
      unset($_REQUEST['cid']);
      unset($_REQUEST['id']);

      // org contacts created.
      $this->contactDelete($this->_contactID);
      $op = new PHPUnit_Extensions_Database_Operation_Delete();
      $op->execute($this->_dbconn, $this->_dataset);

      $this->quickCleanup(
        array(
          'civicrm_address',
          'civicrm_membership',
          'civicrm_membership_type',
        )
      );

      $this->quickCleanUpFinancialEntities();

      for ($i = 5; $i < 13; $i++) {
        try {
          $this->contactDelete($i);
        }
        catch (Exception $e) {
          // ignore
        }
      }
    }
    catch (Exception $e) {
      throw $e;
    }
  }

  /**
   * Get a membership form object.
   *
   * We need to instantiate the form to run preprocess, which means we have to trick it about the request method.
   *
   * @param string $mode
   *
   * @return \CRM_Member_Form_MembershipRenewal
   */
  protected function getForm($membershipId, $mode = 'test') {
    $form = new CRM_Member_Form_MembershipRenewal();
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_REQUEST['cid'] = $this->_contactID;
    $_REQUEST['id'] = $membershipId;
    $form->controller = new CRM_Core_Controller();
    $form->_bltID = 5;
    $form->_mode = $mode;
    $form->buildForm();
    return $form;
  }

  public function cicContactMembershipCreate($membershipTypeId) {
    return $this->contactMembershipCreate(array(
      'contact_id' => $this->_contactID,
      'join_date' => $this->_mem_date,
      'start_date' => $this->_mem_date,
      'end_date' => $this->_mem_date,
      'status_id' => 3,
      'membership_type_id' => $membershipTypeId,
    ));
  }

}
