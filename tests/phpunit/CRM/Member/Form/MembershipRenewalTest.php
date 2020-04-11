<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
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
  protected $_entity = 'Membership';
  protected $_params;
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
  protected $_processorParams = [];

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
  protected $paymentInstruments = [];


  /**
   * @var CiviMailUtils
   */
  protected $mut;

  /**
   * @var int
   */
  private $financialTypeID;

  /**
   * Test setup for every test.
   *
   * Connect to the database, truncate the tables that will be used
   * and redirect stdin to a temporary file.
   *
   * @throws \CRM_Core_Exception
   */
  public function setUp() {
    parent::setUp();

    $this->_individualId = $this->individualCreate();
    $this->_paymentProcessorID = $this->processorCreate();
    $this->financialTypeID = CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'financial_type_id', 'Member Dues');

    $this->loadXMLDataSet(dirname(__FILE__) . '/dataset/data.xml');
    $this->membershipTypeAnnualFixedID = $this->callAPISuccess('membership_type', 'create', [
      'domain_id' => 1,
      'name' => 'AnnualFixed',
      'member_of_contact_id' => 23,
      'duration_unit' => 'year',
      'duration_interval' => 1,
      'period_type' => 'fixed',
      'fixed_period_start_day' => '101',
      'fixed_period_rollover_day' => '1231',
      'relationship_type_id' => 20,
      'min_fee' => 100,
      'financial_type_id' => $this->financialTypeID,
    ])['id'];

    $this->_membershipID = $this->callAPISuccess('Membership', 'create', [
      'contact_id' => $this->_individualId,
      'membership_type_id' => $this->membershipTypeAnnualFixedID,
    ])['id'];

    $this->paymentInstruments = $this->callAPISuccess('Contribution', 'getoptions', ['field' => 'payment_instrument_id'])['values'];
  }

  /**
   * Clean up after each test.
   *
   * @throws \CRM_Core_Exception
   */
  public function tearDown() {
    $this->validateAllPayments();
    $this->validateAllContributions();
    $this->quickCleanUpFinancialEntities();
    $this->quickCleanup(
      [
        'civicrm_relationship',
        'civicrm_membership_type',
        'civicrm_membership',
        'civicrm_uf_match',
        'civicrm_address',
      ]
    );
    foreach ([17, 18, 23, 32] as $contactID) {
      $this->callAPISuccess('contact', 'delete', ['id' => $contactID, 'skip_undelete' => TRUE]);
    }
    $this->callAPISuccess('relationship_type', 'delete', ['id' => 20]);
  }

  /**
   * Test the submit function of the membership form.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testSubmit() {
    $form = $this->getForm();
    $this->createLoggedInUser();
    $params = [
      'cid' => $this->_individualId,
      'join_date' => date('m/d/Y', time()),
      'start_date' => '',
      'end_date' => '',
      // This format reflects the 23 being the organisation & the 25 being the type.
      'membership_type_id' => [23, $this->membershipTypeAnnualFixedID],
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
      'credit_card_exp_date' => [
        'M' => '9',
        // TODO: Future proof
        'Y' => '2024',
      ],
      'credit_card_type' => 'Visa',
      'billing_first_name' => 'Test',
      'billing_middlename' => 'Last',
      'billing_street_address-5' => '10 Test St',
      'billing_city-5' => 'Test',
      'billing_state_province_id-5' => '1003',
      'billing_postal_code-5' => '90210',
      'billing_country_id-5' => '1228',
    ];
    $form->_contactID = $this->_individualId;

    $form->testSubmit($params);
    $form->setRenewalMessage();
    $membership = $this->callAPISuccessGetSingle('Membership', ['contact_id' => $this->_individualId]);
    $this->callAPISuccessGetCount('ContributionRecur', ['contact_id' => $this->_individualId], 0);
    $contribution = $this->callAPISuccess('Contribution', 'get', [
      'contact_id' => $this->_individualId,
      'is_test' => TRUE,
    ]);

    $this->callAPISuccessGetCount('LineItem', [
      'entity_id' => $membership['id'],
      'entity_table' => 'civicrm_membership',
      'contribution_id' => $contribution['id'],
    ], 1);
    $this->_checkFinancialRecords([
      'id' => $contribution['id'],
      'total_amount' => 50,
      'financial_account_id' => 2,
      'payment_instrument_id' => $this->callAPISuccessGetValue('PaymentProcessor', [
        'id' => $this->_paymentProcessorID,
        'return' => 'payment_instrument_id',
      ]),
    ], 'online');
    $this->assertEquals([
      [
        'text' => 'AnnualFixed membership for Mr. Anthony Anderson II has been renewed.',
        'title' => 'Complete',
        'type' => 'success',
        'options' => NULL,
      ],
    ], CRM_Core_Session::singleton()->getStatus());
  }

  /**
   * Test submitting with tax enabled.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testSubmitWithTax() {
    $this->enableTaxAndInvoicing();
    $this->relationForFinancialTypeWithFinancialAccount($this->financialTypeID);
    $form = $this->getForm();

    $form->testSubmit([
      'cid' => $this->_individualId,
      'join_date' => date('Y-m-d'),
      'start_date' => '',
      'end_date' => '',
      // This format reflects the 23 being the organisation & the 25 being the type.
      'membership_type_id' => [23, $this->membershipTypeAnnualFixedID],
      'auto_renew' => '0',
      'max_related' => '',
      'num_terms' => '1',
      'source' => '',
      'total_amount' => '50.00',
      'financial_type_id' => $this->financialTypeID,
      'from_email_address' => '"Demonstrators Anonymous" <info@example.org>',
      'receipt_text_signup' => 'Thank you text',
      'payment_processor_id' => $this->_paymentProcessorID,
      'credit_card_number' => '4111111111111111',
      'cvv2' => '123',
      'credit_card_exp_date' => [
        'M' => '9',
        'Y' => date('Y') + 2,
      ],
      'credit_card_type' => 'Visa',
      'billing_first_name' => 'Test',
      'billing_middlename' => 'Last',
      'billing_street_address-5' => '10 Test St',
      'billing_city-5' => 'Test',
      'billing_state_province_id-5' => '1003',
      'billing_postal_code-5' => '90210',
      'billing_country_id-5' => '1228',
    ]);
    $contribution = $this->callAPISuccessGetSingle('Contribution', ['contact_id' => $this->_individualId, 'is_test' => TRUE, 'return' => ['total_amount', 'tax_amount']]);
    $this->assertEquals(50, $contribution['total_amount']);
    $this->assertEquals(5, $contribution['tax_amount']);
  }

  /**
   * Test the submit function of the membership form.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testSubmitRecur() {
    $form = $this->getForm();

    $this->callAPISuccess('MembershipType', 'create', [
      'id' => $this->membershipTypeAnnualFixedID,
      'duration_unit' => 'month',
      'duration_interval' => 1,
      'auto_renew' => TRUE,
    ]);
    $form->preProcess();
    $this->createLoggedInUser();
    $params = [
      'cid' => $this->_individualId,
      'price_set_id' => 0,
      'join_date' => date('m/d/Y', time()),
      'start_date' => '',
      'end_date' => '',
      'campaign_id' => '',
      // This format reflects the 23 being the organisation & the 25 being the type.
      'membership_type_id' => [23, $this->membershipTypeAnnualFixedID],
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
      'credit_card_exp_date' => [
        'M' => '9',
        'Y' => date('Y') + 1,
      ],
      'credit_card_type' => 'Visa',
      'billing_first_name' => 'Test',
      'billing_middlename' => 'Last',
      'billing_street_address-5' => '10 Test St',
      'billing_city-5' => 'Test',
      'billing_state_province_id-5' => '1003',
      'billing_postal_code-5' => '90210',
      'billing_country_id-5' => '1228',
      'send_receipt' => 1,
    ];
    $form->_mode = 'test';
    $form->_contactID = $this->_individualId;

    $form->testSubmit($params);
    $membership = $this->callAPISuccessGetSingle('Membership', ['contact_id' => $this->_individualId]);
    $contributionRecur = $this->callAPISuccessGetSingle('ContributionRecur', ['contact_id' => $this->_individualId]);
    $this->assertEquals(1, $contributionRecur['is_email_receipt']);
    $this->assertEquals(date('Y-m-d'), date('Y-m-d', strtotime($contributionRecur['modified_date'])));
    $this->assertEquals(date('Y-m-d'), date('Y-m-d', strtotime($contributionRecur['modified_date'])));
    $this->assertNotEmpty($contributionRecur['invoice_id']);
    $this->assertEquals(CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id',
      'Pending'), $contributionRecur['contribution_status_id']);
    $this->assertEquals($this->callAPISuccessGetValue('PaymentProcessor', [
      'id' => $this->_paymentProcessorID,
      'return' => 'payment_instrument_id',
    ]), $contributionRecur['payment_instrument_id']);

    $contribution = $this->callAPISuccess('Contribution', 'getsingle', [
      'contact_id' => $this->_individualId,
      'is_test' => TRUE,
    ]);

    $this->assertEquals($this->callAPISuccessGetValue('PaymentProcessor', [
      'id' => $this->_paymentProcessorID,
      'return' => 'payment_instrument_id',
    ]), $contribution['payment_instrument_id']);
    $this->assertEquals($contributionRecur['id'], $contribution['contribution_recur_id']);

    $this->callAPISuccessGetCount('LineItem', [
      'entity_id' => $membership['id'],
      'entity_table' => 'civicrm_membership',
      'contribution_id' => $contribution['id'],
    ], 1);

    $this->callAPISuccessGetSingle('address', [
      'contact_id' => $this->_individualId,
      'street_address' => '10 Test St',
      'postal_code' => 90210,
    ]);
  }

  /**
   * Test the submit function of the membership form.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testSubmitRecurCompleteInstant() {
    $form = $this->getForm();
    /** @var \CRM_Core_Payment_Dummy $processor */
    $processor = Civi\Payment\System::singleton()->getById($this->_paymentProcessorID);
    $processor->setDoDirectPaymentResult([
      'payment_status_id' => 1,
      'trxn_id' => 'kettles boil water',
      'fee_amount' => .29,
    ]);

    $this->callAPISuccess('MembershipType', 'create', [
      'id' => $this->membershipTypeAnnualFixedID,
      'duration_unit' => 'month',
      'duration_interval' => 1,
      'auto_renew' => TRUE,
    ]);
    $this->createLoggedInUser();
    $form->preProcess();

    $form->_contactID = $this->_individualId;
    $params = $this->getBaseSubmitParams();
    $form->_mode = 'test';

    $form->testSubmit($params);
    $membership = $this->callAPISuccessGetSingle('Membership', ['contact_id' => $this->_individualId]);
    $contributionRecur = $this->callAPISuccessGetSingle('ContributionRecur', ['contact_id' => $this->_individualId]);
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
    $paymentInstrumentID = $this->callAPISuccessGetValue('PaymentProcessor', [
      'id' => $this->_paymentProcessorID,
      'return' => 'payment_instrument_id',
    ]);
    $this->assertEquals($paymentInstrumentID, $contributionRecur['payment_instrument_id']);

    $contribution = $this->callAPISuccess('Contribution', 'getsingle', [
      'contact_id' => $this->_individualId,
      'is_test' => TRUE,
    ]);
    $this->assertEquals($paymentInstrumentID, $contribution['payment_instrument_id']);

    $this->assertEquals('kettles boil water', $contribution['trxn_id']);
    $this->assertEquals(.29, $contribution['fee_amount']);
    $this->assertEquals(7800.90, $contribution['total_amount']);
    $this->assertEquals(7800.61, $contribution['net_amount']);

    $this->callAPISuccessGetCount('LineItem', [
      'entity_id' => $membership['id'],
      'entity_table' => 'civicrm_membership',
      'contribution_id' => $contribution['id'],
    ], 1);
  }

  /**
   * Test the submit function of the membership form.
   *
   * @param string $thousandSeparator
   *
   * @dataProvider getThousandSeparators
   *
   * @throws \CiviCRM_API3_Exception
   * @throws \CRM_Core_Exception
   */
  public function testSubmitRecurCompleteInstantWithMail($thousandSeparator) {
    $this->setCurrencySeparators($thousandSeparator);
    $form = $this->getForm();
    $this->mut = new CiviMailUtils($this, TRUE);
    /** @var \CRM_Core_Payment_Dummy $processor */
    $processor = Civi\Payment\System::singleton()->getById($this->_paymentProcessorID);
    $processor->setDoDirectPaymentResult([
      'payment_status_id' => 1,
      'trxn_id' => 'kettles boil water',
      'fee_amount' => .29,
    ]);

    $this->callAPISuccess('MembershipType', 'create', [
      'id' => $this->membershipTypeAnnualFixedID,
      'duration_unit' => 'month',
      'duration_interval' => 1,
      'auto_renew' => TRUE,
    ]);
    $this->createLoggedInUser();
    $form->preProcess();

    $form->_contactID = $this->_individualId;
    $params = $this->getBaseSubmitParams();
    $params['send_receipt'] = 1;
    $form->_mode = 'test';

    $form->testSubmit($params);
    $contributionRecur = $this->callAPISuccessGetSingle('ContributionRecur', ['contact_id' => $this->_individualId]);
    $this->assertEquals(1, $contributionRecur['is_email_receipt']);
    $this->mut->checkMailLog([
      '$ ' . $this->formatMoneyInput(7800.90),
    ]);
    $this->mut->stop();
    $this->setCurrencySeparators(',');
  }

  /**
   * Test the submit function of the membership form.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testSubmitPayLater() {
    $form = $this->getForm(NULL);
    $this->createLoggedInUser();
    $originalMembership = $this->callAPISuccessGetSingle('membership', []);
    $params = [
      'cid' => $this->_individualId,
      'join_date' => date('m/d/Y', time()),
      'start_date' => '',
      'end_date' => '',
      // This format reflects the 23 being the organisation & the 25 being the type.
      'membership_type_id' => [23, $this->membershipTypeAnnualFixedID],
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
    ];
    $form->_contactID = $this->_individualId;

    $form->testSubmit($params);
    $membership = $this->callAPISuccessGetSingle('Membership', ['contact_id' => $this->_individualId]);
    $this->assertEquals(strtotime($membership['end_date']), strtotime($originalMembership['end_date']));
    $contribution = $this->callAPISuccessGetSingle('Contribution', [
      'contact_id' => $this->_individualId,
      'contribution_status_id' => 2,
      'return' => ['tax_amount', 'trxn_id'],
    ]);
    $this->assertEquals($contribution['trxn_id'], 777);
    $this->assertEquals(NULL, $contribution['tax_amount']);

    $this->callAPISuccessGetCount('LineItem', [
      'entity_id' => $membership['id'],
      'entity_table' => 'civicrm_membership',
      'contribution_id' => $contribution['id'],
    ], 1);
  }

  /**
   * Test the submit function of the membership form.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testSubmitPayLaterWithBilling() {
    $form = $this->getForm(NULL);
    $this->createLoggedInUser();
    $originalMembership = $this->callAPISuccessGetSingle('membership', []);
    $params = [
      'cid' => $this->_individualId,
      'join_date' => date('m/d/Y', time()),
      'start_date' => '',
      'end_date' => '',
      // This format reflects the 23 being the organisation & the 25 being the type.
      'membership_type_id' => [23, $this->membershipTypeAnnualFixedID],
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
    ];
    $form->_contactID = $this->_individualId;

    $form->testSubmit($params);
    $membership = $this->callAPISuccessGetSingle('Membership', ['contact_id' => $this->_individualId]);
    $this->assertEquals(strtotime($membership['end_date']), strtotime($originalMembership['end_date']));
    $contribution = $this->callAPISuccessGetSingle('Contribution', [
      'contact_id' => $this->_individualId,
      'contribution_status_id' => 2,
    ]);
    $this->assertEquals($contribution['trxn_id'], 777);

    $this->callAPISuccessGetCount('LineItem', [
      'entity_id' => $membership['id'],
      'entity_table' => 'civicrm_membership',
      'contribution_id' => $contribution['id'],
    ], 1);
    $this->callAPISuccessGetSingle('address', [
      'contact_id' => $this->_individualId,
      'street_address' => '10 Test St',
      'postal_code' => 90210,
    ]);
  }

  /**
   * Test the submit function of the membership form.
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function testSubmitComplete() {
    $form = $this->getForm(NULL);
    $this->createLoggedInUser();
    $originalMembership = $this->callAPISuccessGetSingle('membership', []);
    $params = [
      'cid' => $this->_individualId,
      'join_date' => date('m/d/Y', time()),
      'start_date' => '',
      'end_date' => '',
      // This format reflects the 23 being the organisation & the 25 being the type.
      'membership_type_id' => [23, $this->membershipTypeAnnualFixedID],
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
    ];
    $form->_contactID = $this->_individualId;

    $form->testSubmit($params);
    $membership = $this->callAPISuccessGetSingle('Membership', ['contact_id' => $this->_individualId]);
    $this->assertEquals(strtotime($membership['end_date']), strtotime('+ 2 years',
      strtotime($originalMembership['end_date'])));
    $contribution = $this->callAPISuccessGetSingle('Contribution', [
      'contact_id' => $this->_individualId,
      'contribution_status_id' => 1,
    ]);

    $this->assertEquals($contribution['trxn_id'], 777);
    $this->assertEquals(.5, $contribution['fee_amount']);
    $this->callAPISuccessGetCount('LineItem', [
      'entity_id' => $membership['id'],
      'entity_table' => 'civicrm_membership',
      'contribution_id' => $contribution['id'],
    ], 1);
  }

  /**
   * Get a membership form object.
   *
   * We need to instantiate the form to run preprocess, which means we have to trick it about the request method.
   *
   * @param string $mode
   *
   * @return \CRM_Member_Form_MembershipRenewal
   *
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
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
    return [
      'cid' => $this->_individualId,
      'price_set_id' => 0,
      'join_date' => date('m/d/Y', time()),
      'start_date' => '',
      'end_date' => '',
      'campaign_id' => '',
      // This format reflects the 23 being the organisation & the 25 being the type.
      'membership_type_id' => [23, $this->membershipTypeAnnualFixedID],
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
      'credit_card_exp_date' => [
        'M' => '9',
        'Y' => date('Y') + 1,
      ],
      'credit_card_type' => 'Visa',
      'billing_first_name' => 'Test',
      'billing_middlename' => 'Last',
      'billing_street_address-5' => '10 Test St',
      'billing_city-5' => 'Test',
      'billing_state_province_id-5' => '1003',
      'billing_postal_code-5' => '90210',
      'billing_country_id-5' => '1228',
    ];
  }

}
