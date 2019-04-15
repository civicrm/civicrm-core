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
 *  Test CRM_Member_Form_Membership functions.
 *
 * @package   CiviCRM
 * @group headless
 */
class CRM_Member_Form_MembershipRenewalTest extends CiviUnitTestCase {

  protected $_individualId;
  protected $_contribution;
  protected $_financialTypeId = 1;
  protected $_apiversion;
  protected $_entity = 'Membership';
  protected $_params;
  protected $_ids = array();
  protected $_paymentProcessorID;

  /**
   * Membership type ID for annual fixed membership.
   *
   * @var int
   */
  protected $membershipTypeAnnualFixedID;

  /**
   * Parameters to create payment processor.
   *
   * @var array
   */
  protected $_processorParams = array();

  /**
   * ID of created membership.
   *
   * @var int
   */
  protected $_membershipID;

  /**
   * Payment instrument mapping.
   *
   * @var array
   */
  protected $paymentInstruments = array();


  /**
   * @var CiviMailUtils
   */
  protected $mut;

  /**
   * Test setup for every test.
   *
   * Connect to the database, truncate the tables that will be used
   * and redirect stdin to a temporary file.
   */
  public function setUp() {
    $this->_apiversion = 3;
    parent::setUp();

    $this->_individualId = $this->individualCreate();
    $this->_paymentProcessorID = $this->processorCreate();
    // Insert test data.
    $op = new PHPUnit_Extensions_Database_Operation_Insert();
    $op->execute($this->_dbconn,
      $this->createFlatXMLDataSet(
        dirname(__FILE__) . '/dataset/data.xml'
      )
    );
    $membershipTypeAnnualFixed = $this->callAPISuccess('membership_type', 'create', array(
      'domain_id' => 1,
      'name' => "AnnualFixed",
      'member_of_contact_id' => 23,
      'duration_unit' => "year",
      'duration_interval' => 1,
      'period_type' => "fixed",
      'fixed_period_start_day' => "101",
      'fixed_period_rollover_day' => "1231",
      'relationship_type_id' => 20,
      'financial_type_id' => 2,
    ));
    $this->membershipTypeAnnualFixedID = $membershipTypeAnnualFixed['id'];
    $membership = $this->callAPISuccess('Membership', 'create', array(
      'contact_id' => $this->_individualId,
      'membership_type_id' => $this->membershipTypeAnnualFixedID,
    ));
    $this->_membershipID = $membership['id'];

    $instruments = $this->callAPISuccess('contribution', 'getoptions', array('field' => 'payment_instrument_id'));
    $this->paymentInstruments = $instruments['values'];
  }

  /**
   * Clean up after each test.
   */
  public function tearDown() {
    $this->quickCleanUpFinancialEntities();
    $this->quickCleanup(
      array(
        'civicrm_relationship',
        'civicrm_membership_type',
        'civicrm_membership',
        'civicrm_uf_match',
        'civicrm_address',
      )
    );
    foreach (array(17, 18, 23, 32) as $contactID) {
      $this->callAPISuccess('contact', 'delete', array('id' => $contactID, 'skip_undelete' => TRUE));
    }
    $this->callAPISuccess('relationship_type', 'delete', array('id' => 20));
  }

  /**
   * Test the submit function of the membership form.
   */
  public function testSubmit() {
    $form = $this->getForm();
    $this->createLoggedInUser();
    $params = array(
      'cid' => $this->_individualId,
      'join_date' => date('m/d/Y', time()),
      'start_date' => '',
      'end_date' => '',
      // This format reflects the 23 being the organisation & the 25 being the type.
      'membership_type_id' => array(23, $this->membershipTypeAnnualFixedID),
      'auto_renew' => '0',
      'max_related' => '',
      'num_terms' => '1',
      'source' => '',
      'total_amount' => '50.00',
      //Member dues, see data.xml
      'financial_type_id' => '2',
      'soft_credit_type_id' => '',
      'soft_credit_contact_id' => '',
      'from_email_address' => '"Demonstrators Anonymous" <info@example.org>',
      'receipt_text_signup' => 'Thank you text',
      'payment_processor_id' => $this->_paymentProcessorID,
      'credit_card_number' => '4111111111111111',
      'cvv2' => '123',
      'credit_card_exp_date' => array(
        'M' => '9',
        // TODO: Future proof
        'Y' => '2024',
      ),
      'credit_card_type' => 'Visa',
      'billing_first_name' => 'Test',
      'billing_middlename' => 'Last',
      'billing_street_address-5' => '10 Test St',
      'billing_city-5' => 'Test',
      'billing_state_province_id-5' => '1003',
      'billing_postal_code-5' => '90210',
      'billing_country_id-5' => '1228',
    );
    $form->_contactID = $this->_individualId;

    $form->testSubmit($params);
    $membership = $this->callAPISuccessGetSingle('Membership', array('contact_id' => $this->_individualId));
    $this->callAPISuccessGetCount('ContributionRecur', array('contact_id' => $this->_individualId), 0);
    $contribution = $this->callAPISuccess('Contribution', 'get', array(
      'contact_id' => $this->_individualId,
      'is_test' => TRUE,
    ));

    $this->callAPISuccessGetCount('LineItem', array(
      'entity_id' => $membership['id'],
      'entity_table' => 'civicrm_membership',
      'contribution_id' => $contribution['id'],
    ), 1);
    $this->_checkFinancialRecords(array(
      'id' => $contribution['id'],
      'total_amount' => 50,
      'financial_account_id' => 2,
      'payment_instrument_id' => $this->callAPISuccessGetValue('PaymentProcessor', array(
        'id' => $this->_paymentProcessorID,
        'return' => 'payment_instrument_id',
      )),
    ), 'online');
  }

  /**
   * Test the submit function of the membership form.
   */
  public function testSubmitRecur() {
    $form = $this->getForm();

    $this->callAPISuccess('MembershipType', 'create', array(
      'id' => $this->membershipTypeAnnualFixedID,
      'duration_unit' => 'month',
      'duration_interval' => 1,
      'auto_renew' => TRUE,
    ));
    $form->preProcess();
    $this->createLoggedInUser();
    $params = array(
      'cid' => $this->_individualId,
      'price_set_id' => 0,
      'join_date' => date('m/d/Y', time()),
      'start_date' => '',
      'end_date' => '',
      'campaign_id' => '',
      // This format reflects the 23 being the organisation & the 25 being the type.
      'membership_type_id' => array(23, $this->membershipTypeAnnualFixedID),
      'auto_renew' => '1',
      'is_recur' => 1,
      'max_related' => 0,
      'num_terms' => '1',
      'source' => '',
      'total_amount' => '77.00',
      //Member dues, see data.xml
      'financial_type_id' => '2',
      'soft_credit_type_id' => 11,
      'soft_credit_contact_id' => '',
      'from_email_address' => '"Demonstrators Anonymous" <info@example.org>',
      'receipt_text' => 'Thank you text',
      'payment_processor_id' => $this->_paymentProcessorID,
      'credit_card_number' => '4111111111111111',
      'cvv2' => '123',
      'credit_card_exp_date' => array(
        'M' => '9',
        // TODO: Future proof
        'Y' => '2019',
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
    );
    $form->_mode = 'test';
    $form->_contactID = $this->_individualId;

    $form->testSubmit($params);
    $membership = $this->callAPISuccessGetSingle('Membership', array('contact_id' => $this->_individualId));
    $contributionRecur = $this->callAPISuccessGetSingle('ContributionRecur', array('contact_id' => $this->_individualId));
    $this->assertEquals(1, $contributionRecur['is_email_receipt']);
    $this->assertEquals(date('Y-m-d'), date('Y-m-d', strtotime($contributionRecur['modified_date'])));
    $this->assertEquals(date('Y-m-d'), date('Y-m-d', strtotime($contributionRecur['modified_date'])));
    $this->assertNotEmpty($contributionRecur['invoice_id']);
    $this->assertEquals(CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id',
      'Pending'), $contributionRecur['contribution_status_id']);
    $this->assertEquals($this->callAPISuccessGetValue('PaymentProcessor', array(
      'id' => $this->_paymentProcessorID,
      'return' => 'payment_instrument_id',
    )), $contributionRecur['payment_instrument_id']);

    $contribution = $this->callAPISuccess('Contribution', 'getsingle', array(
      'contact_id' => $this->_individualId,
      'is_test' => TRUE,
    ));

    $this->assertEquals($this->callAPISuccessGetValue('PaymentProcessor', array(
      'id' => $this->_paymentProcessorID,
      'return' => 'payment_instrument_id',
    )), $contribution['payment_instrument_id']);
    $this->assertEquals($contributionRecur['id'], $contribution['contribution_recur_id']);

    $this->callAPISuccessGetCount('LineItem', array(
      'entity_id' => $membership['id'],
      'entity_table' => 'civicrm_membership',
      'contribution_id' => $contribution['id'],
    ), 1);

    $this->callAPISuccessGetSingle('address', array(
      'contact_id' => $this->_individualId,
      'street_address' => '10 Test St',
      'postal_code' => 90210,
    ));
  }

  /**
   * Test the submit function of the membership form.
   */
  public function testSubmitRecurCompleteInstant() {
    $form = $this->getForm();
    /** @var \CRM_Core_Payment_Dummy $processor */
    $processor = Civi\Payment\System::singleton()->getById($this->_paymentProcessorID);
    $processor->setDoDirectPaymentResult(array(
      'payment_status_id' => 1,
      'trxn_id' => 'kettles boil water',
      'fee_amount' => .29,
    ));

    $this->callAPISuccess('MembershipType', 'create', array(
      'id' => $this->membershipTypeAnnualFixedID,
      'duration_unit' => 'month',
      'duration_interval' => 1,
      'auto_renew' => TRUE,
    ));
    $this->createLoggedInUser();
    $form->preProcess();

    $form->_contactID = $this->_individualId;
    $params = $this->getBaseSubmitParams();
    $form->_mode = 'test';

    $form->testSubmit($params);
    $membership = $this->callAPISuccessGetSingle('Membership', array('contact_id' => $this->_individualId));
    $contributionRecur = $this->callAPISuccessGetSingle('ContributionRecur', array('contact_id' => $this->_individualId));
    $this->assertEquals($contributionRecur['id'], $membership['contribution_recur_id']);
    $this->assertEquals(0, $contributionRecur['is_email_receipt']);
    $this->assertEquals(date('Y-m-d'), date('Y-m-d', strtotime($contributionRecur['modified_date'])));
    $this->assertNotEmpty($contributionRecur['invoice_id']);
    // @todo fix this part!
    /*
    $this->assertEquals(CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id',
    'In Progress'), $contributionRecur['contribution_status_id']);
    $this->assertNotEmpty($contributionRecur['next_sched_contribution_date']);
     */
    $paymentInstrumentID = $this->callAPISuccessGetValue('PaymentProcessor', array(
      'id' => $this->_paymentProcessorID,
      'return' => 'payment_instrument_id',
    ));
    $this->assertEquals($paymentInstrumentID, $contributionRecur['payment_instrument_id']);

    $contribution = $this->callAPISuccess('Contribution', 'getsingle', array(
      'contact_id' => $this->_individualId,
      'is_test' => TRUE,
    ));
    $this->assertEquals($paymentInstrumentID, $contribution['payment_instrument_id']);

    $this->assertEquals('kettles boil water', $contribution['trxn_id']);
    $this->assertEquals(.29, $contribution['fee_amount']);
    $this->assertEquals(7800.90, $contribution['total_amount']);
    $this->assertEquals(7800.61, $contribution['net_amount']);

    $this->callAPISuccessGetCount('LineItem', array(
      'entity_id' => $membership['id'],
      'entity_table' => 'civicrm_membership',
      'contribution_id' => $contribution['id'],
    ), 1);
  }

  /**
   * Test the submit function of the membership form.
   *
   * @param string $thousandSeparator
   *
   * @dataProvider getThousandSeparators
   */
  public function testSubmitRecurCompleteInstantWithMail($thousandSeparator) {
    $this->setCurrencySeparators($thousandSeparator);
    $form = $this->getForm();
    $this->mut = new CiviMailUtils($this, TRUE);
    /** @var \CRM_Core_Payment_Dummy $processor */
    $processor = Civi\Payment\System::singleton()->getById($this->_paymentProcessorID);
    $processor->setDoDirectPaymentResult(array(
      'payment_status_id' => 1,
      'trxn_id' => 'kettles boil water',
      'fee_amount' => .29,
    ));

    $this->callAPISuccess('MembershipType', 'create', array(
      'id' => $this->membershipTypeAnnualFixedID,
      'duration_unit' => 'month',
      'duration_interval' => 1,
      'auto_renew' => TRUE,
    ));
    $this->createLoggedInUser();
    $form->preProcess();

    $form->_contactID = $this->_individualId;
    $params = $this->getBaseSubmitParams();
    $params['send_receipt'] = 1;
    $form->_mode = 'test';

    $form->testSubmit($params);
    $contributionRecur = $this->callAPISuccessGetSingle('ContributionRecur', array('contact_id' => $this->_individualId));
    $this->assertEquals(1, $contributionRecur['is_email_receipt']);
    $this->mut->checkMailLog([
      '$ ' . $this->formatMoneyInput(7800.90),
    ]);
    $this->mut->stop();
    $this->setCurrencySeparators(',');
  }

  /**
   * Test the submit function of the membership form.
   */
  public function testSubmitPayLater() {
    $form = $this->getForm(NULL);
    $this->createLoggedInUser();
    $originalMembership = $this->callAPISuccessGetSingle('membership', array());
    $params = array(
      'cid' => $this->_individualId,
      'join_date' => date('m/d/Y', time()),
      'start_date' => '',
      'end_date' => '',
      // This format reflects the 23 being the organisation & the 25 being the type.
      'membership_type_id' => array(23, $this->membershipTypeAnnualFixedID),
      'auto_renew' => '0',
      'max_related' => '',
      'num_terms' => '2',
      'source' => '',
      'total_amount' => '50.00',
      //Member dues, see data.xml
      'financial_type_id' => '2',
      'soft_credit_type_id' => '',
      'soft_credit_contact_id' => '',
      'payment_instrument_id' => 4,
      'from_email_address' => '"Demonstrators Anonymous" <info@example.org>',
      'receipt_text_signup' => 'Thank you text',
      'payment_processor_id' => $this->_paymentProcessorID,
      'record_contribution' => TRUE,
      'trxn_id' => 777,
      'contribution_status_id' => 2,
    );
    $form->_contactID = $this->_individualId;

    $form->testSubmit($params);
    $membership = $this->callAPISuccessGetSingle('Membership', array('contact_id' => $this->_individualId));
    $this->assertEquals(strtotime($membership['end_date']), strtotime($originalMembership['end_date']));
    $contribution = $this->callAPISuccessGetSingle('Contribution', array(
      'contact_id' => $this->_individualId,
      'contribution_status_id' => 2,
      'return' => array("tax_amount", "trxn_id"),
    ));
    $this->assertEquals($contribution['trxn_id'], 777);
    $this->assertEquals($contribution['tax_amount'], NULL);

    $this->callAPISuccessGetCount('LineItem', array(
      'entity_id' => $membership['id'],
      'entity_table' => 'civicrm_membership',
      'contribution_id' => $contribution['id'],
    ), 1);
  }

  /**
   * Test the submit function of the membership form.
   */
  public function testSubmitPayLaterWithBilling() {
    $form = $this->getForm(NULL);
    $this->createLoggedInUser();
    $originalMembership = $this->callAPISuccessGetSingle('membership', array());
    $params = array(
      'cid' => $this->_individualId,
      'join_date' => date('m/d/Y', time()),
      'start_date' => '',
      'end_date' => '',
      // This format reflects the 23 being the organisation & the 25 being the type.
      'membership_type_id' => array(23, $this->membershipTypeAnnualFixedID),
      'auto_renew' => '0',
      'max_related' => '',
      'num_terms' => '2',
      'source' => '',
      'total_amount' => '50.00',
      //Member dues, see data.xml
      'financial_type_id' => '2',
      'soft_credit_type_id' => '',
      'soft_credit_contact_id' => '',
      'payment_instrument_id' => 4,
      'from_email_address' => '"Demonstrators Anonymous" <info@example.org>',
      'receipt_text_signup' => 'Thank you text',
      'payment_processor_id' => $this->_paymentProcessorID,
      'record_contribution' => TRUE,
      'trxn_id' => 777,
      'contribution_status_id' => 2,
      'billing_first_name' => 'Test',
      'billing_middlename' => 'Last',
      'billing_street_address-5' => '10 Test St',
      'billing_city-5' => 'Test',
      'billing_state_province_id-5' => '1003',
      'billing_postal_code-5' => '90210',
      'billing_country_id-5' => '1228',
    );
    $form->_contactID = $this->_individualId;

    $form->testSubmit($params);
    $membership = $this->callAPISuccessGetSingle('Membership', array('contact_id' => $this->_individualId));
    $this->assertEquals(strtotime($membership['end_date']), strtotime($originalMembership['end_date']));
    $contribution = $this->callAPISuccessGetSingle('Contribution', array(
      'contact_id' => $this->_individualId,
      'contribution_status_id' => 2,
    ));
    $this->assertEquals($contribution['trxn_id'], 777);

    $this->callAPISuccessGetCount('LineItem', array(
      'entity_id' => $membership['id'],
      'entity_table' => 'civicrm_membership',
      'contribution_id' => $contribution['id'],
    ), 1);
    $this->callAPISuccessGetSingle('address', array(
      'contact_id' => $this->_individualId,
      'street_address' => '10 Test St',
      'postal_code' => 90210,
    ));
  }

  /**
   * Test the submit function of the membership form.
   */
  public function testSubmitComplete() {
    $form = $this->getForm(NULL);
    $this->createLoggedInUser();
    $originalMembership = $this->callAPISuccessGetSingle('membership', array());
    $params = array(
      'cid' => $this->_individualId,
      'join_date' => date('m/d/Y', time()),
      'start_date' => '',
      'end_date' => '',
      // This format reflects the 23 being the organisation & the 25 being the type.
      'membership_type_id' => array(23, $this->membershipTypeAnnualFixedID),
      'auto_renew' => '0',
      'max_related' => '',
      'num_terms' => '2',
      'source' => '',
      'total_amount' => '50.00',
      //Member dues, see data.xml
      'financial_type_id' => '2',
      'soft_credit_type_id' => '',
      'soft_credit_contact_id' => '',
      'payment_instrument_id' => 4,
      'from_email_address' => '"Demonstrators Anonymous" <info@example.org>',
      'receipt_text_signup' => 'Thank you text',
      'payment_processor_id' => $this->_paymentProcessorID,
      'record_contribution' => TRUE,
      'trxn_id' => 777,
      'contribution_status_id' => 1,
      'fee_amount' => .5,
    );
    $form->_contactID = $this->_individualId;

    $form->testSubmit($params);
    $membership = $this->callAPISuccessGetSingle('Membership', array('contact_id' => $this->_individualId));
    $this->assertEquals(strtotime($membership['end_date']), strtotime('+ 2 years',
      strtotime($originalMembership['end_date'])));
    $contribution = $this->callAPISuccessGetSingle('Contribution', array(
      'contact_id' => $this->_individualId,
      'contribution_status_id' => 1,
    ));

    $this->assertEquals($contribution['trxn_id'], 777);
    $this->assertEquals(.5, $contribution['fee_amount']);
    $this->callAPISuccessGetCount('LineItem', array(
      'entity_id' => $membership['id'],
      'entity_table' => 'civicrm_membership',
      'contribution_id' => $contribution['id'],
    ), 1);
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
  protected function getForm($mode = 'test') {
    $form = new CRM_Member_Form_MembershipRenewal();
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $form->controller = new CRM_Core_Controller();
    $form->_bltID = 5;
    $form->_mode = $mode;
    $form->_id = $this->_membershipID;
    $form->preProcess();
    return $form;
  }

  /**
   * Get some re-usable parameters for the submit function.
   *
   * @return array
   */
  protected function getBaseSubmitParams() {
    $params = array(
      'cid' => $this->_individualId,
      'price_set_id' => 0,
      'join_date' => date('m/d/Y', time()),
      'start_date' => '',
      'end_date' => '',
      'campaign_id' => '',
      // This format reflects the 23 being the organisation & the 25 being the type.
      'membership_type_id' => array(23, $this->membershipTypeAnnualFixedID),
      'auto_renew' => '1',
      'is_recur' => 1,
      'max_related' => 0,
      'num_terms' => '1',
      'source' => '',
      'total_amount' => $this->formatMoneyInput('7800.90'),
      //Member dues, see data.xml
      'financial_type_id' => '2',
      'soft_credit_type_id' => 11,
      'soft_credit_contact_id' => '',
      'from_email_address' => '"Demonstrators Anonymous" <info@example.org>',
      'receipt_text' => 'Thank you text',
      'payment_processor_id' => $this->_paymentProcessorID,
      'credit_card_number' => '4111111111111111',
      'cvv2' => '123',
      'credit_card_exp_date' => array(
        'M' => '9',
        // TODO: Future proof
        'Y' => '2019',
      ),
      'credit_card_type' => 'Visa',
      'billing_first_name' => 'Test',
      'billing_middlename' => 'Last',
      'billing_street_address-5' => '10 Test St',
      'billing_city-5' => 'Test',
      'billing_state_province_id-5' => '1003',
      'billing_postal_code-5' => '90210',
      'billing_country_id-5' => '1228',
    );
    return $params;
  }

}
