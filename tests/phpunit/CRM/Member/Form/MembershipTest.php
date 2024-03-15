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
 *  File for the MembershipTest class
 *
 *  (PHP 5)
 *
 * @author Walt Haas <walt@dharmatech.org> (801) 534-1262
 */

use Civi\Api4\FinancialType;
use Civi\Api4\Membership;
use Civi\Api4\MembershipType;
use Civi\Api4\PriceFieldValue;
use Civi\Test\FormTrait;

/**
 *  Test CRM_Member_Form_Membership functions.
 *
 * @package   CiviCRM
 * @group headless
 */
class CRM_Member_Form_MembershipTest extends CiviUnitTestCase {

  use CRMTraits_Financial_OrderTrait;
  use CRMTraits_Financial_PriceSetTrait;
  use FormTrait;

  /**
   * Test setup for every test.
   *
   * Connect to the database, truncate the tables that will be used
   * and redirect stdin to a temporary file.
   */
  public function setUp(): void {
    parent::setUp();

    $this->individualCreate();
    $this->processorCreate();

    $this->organizationCreate([], 'organization');
    $this->organizationCreate([], 'organization2');

    $this->createTestEntity('RelationshipType', [
      'name_a_b' => 'Member of',
      'label_a_b' => 'Member of',
      'name_b_a' => 'Member is',
      'label_b_a' => 'Member is',
      'contact_type_a' => 'Individual',
      'contact_type_b' => 'Organization',
    ], 'member');

    $this->createTestEntity('MembershipType', [
      'domain_id' => 1,
      'name' => 'AnnualFixed',
      'member_of_contact_id' => $this->ids['Contact']['organization'],
      'duration_unit' => 'year',
      'minimum_fee' => 50,
      'duration_interval' => 1,
      'period_type' => 'fixed',
      'fixed_period_start_day' => '101',
      'fixed_period_rollover_day' => '1231',
      'relationship_type_id' => [$this->ids['RelationshipType']['member']],
      'relationship_direction' => ['b_a'],
      'financial_type_id:name' => 'Member Dues',
    ], 'AnnualFixed');

    $this->createTestEntity('MembershipType', [
      'name' => 'AnnualRolling',
      'member_of_contact_id' => $this->ids['Contact']['organization'],
      'duration_unit' => 'year',
      'duration_interval' => 1,
      'period_type' => 'rolling',
      'relationship_type_id' => [$this->ids['RelationshipType']['member']],
      'relationship_direction' => ['b_a'],
      'financial_type_id:name' => 'Member Dues',
    ], 'AnnualRolling');

    $this->createTestEntity('MembershipType', [
      'name' => 'AnnualRolling1',
      'member_of_contact_id' => $this->ids['Contact']['organization2'],
      'duration_unit' => 'year',
      'duration_interval' => 1,
      'period_type' => 'rolling',
      'relationship_type_id' => [$this->ids['RelationshipType']['member']],
      'relationship_direction' => ['b_a'],
      'financial_type_id:name' => 'Member Dues',
    ], 'AnnualRollingOrg2');

    $this->createTestEntity('MembershipType', [
      'name' => 'Lifetime',
      'member_of_contact_id' => $this->ids['Contact']['organization'],
      'duration_unit' => 'lifetime',
      'duration_interval' => 1,
      'relationship_type_id' => $this->ids['RelationshipType']['member'],
      'relationship_direction' => 'b_a',
      'financial_type_id:name' => 'Member Dues',
      'period_type' => 'rolling',
    ], 'lifetime');
  }

  /**
   * Clean up after each test.
   */
  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    $this->quickCleanup(
      [
        'civicrm_relationship',
        'civicrm_uf_match',
        'civicrm_email',
      ]
    );
    $this->callAPISuccess('Contact', 'delete', ['id' => $this->ids['Contact']['organization'], 'skip_undelete' => TRUE]);
    $this->callAPISuccess('RelationshipType', 'delete', ['id' => $this->ids['RelationshipType']['member']]);
    parent::tearDown();
  }

  /**
   *  Test CRM_Member_Form_Membership::formRule() with a parameter
   *  that has an empty contact_select_id value
   *
   * @throws \CRM_Core_Exception
   */
  public function testFormRuleEmptyContact(): void {
    $params = [
      'contact_select_id' => 0,
      'membership_type_id' => [1 => NULL],
    ];
    $files = [];
    $obj = new CRM_Member_Form_Membership();
    $rc = CRM_Member_Form_Membership::formRule($params, $files, $obj);
    $this->assertIsArray($rc);
    $this->assertArrayHasKey('membership_type_id', $rc);

    $params['membership_type_id'] = [1 => 3];
    $rc = CRM_Member_Form_Membership::formRule($params, $files, $obj);
    $this->assertIsArray($rc);
    $this->assertArrayHasKey('join_date', $rc);
  }

  /**
   * Test that form rule fails if start date is before join date.
   *
   * Test CRM_Member_Form_Membership::formRule() with a parameter
   * that has an start date before the join date and a rolling
   * membership type.
   *
   * @throws \CRM_Core_Exception
   */
  public function testFormRuleRollingEarlyStart(): void {
    $unixNow = time();
    $unixYesterday = $unixNow - (24 * 60 * 60);
    $ymdYesterday = date('Y-m-d', $unixYesterday);
    $params = [
      'join_date' => date('Y-m-d'),
      'start_date' => $ymdYesterday,
      'end_date' => '',
      'membership_type_id' => [$this->ids['Contact']['organization'], $this->ids['MembershipType']['AnnualRolling']],
    ];
    $files = [];
    $obj = new CRM_Member_Form_Membership();
    $rc = CRM_Member_Form_Membership::formRule($params, $files, $obj);
    $this->assertIsArray($rc);
    $this->assertArrayHasKey('start_date', $rc);
  }

  /**
   *  Test CRM_Member_Form_Membership::formRule() with a parameter
   *  that has an end date before the start date and a rolling
   *  membership type
   *
   * @throws \CRM_Core_Exception
   */
  public function testFormRuleRollingEarlyEnd(): void {
    $unixYesterday = time() - (24 * 60 * 60);
    $ymdYesterday = date('Y-m-d', $unixYesterday);
    $params = [
      'join_date' => date('Y-m-d'),
      'start_date' => date('Y-m-d'),
      'end_date' => $ymdYesterday,
      'membership_type_id' => [$this->ids['Contact']['organization'], $this->ids['MembershipType']['AnnualRolling']],
    ];
    $files = [];
    $obj = new CRM_Member_Form_Membership();
    $rc = CRM_Member_Form_Membership::formRule($params, $files, $obj);
    $this->assertIsArray($rc);
    $this->assertArrayHasKey('end_date', $rc);
  }

  /**
   *  Test CRM_Member_Form_Membership::formRule() with end date but no start
   * date and a rolling membership type.
   *
   * @throws \CRM_Core_Exception
   */
  public function testFormRuleRollingEndNoStart(): void {
    $unixNow = time();
    $unixYearFromNow = $unixNow + (365 * 24 * 60 * 60);
    $ymdYearFromNow = date('Y-m-d', $unixYearFromNow);
    $params = [
      'join_date' => date('Y-m-d'),
      'start_date' => '',
      'end_date' => $ymdYearFromNow,
      'membership_type_id' => [$this->ids['Contact']['organization'], $this->ids['MembershipType']['AnnualRolling']],
    ];
    $files = [];
    $obj = new CRM_Member_Form_Membership();
    $ruleResult = $obj::formRule($params, $files, $obj);
    $this->assertIsArray($ruleResult);
    $this->assertArrayHasKey('start_date', $ruleResult);
  }

  /**
   *  Test CRM_Member_Form_Membership::formRule() with a parameter
   *  that has an end date and a lifetime membership type
   *
   * @throws \CRM_Core_Exception
   */
  public function testFormRuleRollingLifetimeEnd(): void {
    $unixNow = time();
    $unixYearFromNow = $unixNow + (365 * 24 * 60 * 60);
    $params = [
      'join_date' => date('Y-m-d'),
      'start_date' => date('Y-m-d'),
      'end_date' => date('Y-m-d',
        $unixYearFromNow
      ),
      'membership_type_id' => [$this->ids['Contact']['organization'], $this->ids['MembershipType']['lifetime']],
    ];
    $files = [];
    $obj = new CRM_Member_Form_Membership();
    $rc = $obj::formRule($params, $files, $obj);
    $this->assertIsArray($rc);
    $this->assertArrayHasKey('status_id', $rc);
  }

  /**
   *  Test CRM_Member_Form_Membership::formRule() with a parameter
   *  that has permanent override and no status
   *
   * @throws \CRM_Core_Exception
   */
  public function testFormRulePermanentOverrideWithNoStatus(): void {
    $params = [
      'join_date' => date('Y-m-d'),
      'membership_type_id' => [$this->ids['Contact']['organization'], $this->ids['MembershipType']['AnnualFixed']],
      'is_override' => TRUE,
    ];
    $files = [];
    $obj = new CRM_Member_Form_Membership();
    $rc = $obj::formRule($params, $files, $obj);
    $this->assertIsArray($rc);
    $this->assertTrue(array_key_exists('status_id', $rc));
  }

  public function testFormRuleUntilDateOverrideWithValidOverrideEndDate(): void {
    $params = [
      'join_date' => date('Y-m-d'),
      'membership_type_id' => [$this->ids['Contact']['organization'], $this->ids['MembershipType']['AnnualFixed']],
      'is_override' => TRUE,
      'status_id' => 1,
      'status_override_end_date' => date('Y-m-d'),
    ];
    $files = [];
    $membershipForm = new CRM_Member_Form_Membership();
    $validationResponse = CRM_Member_Form_Membership::formRule($params, $files, $membershipForm);
    $this->assertTrue($validationResponse);
  }

  /**
   * @throws \CRM_Core_Exception
   */
  public function testFormRuleUntilDateOverrideWithNoOverrideEndDate(): void {
    $params = [
      'join_date' => date('Y-m-d'),
      'membership_type_id' => [$this->ids['Contact']['organization'], $this->ids['MembershipType']['AnnualFixed']],
      'is_override' => CRM_Member_StatusOverrideTypes::UNTIL_DATE,
      'status_id' => 1,
    ];
    $files = [];
    $membershipForm = new CRM_Member_Form_Membership();
    $validationResponse = CRM_Member_Form_Membership::formRule($params, $files, $membershipForm);
    $this->assertIsArray($validationResponse);
    $this->assertEquals('Please enter the Membership override end date.', $validationResponse['status_override_end_date']);
  }

  /**
   *  Test CRM_Member_Form_Membership::formRule() with a join date
   *  of one month from now and a rolling membership type
   */
  public function testFormRuleRollingJoin1MonthFromNow(): void {
    $unixNow = time();
    $unix1MFmNow = $unixNow + (31 * 24 * 60 * 60);
    $params = [
      'join_date' => date('Y-m-d', $unix1MFmNow),
      'start_date' => '',
      'end_date' => '',
      'membership_type_id' => [$this->ids['Contact']['organization'], $this->ids['MembershipType']['AnnualRolling']],
    ];
    $files = [];
    $obj = new CRM_Member_Form_Membership();
    $rc = $obj::formRule($params, $files, $obj);

    // Should have found no valid membership status.
    $this->assertIsArray($rc);
    $this->assertArrayHasKey('_qf_default', $rc);
  }

  /**
   * Test CRM_Member_Form_Membership::formRule() with a join date of today and a rolling membership type.
   */
  public function testFormRuleRollingJoinToday(): void {
    $params = [
      'join_date' => date('Y-m-d'),
      'start_date' => '',
      'end_date' => '',
      'membership_type_id' => [$this->ids['Contact']['organization'], $this->ids['MembershipType']['AnnualRolling']],
    ];
    $files = [];
    $obj = new CRM_Member_Form_Membership();
    $rc = $obj::formRule($params, $files, $obj);

    //  Should have found New membership status
    $this->assertTrue($rc);
  }

  /**
   *  Test CRM_Member_Form_Membership::formRule() with a join date
   *  of one month ago and a rolling membership type
   *
   * @throws \CRM_Core_Exception
   */
  public function testFormRuleRollingJoin1MonthAgo(): void {
    $unixNow = time();
    $unix1MAgo = $unixNow - (31 * 24 * 60 * 60);
    $params = [
      'join_date' => date('Y-m-d', $unix1MAgo),
      'start_date' => '',
      'end_date' => '',
      'membership_type_id' => [$this->ids['Contact']['organization'], $this->ids['MembershipType']['AnnualRolling']],
    ];
    $files = [];
    $obj = new CRM_Member_Form_Membership();
    $rc = $obj::formRule($params, $files, $obj);

    // Should have found New membership status.
    $this->assertTrue($rc);
  }

  /**
   *  Test CRM_Member_Form_Membership::formRule() with a join date of six months ago and a rolling membership type.
   */
  public function testFormRuleRollingJoin6MonthsAgo(): void {
    $unixNow = time();
    $unix6MAgo = $unixNow - (180 * 24 * 60 * 60);
    $params = [
      'join_date' => date('Y-m-d', $unix6MAgo),
      'start_date' => '',
      'end_date' => '',
      'membership_type_id' => [$this->ids['Contact']['organization'], $this->ids['MembershipType']['AnnualRolling']],
    ];
    $files = [];
    $obj = new CRM_Member_Form_Membership();
    $rc = $obj::formRule($params, $files, $obj);

    // Should have found Current membership status.
    $this->assertTrue($rc);
  }

  /**
   *  Test CRM_Member_Form_Membership::formRule() with a join date
   *  of one year+ ago and a rolling membership type
   */
  public function testFormRuleRollingJoin1YearAgo(): void {
    $unixNow = time();
    $unix1YAgo = $unixNow - (370 * 24 * 60 * 60);
    $params = [
      'join_date' => date('Y-m-d', $unix1YAgo),
      'start_date' => '',
      'end_date' => '',
      'membership_type_id' => [$this->ids['Contact']['organization'], $this->ids['MembershipType']['AnnualRolling']],
    ];
    $files = [];
    $obj = new CRM_Member_Form_Membership();
    $rc = $obj::formRule($params, $files, $obj);

    //  Should have found Grace membership status
    $this->assertTrue($rc);
  }

  /**
   *  Test CRM_Member_Form_Membership::formRule() with a join date
   *  of two years ago and a rolling membership type
   */
  public function testFormRuleRollingJoin2YearsAgo(): void {
    $unixNow = time();
    $unix2YAgo = $unixNow - (2 * 365 * 24 * 60 * 60);
    $params = [
      'join_date' => date('Y-m-d', $unix2YAgo),
      'start_date' => '',
      'end_date' => '',
      'membership_type_id' => [$this->ids['Contact']['organization'], $this->ids['MembershipType']['AnnualRolling']],
    ];
    $files = [];
    $obj = new CRM_Member_Form_Membership();
    $rc = $obj::formRule($params, $files, $obj);

    //  Should have found Expired membership status
    $this->assertTrue($rc);
  }

  /**
   *  Test CRM_Member_Form_Membership::formRule() with a current status.
   *
   * The setup is a join date of six months ago and a fixed membership type.
   */
  public function testFormRuleFixedJoin6MonthsAgo(): void {
    $unixNow = time();
    $unix6MAgo = $unixNow - (180 * 24 * 60 * 60);
    $params = [
      'join_date' => date('Y-m-d', $unix6MAgo),
      'start_date' => '',
      'end_date' => '',
      'membership_type_id' => [$this->ids['Contact']['organization'], $this->ids['MembershipType']['AnnualFixed']],
    ];
    $files = [];
    $obj = new CRM_Member_Form_Membership();
    $rc = $obj::formRule($params, $files, $obj);

    //  Should have found Current membership status
    $this->assertTrue($rc);
  }

  /**
   * Test the submit function of the membership form.
   *
   * @param string $thousandSeparator
   *
   * @throws \CRM_Core_Exception
   *
   * @dataProvider getThousandSeparators
   */
  public function testSubmit(string $thousandSeparator): void {
    $_REQUEST['mode'] = 'test';
    $this->setCurrencySeparators($thousandSeparator);
    $this->createLoggedInUser();
    $params = [
      'cid' => $this->ids['Contact']['individual_0'],
      'contact_id' => $this->ids['Contact']['individual_0'],
      'join_date' => date('Y-m-d'),
      // This format reflects the organisation & then the type.
      'membership_type_id' => [$this->ids['Contact']['organization'], $this->ids['MembershipType']['AnnualFixed']],
      'auto_renew' => '0',
      'max_related' => '',
      'num_terms' => 2,
      'source' => '',
      'total_amount' => $this->formatMoneyInput(1234.56),
      //Member dues, see data.xml
      'financial_type_id' => '2',
      'soft_credit_type_id' => '',
      'soft_credit_contact_id' => '',
      'from_email_address' => '"Demonstrators Anonymous" <info@example.org>',
      'payment_processor_id' => $this->ids['PaymentProcessor']['dummy'],
      'credit_card_number' => '4111111111111111',
      'cvv2' => '123',
      'credit_card_exp_date' => [
        'M' => '9',
        'Y' => date('Y', strtotime('+ 2 years')),
      ],
      'credit_card_type' => 'Visa',
      'billing_first_name' => 'Test',
      'billing_last_name' => 'Last',
      'billing_street_address-5' => '10 Test St',
      'billing_city-5' => 'Test',
      'billing_state_province_id-5' => '1003',
      'billing_postal_code-5' => '90210',
      'billing_country_id-5' => '1228',
      'send_receipt' => TRUE,
      'receipt_text' => 'Receipt text',
    ];
    $form = $this->getForm($params);
    $mailUtil = new CiviMailUtils($this, TRUE);
    $form->buildForm();
    $form->postProcess();
    $membership = $this->callAPISuccessGetSingle('Membership', ['contact_id' => $this->ids['Contact']['individual_0']]);
    $this->assertEquals($form->getMembershipID(), $membership['id']);
    $membershipEndYear = date('Y') + 1;
    if (date('m-d') === '12-31') {
      // If you join on Dec 31, then the first term would end right away, so
      // add a year.
      $membershipEndYear++;
    }
    $this->assertEquals($membershipEndYear . '-12-31', $membership['end_date']);
    $this->callAPISuccessGetCount('ContributionRecur', ['contact_id' => $this->ids['Contact']['individual_0']], 0);
    $contribution = $this->callAPISuccess('Contribution', 'get', [
      'contact_id' => $this->ids['Contact']['individual_0'],
      'is_test' => TRUE,
    ]);

    //CRM-20264 : Check that CC type and number (last 4 digit) is stored during backoffice membership payment
    $financialTrxn = $this->callAPISuccessGetSingle(
      'Payment',
      [
        'contribution_id' => $contribution['id'],
        'return' => ['card_type_id', 'pan_truncation'],
      ]
    );
    $this->assertEquals(1, $financialTrxn['card_type_id']);
    $this->assertEquals(1111, $financialTrxn['pan_truncation']);

    $this->callAPISuccessGetCount('LineItem', [
      'entity_id' => $membership['id'],
      'entity_table' => 'civicrm_membership',
      'contribution_id' => $contribution['id'],
    ], 1);

    $this->_checkFinancialRecords([
      'id' => $contribution['id'],
      'total_amount' => 1234.56,
      'financial_account_id' => 2,
      'payment_instrument_id' => $this->callAPISuccessGetValue('PaymentProcessor', [
        'id' => $this->ids['PaymentProcessor']['dummy'],
        'return' => 'payment_instrument_id',
      ]),
    ], 'online');
    $mailUtil->checkMailLog([
      Civi::format()->money('1234.56'),
      'Receipt text',
    ]);
    $this->assertEquals([
      [
        'text' => 'AnnualFixed membership for Mr. Anthony Anderson II has been added. The new Membership Expiration Date is December 31st, ' . $membershipEndYear . '. A membership confirmation and receipt has been sent to anthony_anderson@civicrm.org.',
        'title' => 'Complete',
        'type' => 'success',
        'options' => NULL,
      ],
    ], CRM_Core_Session::singleton()->getStatus());

    // Check if Membership is set to New.
    $this->assertEquals(CRM_Core_PseudoConstant::getKey('CRM_Member_BAO_Membership', 'status_id', 'New'), $membership['status_id']);
  }

  /**
   * Test the submit function of the membership form for free membership.
   *
   * It turns out that no receipt is sent. This just locks in that pre-existing
   * behaviour.
   *
   * @throws \CRM_Core_Exception
   */
  public function testSubmitFree(): void {
    $mailUtil = new CiviMailUtils($this, TRUE);
    $this->createLoggedInUser();
    MembershipType::update()->addWhere('id', '=', $this->ids['MembershipType']['AnnualFixed'])
      ->setValues(['minimum_fee' => 0])->execute();
    $form = $this->getForm([
      'contact_id' => $this->ids['Contact']['individual_0'],
      'join_date' => date('Y-m-d'),
      'membership_type_id' => [$this->ids['Contact']['organization'], $this->ids['MembershipType']['AnnualFixed']],
      'total_amount' => 0,
      'from_email_address' => '"Demonstrators Anonymous" <info@example.org>',
      'send_receipt' => TRUE,
      'receipt_text' => 'Receipt text',
      'financial_type_id' => '',
    ]);
    $form->postProcess();

    // Check if Membership is set to New.
    $membership = $this->callAPISuccessGetSingle('Membership', ['contact_id' => $this->ids['Contact']['individual_0']]);
    $this->assertEquals(CRM_Core_PseudoConstant::getKey('CRM_Member_BAO_Membership', 'status_id', 'New'), $membership['status_id']);

    $mailUtil->checkMailLog([], [
      'Membership',
      'Receipt text',
    ]);
  }

  /**
   * Test the submit function of the membership form for paid membership when we don't record a payment.
   * "Expected result" - ie. what happens now! is that Membership is created with status "New" and no contribution is created.
   *
   * @throws \CRM_Core_Exception
   */
  public function testSubmitPaidNoPayment(): void {
    $this->createLoggedInUser();
    $form = $this->getForm([
      'contact_id' => $this->ids['Contact']['individual_0'],
      'join_date' => date('Y-m-d'),
      'membership_type_id' => [$this->ids['Contact']['organization'], $this->ids['MembershipType']['AnnualFixed']],
      'total_amount' => 50,
      'from_email_address' => '"Demonstrators Anonymous" <info@example.org>',
      'send_receipt' => TRUE,
      'receipt_text' => 'Receipt text',
      'financial_type_id' => '',
    ]);
    $form->postProcess();

    // Check if Membership is set to New.
    $membership = $this->callAPISuccessGetSingle('Membership', ['contact_id' => $this->ids['Contact']['individual_0']]);
    $this->assertEquals(CRM_Core_PseudoConstant::getKey('CRM_Member_BAO_Membership', 'status_id', 'New'), $membership['status_id']);
  }

  /**
   * Test the submit function of the membership form on membership type change.
   *  Check if the related contribution is also updated if the minimum_fee didn't match
   *
   * @throws \CRM_Core_Exception
   */
  public function testContributionUpdateOnMembershipTypeChange(): void {
    // @todo figure out why financial validation fails with this test.
    $this->isValidateFinancialsOnPostAssert = FALSE;
    // Step 1: Create a Membership via backoffice whose with 50.00 payment
    $form = $this->getForm([
      'cid' => $this->ids['Contact']['individual_0'],
      'join_date' => date('Y-m-d'),
      'start_date' => '',
      'end_date' => '',
      // This format reflects the first being the organisation & the $this->ids['MembershipType']['AnnualFixed'] being the type.
      'membership_type_id' => [$this->ids['Contact']['organization'], $this->ids['MembershipType']['AnnualFixed']],
      'record_contribution' => 1,
      'total_amount' => 50,
      'receive_date' => date('Y-m-d', time()) . ' 20:36:00',
      'payment_instrument_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'payment_instrument_id', 'Check'),
      'contribution_status_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed'),
      'financial_type_id' => '2',
      'payment_processor_id' => $this->ids['PaymentProcessor']['dummy'],
    ]);
    $mailUtil = new CiviMailUtils($this, TRUE);
    $this->createLoggedInUser();
    $form->postProcess();
    $membership = $this->callAPISuccessGetSingle('Membership', ['contact_id' => $this->ids['Contact']['individual_0']]);
    // check the membership status after partial payment, if its Pending
    $this->assertEquals('New', CRM_Core_PseudoConstant::getName('CRM_Member_BAO_Membership', 'status_id', $membership['status_id']));
    $contribution = $this->callAPISuccessGetSingle('Contribution', [
      'contact_id' => $this->ids['Contact']['individual_0'],
    ]);
    $this->assertEquals('Completed', $contribution['contribution_status']);
    $this->assertEquals(50.00, $contribution['total_amount']);
    $this->assertEquals(50.00, $contribution['net_amount']);

    // Step 2: Change the membership type whose minimum free is less than earlier membership
    $secondMembershipType = $this->callAPISuccess('membership_type', 'create', [
      'domain_id' => 1,
      'name' => 'Second Test Membership',
      'member_of_contact_id' => $this->ids['Contact']['organization'],
      'duration_unit' => 'month',
      'minimum_fee' => 25,
      'duration_interval' => 1,
      'period_type' => 'fixed',
      'fixed_period_start_day' => '101',
      'fixed_period_rollover_day' => '1231',
      'relationship_type_id' => 20,
      'financial_type_id' => 2,
    ]);
    Civi::settings()->set('update_contribution_on_membership_type_change', TRUE);
    $_REQUEST['id'] = $membership['id'];
    $params = [
      'cid' => $this->ids['Contact']['individual_0'],
      'join_date' => date('Y-m-d'),
      'start_date' => '',
      'end_date' => '',
      // This format reflects the first number being the organisation & the 25 being the type.
      'membership_type_id' => [$this->ids['Contact']['organization'], $secondMembershipType['id']],
      'status_id' => 1,
      'receive_date' => date('Y-m-d', time()) . ' 20:36:00',
      'payment_instrument_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'payment_instrument_id', 'Check'),
      //Member dues, see data.xml
      'financial_type_id' => '2',
      'payment_processor_id' => $this->ids['PaymentProcessor']['dummy'],
    ];
    $form = $this->getForm($params);
    $form->preProcess();
    $form->buildQuickForm();
    $form->_action = CRM_Core_Action::UPDATE;
    $form->_contactID = $this->ids['Contact']['individual_0'];
    $form->postProcess();
    $membership = $this->callAPISuccessGetSingle('Membership', ['contact_id' => $this->ids['Contact']['individual_0']]);
    // check the membership status after partial payment, if its Pending
    $contribution = $this->callAPISuccessGetSingle('Contribution', [
      'contact_id' => $this->ids['Contact']['individual_0'],
    ]);
    $payment = CRM_Contribute_BAO_Contribution::getPaymentInfo($membership['id'], 'membership', FALSE);
    // Check the contribution status on membership type change whose minimum fee was less than earlier membership
    $this->assertEquals('Pending refund', $contribution['contribution_status']);
    // Earlier paid amount
    $this->assertEquals(50, $payment['paid']);
    // balance remaining
    $this->assertEquals(-25, $payment['balance']);

    //Update to lifetime membership.
    $params['membership_type_id'] = [$this->ids['Contact']['organization'], $this->ids['MembershipType']['lifetime']];
    $form = $this->getForm($params);
    $form->preProcess();
    $form->buildQuickForm();
    $form->postProcess();
    $membership = $this->callAPISuccessGetSingle('Membership', ['contact_id' => $this->ids['Contact']['individual_0']]);
    $this->assertEquals($this->ids['MembershipType']['lifetime'], $membership['membership_type_id']);
    $this->assertTrue(empty($membership['end_date']), 'Lifetime Membership on the individual has an End date.');
  }

  /**
   * Test the submit function of the membership form for partial payment.
   *
   * @param string $thousandSeparator
   *   punctuation used to refer to thousands.
   *
   * @throws \CRM_Core_Exception
   * @dataProvider getThousandSeparators
   */
  public function testSubmitPartialPayment(string $thousandSeparator): void {
    $this->setCurrencySeparators($thousandSeparator);
    // Step 1: submit a partial payment for a membership via backoffice
    $form = $this->getForm();
    $form->preProcess();
    $mailUtil = new CiviMailUtils($this, TRUE);
    $this->createLoggedInUser();
    $priceSet = $this->callAPISuccess('PriceSet', 'Get', ["extends" => "CiviMember"]);
    $form->set('priceSetId', $priceSet['id']);

    CRM_Price_BAO_PriceSet::buildPriceSet($form);
    $params = [
      'contact_id' => $this->ids['Contact']['individual_0'],
      'join_date' => date('Y-m-d'),
      'start_date' => '',
      'end_date' => '',
      // This format reflects the first number being the organisation & the second being the type.
      'membership_type_id' => [$this->ids['Contact']['organization'], $this->ids['MembershipType']['AnnualFixed']],
      'receive_date' => date('Y-m-d', time()) . ' 20:36:00',
      'record_contribution' => 1,
      'total_amount' => $this->formatMoneyInput(50),
      'payment_instrument_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'payment_instrument_id', 'Check'),
      'contribution_status_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Pending'),
      //Member dues, see data.xml
      'financial_type_id' => '2',
      'payment_processor_id' => $this->ids['PaymentProcessor']['dummy'],
    ];
    $form->_contactID = $this->ids['Contact']['individual_0'];
    $form->testSubmit($params);
    $membership = $this->callAPISuccessGetSingle('Membership', ['contact_id' => $this->ids['Contact']['individual_0']]);
    // check the membership status after partial payment, if its Pending
    $this->assertEquals(array_search('Pending', CRM_Member_PseudoConstant::membershipStatus(), TRUE), $membership['status_id']);
    $contribution = $this->callAPISuccessGetSingle('Contribution', ['contact_id' => $this->ids['Contact']['individual_0']]);
    $this->callAPISuccess('Payment', 'create', ['contribution_id' => $contribution['id'], 'total_amount' => 25, 'payment_instrument_id' => 'Cash']);
    $contribution = $this->callAPISuccessGetSingle('Contribution', ['id' => $contribution['id']]);
    $this->assertEquals('Partially paid', $contribution['contribution_status']);

    // Step 2: submit the other half of the partial payment
    //  via AdditionalPayment form to complete the related contribution
    $this->getTestForm('CRM_Contribute_Form_AdditionalPayment', [
      'total_amount' => 150.00,
      'trxn_date' => '2017-04-11 13:05:11',
      'payment_instrument_id' => CRM_Core_PseudoConstant::getKey('CRM_Financial_BAO_FinancialTrxn', 'payment_instrument_id', 'Check'),
      'check_number' => 'check-12345',
      'trxn_id' => '',
      'currency' => 'USD',
      'fee_amount' => '',
      'net_amount' => '',
      'payment_processor_id' => 0,
      'contact_id' => $this->ids['Contact']['individual_0'],
    ], ['id' => $contribution['id']])->processForm();
    $membership = $this->callAPISuccessGetSingle('Membership', ['contact_id' => $this->ids['Contact']['individual_0']]);
    // check the membership status after additional payment, if its changed to 'New'
    $this->assertEquals(array_search('New', CRM_Member_PseudoConstant::membershipStatus(), TRUE), $membership['status_id']);

    // check the contribution status and net amount after additional payment
    $contribution = $this->callAPISuccessGetSingle('Contribution', [
      'contact_id' => $this->ids['Contact']['individual_0'],
    ]);
    $this->assertEquals('Completed', $contribution['contribution_status']);
    $this->validateAllPayments();
  }

  /**
   * Test the submit function of the membership form.
   *
   * @throws \CRM_Core_Exception
   */
  public function testSubmitRecur(): void {
    CRM_Core_Session::singleton()->getStatus(TRUE);
    $pendingVal = $this->callAPISuccessGetValue('OptionValue', [
      'return' => 'id',
      'option_group_id' => 'contribution_status',
      'label' => 'Pending Label**',
    ]);
    //Update label for Pending contribution status.
    $this->callAPISuccess('OptionValue', 'create', [
      'id' => $pendingVal,
      'label' => 'PendingEdited',
    ]);

    $this->callAPISuccess('MembershipType', 'create', [
      'id' => $this->ids['MembershipType']['AnnualFixed'],
      'duration_unit' => 'month',
      'duration_interval' => 1,
      'auto_renew' => 1,
    ]);
    $params = $this->getBaseSubmitParams();
    // Change financial_type_id to test our override flows through to the line item.
    $params['financial_type_id'] = FinancialType::get(FALSE)->addWhere('id', '!=', $params['financial_type_id'])->addSelect('id')->execute()->first()['id'];
    $form = $this->getForm($params);
    $this->createLoggedInUser();
    $form->_mode = 'test';
    $form->_contactID = $this->ids['Contact']['individual_0'];
    $form->testSubmit($params);
    $membership = $this->callAPISuccessGetSingle('Membership', ['contact_id' => $this->ids['Contact']['individual_0']]);
    $this->callAPISuccessGetCount('ContributionRecur', ['contact_id' => $this->ids['Contact']['individual_0']], 1);

    $contribution = $this->callAPISuccess('Contribution', 'get', [
      'contact_id' => $this->ids['Contact']['individual_0'],
      'is_test' => TRUE,
    ]);

    //Check if Membership Payment is recorded.
    $this->callAPISuccessGetCount('MembershipPayment', [
      'membership_id' => $membership['id'],
      'contribution_id' => $contribution['id'],
    ], 1);

    // CRM-16992.
    $this->callAPISuccessGetCount('LineItem', [
      'entity_id' => $membership['id'],
      'entity_table' => 'civicrm_membership',
      'contribution_id' => $contribution['id'],
      'financial_type_id' => $params['financial_type_id'],
    ], 1);
    $this->assertEquals([
      [
        'text' => 'AnnualFixed membership for Mr. Anthony Anderson II has been added. The new Membership Expiration Date is ' . date('F jS, Y', strtotime('last day of this month')) . '.',
        'title' => 'Complete',
        'type' => 'success',
        'options' => NULL,
      ],
    ], CRM_Core_Session::singleton()->getStatus());
  }

  /**
   * Test submit recurring with two line items.
   *
   * @throws \CRM_Core_Exception
   */
  public function testSubmitRecurTwoRows(): void {
    $this->createMembershipPriceSet();
    MembershipType::update()
      ->addWhere('id', '=', $this->ids['MembershipType']['AnnualRollingOrg2'])
      ->setValues(['frequency_interval' => 1, 'frequency_unit' => 'month', 'auto_renew' => 1])->execute();
    $form = $this->getForm();
    $form->_mode = 'live';
    $priceParams = [
      'price_' . $this->getPriceFieldID() => [
        $this->ids['PriceFieldValue']['AnnualRollingOrg2'] => 1,
        $this->ids['PriceFieldValue']['AnnualRolling'] => 1,
      ],
      'price_set_id' => $this->getPriceSetID(),
      'membership_type_id' => NULL,
      // Set financial type id to null to check it is retrieved from the price set.
      'financial_type_id' => NULL,
    ];
    $form->testSubmit(array_merge($this->getBaseSubmitParams(), $priceParams));
    $memberships = $this->callAPISuccess('Membership', 'get')['values'];
    $this->assertCount(2, $memberships);
    $this->callAPISuccessGetSingle('Contribution', ['financial_type_id' => 1]);
    $this->callAPISuccessGetCount('MembershipPayment', [], 2);
    $lines = $this->callAPISuccess('LineItem', 'get', ['sequential' => 1])['values'];
    $this->assertCount(2, $lines);
    $this->assertEquals('civicrm_membership', $lines[0]['entity_table']);
    $this->assertEquals('civicrm_membership', $lines[1]['entity_table']);

  }

  /**
   * CRM-20946: Test the financial entries especially the reversed amount,
   *  after related Contribution is cancelled
   *
   * @throws \CRM_Core_Exception
   */
  public function testFinancialEntriesOnCancelledContribution(): void {
    // @todo figure out why financial validation fails with this test.
    $this->isValidateFinancialsOnPostAssert = FALSE;
    // Create two memberships for individual $this->ids['Contact']['individual_0'], via a price set in the back end.
    $this->createTwoMembershipsViaPriceSetInBackEnd($this->ids['Contact']['individual_0']);

    // cancel the related contribution via API
    $contribution = $this->callAPISuccessGetSingle('Contribution', [
      'contact_id' => $this->ids['Contact']['individual_0'],
      'contribution_status_id' => 2,
    ]);
    $this->callAPISuccess('Contribution', 'create', [
      'id' => $contribution['id'],
      'contribution_status_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_DAO_Contribution', 'contribution_status_id', 'Cancelled'),
    ]);

    // fetch financial_trxn ID of the related contribution
    $sql = "SELECT financial_trxn_id
     FROM civicrm_entity_financial_trxn
     WHERE entity_id = %1 AND entity_table = 'civicrm_contribution'
     ORDER BY id DESC
     LIMIT 1
    ";
    $financialTrxnID = CRM_Core_DAO::singleValueQuery($sql, [1 => [$contribution['id'], 'Int']]);

    // fetch entity_financial_trxn records and compare their cancelled records
    $result = $this->callAPISuccess('EntityFinancialTrxn', 'Get', [
      'financial_trxn_id' => $financialTrxnID,
      'entity_table' => 'civicrm_financial_item',
    ]);
    // compare the reversed amounts of respective memberships after cancelling contribution
    $cancelledMembershipAmounts = [
      -259.00,
      -20.00,
    ];
    $count = 0;
    foreach ($result['values'] as $record) {
      $this->assertEquals($cancelledMembershipAmounts[$count], $record['amount']);
      $count++;
    }
  }

  /**
   * Test membership with soft credits.
   */
  public function testMembershipSoftCredit(): void {
    $this->createLoggedInUser();
    $softIndividualID = $this->individualCreate([], 'soft');
    $params = $this->getBaseSubmitParams();
    unset($params['auto_renew'], $params['is_recur']);
    $params['record_contribution'] = TRUE;
    $params['soft_credit_type_id'] = $this->callAPISuccessGetValue('OptionValue', [
      'return' => 'value',
      'name' => 'Gift',
      'option_group_id' => "soft_credit_type",
    ]);
    $params['soft_credit_contact_id'] = $softIndividualID;
    $form = $this->getTestForm('CRM_Member_Form_Membership', $params);
    $form->processForm();
    // Membership is created on main contact.
    $this->callAPISuccessGetSingle('Membership', ['contact_id' => $this->ids['Contact']['individual_0']]);

    // Verify is main contribution is created on soft contact.
    $contribution = $this->callAPISuccessGetSingle('Contribution', [
      'contact_id' => $softIndividualID,
    ]);
    $this->assertEquals($contribution['soft_credit'][1]['contact_id'], $this->ids['Contact']['individual_0']);

    // Verify if soft credit is created.
    $this->callAPISuccessGetSingle('ContributionSoft', [
      'contact_id' => $this->ids['Contact']['individual_0'],
      'contribution_id' => $contribution['id'],
    ]);
  }

  /**
   * Test the submit function of the membership form.
   *
   * @throws \CRM_Core_Exception
   */
  public function testSubmitPayLaterWithBilling(): void {
    $params = [
      'contact_id' => $this->ids['Contact']['individual_0'],
      'join_date' => date('Y-m-d'),
      'start_date' => '',
      'end_date' => '',
      // This format reflects the first number being the organisation & the second being the type.
      'membership_type_id' => [$this->ids['Contact']['organization'], $this->ids['MembershipType']['AnnualFixed']],
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
      'payment_processor_id' => $this->ids['PaymentProcessor']['dummy'],
      'record_contribution' => TRUE,
      'trxn_id' => 777,
      'contribution_status_id' => 2,
      'billing_first_name' => 'Test',
      'billing_middle_name' => 'Last',
      'billing_street_address-5' => '10 Test St',
      'billing_city-5' => 'Test',
      'billing_state_province_id-5' => '1003',
      'billing_postal_code-5' => '90210',
      'billing_country_id-5' => '1228',
    ];
    $form = $this->getForm($params);
    $this->createLoggedInUser();

    $form->_contactID = $this->ids['Contact']['individual_0'];

    $form->testSubmit($params);
    $membership = $this->callAPISuccessGetSingle('Membership', ['contact_id' => $this->ids['Contact']['individual_0']]);
    $contribution = $this->callAPISuccessGetSingle('Contribution', [
      'contact_id' => $this->ids['Contact']['individual_0'],
      'contribution_status_id' => 2,
    ]);
    $this->assertEquals($contribution['trxn_id'], 777);

    $this->callAPISuccessGetCount('LineItem', [
      'entity_id' => $membership['id'],
      'entity_table' => 'civicrm_membership',
      'contribution_id' => $contribution['id'],
    ], 1);
    $this->callAPISuccessGetSingle('address', [
      'contact_id' => $this->ids['Contact']['individual_0'],
      'street_address' => '10 Test St',
      'postal_code' => 90210,
    ]);

    // Check if Membership is set to Pending.
    $this->assertEquals(CRM_Core_PseudoConstant::getKey('CRM_Member_BAO_Membership', 'status_id', 'Pending'), $membership['status_id']);
  }

  /**
   * Test if membership is updated to New after contribution
   * is updated from Partially paid to Completed.
   *
   * @throws \CRM_Core_Exception
   */
  public function testSubmitUpdateMembershipFromPartiallyPaid(): void {
    $memStatus = CRM_Member_BAO_Membership::buildOptions('status_id', 'validate');

    // Perform a pay later membership contribution.
    $this->testSubmitPayLaterWithBilling();
    $membership = $this->callAPISuccessGetSingle('Membership', ['contact_id' => $this->ids['Contact']['individual_0']]);
    $this->assertEquals($membership['status_id'], array_search('Pending', $memStatus));
    $contribution = $this->callAPISuccessGetSingle('MembershipPayment', [
      'membership_id' => $membership['id'],
    ]);
    $prevContribution = $this->callAPISuccessGetSingle('Contribution', ['id' => $contribution['id']]);
    $this->callAPISuccess('Payment', 'create', [
      'contribution_id' => $contribution['contribution_id'],
      'payment_instrument_id' => 'Cash',
      'total_amount' => 5,
    ]);

    $submitParams = [
      'id' => $contribution['contribution_id'],
      'contribution_status_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed'),
    ];
    $fields = ['total_amount', 'net_amount', 'financial_type_id', 'receive_date', 'contact_id', 'payment_instrument_id'];
    foreach ($fields as $val) {
      $submitParams[$val] = $prevContribution[$val];
    }
    $_REQUEST['action'] = 'update';
    $_REQUEST['id'] = $contribution['contribution_id'];
    // Complete the contribution from offline form.
    $form = $this->getContributionForm($submitParams);
    $form->postProcess();

    // Check if Membership is updated to New.
    $membership = $this->callAPISuccessGetSingle('Membership', ['contact_id' => $this->ids['Contact']['individual_0']]);
    $this->assertEquals('New', CRM_Core_PseudoConstant::getName('CRM_Member_BAO_Membership', 'status_id', $membership['status_id']));
  }

  /**
   * Test if membership is updated to New after contribution
   * is updated from Partially paid to Completed.
   *
   * @throws \CRM_Core_Exception
   */
  public function testSubmitSaveMembershipNoChangesUnpaid(): void {

    // Perform a pay later membership contribution.
    $this->testSubmitPayLaterWithBilling();
    $membership = $this->callAPISuccessGetSingle('Membership', ['contact_id' => $this->ids['Contact']['individual_0']]);
    $this->assertEquals(CRM_Core_PseudoConstant::getKey('CRM_Member_BAO_Membership', 'status_id', 'Pending'), $membership['status_id']);

    $_REQUEST['id'] = $membership['id'];
    $form = $this->getForm([
      'contact_id' => $this->ids['Contact']['individual_0'],
      'id' => $membership['id'],
      'join_date' => date('Y-m-d'),
      'membership_type_id' => [$this->ids['Contact']['organization'], $this->ids['MembershipType']['AnnualFixed']],
      'financial_type_id' => '',
    ]);
    $form->postProcess();

    // Check if Membership stays as Pending.
    $membership = $this->callAPISuccessGetSingle('Membership', ['contact_id' => $this->ids['Contact']['individual_0']]);
    $this->assertEquals(CRM_Core_PseudoConstant::getKey('CRM_Member_BAO_Membership', 'status_id', 'Pending'), $membership['status_id']);
  }

  /**
   * Test the submit function of the membership form.
   *
   * @throws \CRM_Core_Exception
   */
  public function testSubmitRecurCompleteInstant(): void {
    $mut = new CiviMailUtils($this, TRUE);
    /** @var \CRM_Core_Payment_Dummy $processor */
    $processor = Civi\Payment\System::singleton()->getById($this->ids['PaymentProcessor']['dummy']);
    $processor->setDoDirectPaymentResult([
      'payment_status_id' => 1,
      'trxn_id' => 'kettles boil water',
      'fee_amount' => .14,
    ]);
    $processorDetail = $processor->getPaymentProcessor();
    $this->callAPISuccess('MembershipType', 'create', [
      'id' => $this->ids['MembershipType']['AnnualFixed'],
      'duration_unit' => 'month',
      'duration_interval' => 1,
      'auto_renew' => 1,
    ]);
    $form = $this->getForm($this->getBaseSubmitParams());
    $this->createLoggedInUser();
    $form->_mode = 'test';
    $form->_contactID = $this->ids['Contact']['individual_0'];
    $form->testSubmit();
    $membership = $this->callAPISuccessGetSingle('Membership', ['contact_id' => $this->ids['Contact']['individual_0']]);
    $this->callAPISuccessGetCount('ContributionRecur', ['contact_id' => $this->ids['Contact']['individual_0']], 1);

    $contribution = $this->callAPISuccess('Contribution', 'getsingle', [
      'contact_id' => $this->ids['Contact']['individual_0'],
      'is_test' => TRUE,
    ]);

    $this->assertEquals(.14, $contribution['fee_amount']);
    $this->assertEquals('kettles boil water', $contribution['trxn_id']);
    $this->assertEquals($processorDetail['payment_instrument_id'], $contribution['payment_instrument_id']);

    $this->callAPISuccessGetCount('LineItem', [
      'entity_id' => $membership['id'],
      'entity_table' => 'civicrm_membership',
      'contribution_id' => $contribution['id'],
    ], 1);
    $mut->checkMailLog([
      '===========================================================
Billing Name and Address
===========================================================
Test Last
10 Test St
Test, AR 90210
US',
      '===========================================================
Membership Information
===========================================================
Membership Type: AnnualFixed
Membership Start Date: ',
      '===========================================================
Credit Card Information
===========================================================
Visa
************1111
Expires: ',
    ]);
    $mut->stop();

  }

  /**
   * CRM-20955, CRM-20966:
   * Test creating two memberships with inheritance via price set in the back end,
   * checking that the correct primary & secondary memberships, contributions, line items
   * & membership_payment records are created.
   * Uses some data from tests/phpunit/CRM/Member/Form/dataset/data.xml .
   *
   * @throws \CRM_Core_Exception
   */
  public function testTwoInheritedMembershipsViaPriceSetInBackend(): void {
    // Create an organization and give it a "Member of" relationship to $this->ids['Contact']['individual_0'].
    $orgID = $this->organizationCreate();
    $relationship = $this->callAPISuccess('Relationship', 'create', [
      'contact_id_a' => $this->ids['Contact']['individual_0'],
      'contact_id_b' => $orgID,
      'relationship_type_id' => $this->ids['RelationshipType']['member'],
      'is_active' => 1,
    ]);

    // Create two memberships for the organization, via a price set in the back end.
    $this->createTwoMembershipsViaPriceSetInBackEnd($orgID, FALSE);

    // Check the primary memberships on the organization.
    $orgMembershipResult = $this->callAPISuccess('membership', 'get', [
      'contact_id' => $orgID,
    ]);
    $this->assertEquals(2, $orgMembershipResult['count'], '2 primary memberships should have been created on the organization.');
    $primaryMembershipIds = [];
    foreach ($orgMembershipResult['values'] as $membership) {
      $primaryMembershipIds[] = $membership['id'];
      $this->assertTrue(empty($membership['owner_membership_id']), 'Membership on the organization has owner_membership_id so is inherited.');
    }

    // CRM-20955: check that correct inherited memberships were created for the individual,
    // for both of the primary memberships.
    $individualMembershipResult = $this->callAPISuccess('membership', 'get', [
      'contact_id' => $this->ids['Contact']['individual_0'],
    ]);
    $this->assertEquals(2, $individualMembershipResult['count'], "2 inherited memberships should have been created on the individual.");
    foreach ($individualMembershipResult['values'] as $membership) {
      $this->assertNotEmpty($membership['owner_membership_id'], "Membership on the individual lacks owner_membership_id so is not inherited.");
      $this->assertNotContains($membership['id'], $primaryMembershipIds, "Inherited membership id should not be the id of a primary membership.");
      $this->assertContains($membership['owner_membership_id'], $primaryMembershipIds, "Inherited membership owner_membership_id should be the id of a primary membership.");
    }

    // CRM-20966: check that the correct membership contribution, line items
    // & membership_payment records were created for the organization.
    $contributionResult = $this->callAPISuccess('contribution', 'get', [
      'contact_id' => $orgID,
      'sequential' => 1,
      'api.line_item.get' => [],
      'api.membership_payment.get' => [],
    ]);
    $this->assertEquals(1, $contributionResult['count'], "One contribution should have been created for the organization's memberships.");

    $this->assertEquals(2, $contributionResult['values'][0]['api.line_item.get']['count'], "2 line items should have been created for the organization's memberships.");
    foreach ($contributionResult['values'][0]['api.line_item.get']['values'] as $lineItem) {
      $this->assertEquals('civicrm_membership', $lineItem['entity_table'], "Membership line item's entity_table should be 'civicrm_membership'.");
      $this->assertContains($lineItem['entity_id'], $primaryMembershipIds, "Membership line item's entity_id should be the id of a primary membership.");
    }

    $this->assertEquals(2, $contributionResult['values'][0]['api.membership_payment.get']['count'], "2 membership payment records should have been created for the organization's memberships.");
    foreach ($contributionResult['values'][0]['api.membership_payment.get']['values'] as $membershipPayment) {
      $this->assertEquals($contributionResult['values'][0]['id'], $membershipPayment['contribution_id'], "membership payment's contribution ID should be the ID of the organization's membership contribution.");
      $this->assertContains($membershipPayment['membership_id'], $primaryMembershipIds, "membership payment's membership ID should be the ID of a primary membership.");
    }
    // Check for orphan line items.
    $this->callAPISuccessGetCount('LineItem', [], 2);

    // CRM-20966: check that deleting relationship used for inheritance does not delete contribution.
    $this->callAPISuccess('relationship', 'delete', [
      'id' => $relationship['id'],
    ]);

    $contributionResultAfterRelationshipDelete = $this->callAPISuccess('contribution', 'get', [
      'id' => $contributionResult['values'][0]['id'],
      'contact_id' => $orgID,
    ]);
    $this->assertEquals(1, $contributionResultAfterRelationshipDelete['count'], "Contribution has been wrongly deleted.");
  }

  /**
   * dev/core/issues/860:
   * Test creating two memberships via price set in the back end with a discount,
   * checking that the line items have correct amounts.
   *
   * @throws \CRM_Core_Exception
   */
  public function testTwoMembershipsViaPriceSetInBackendWithDiscount(): void {
    // Register buildAmount hook to apply discount.
    $this->hookClass->setHook('civicrm_buildAmount', [$this, 'buildAmountMembershipDiscount']);
    $this->enableTaxAndInvoicing();
    $this->addTaxAccountToFinancialType(2);
    // Create two memberships for individual $this->ids['Contact']['individual_0'], via a price set in the back end.
    $this->createTwoMembershipsViaPriceSetInBackEnd($this->ids['Contact']['individual_0']);
    $contribution = $this->callAPISuccessGetSingle('Contribution', [
      'contact_id' => $this->ids['Contact']['individual_0'],
    ]);

    // Note: we can't check for the contribution total being discounted, because the total is set
    // when the contribution is created via $form->testSubmit(), but buildAmount isn't called
    // until testSubmit() runs. Fixing that might involve making testSubmit() more sophisticated,
    // or just hacking total_amount for this case.

    $lineItemResult = $this->callAPISuccess('LineItem', 'get', [
      'contribution_id' => $contribution['id'],
    ]);
    $this->assertEquals(2, $lineItemResult['count']);
    $discountedItems = 0;
    foreach ($lineItemResult['values'] as $lineItem) {
      $this->assertEquals(round($lineItem['line_total'] * .1, 2), $lineItem['tax_amount']);
      if (str_starts_with($lineItem['label'], 'Long Haired Goat')) {
        $this->assertEquals(15.0, $lineItem['line_total']);
        $this->assertEquals('Long Haired Goat - one leg free!', $lineItem['label']);
        $discountedItems++;
      }
    }
    $this->assertEquals(1, $discountedItems);
  }

  /**
   * Implements hook_civicrm_buildAmount() for testTwoMembershipsViaPriceSetInBackendWithDiscount().
   */
  public function buildAmountMembershipDiscount($pageType, &$form, &$amount) {
    foreach ($amount as $id => $priceField) {
      if (is_array($priceField['options'])) {
        foreach ($priceField['options'] as $optionId => $option) {
          if ($option['membership_type_id'] == $this->ids['MembershipType']['AnnualRolling']) {
            // Long Haired Goat membership discount.
            $amount[$id]['options'][$optionId]['amount'] = $option['amount'] * 0.75;
            $amount[$id]['options'][$optionId]['label'] = $option['label'] . ' - one leg free!';
          }
        }
      }
    }
  }

  /**
   * Get a membership form object.
   *
   * We need to instantiate the form to run preprocess, which means we have to
   * trick it about the request method.
   *
   * @param array $formValues
   *
   * @return \CRM_Member_Form_Membership
   */
  protected function getForm(array $formValues = []): CRM_Member_Form_Membership {
    if (isset($_REQUEST['cid'])) {
      unset($_REQUEST['cid']);
    }
    /** @var CRM_Member_Form_Membership $form*/
    $form = $this->getFormObject('CRM_Member_Form_Membership', $formValues);
    $form->preProcess();
    $form->buildForm();
    return $form;
  }

  /**
   * @return array
   */
  protected function getBaseSubmitParams(): array {
    return [
      'contact_id' => $this->ids['Contact']['individual_0'],
      'price_set_id' => 0,
      'join_date' => date('Y-m-d'),
      'start_date' => '',
      'end_date' => '',
      'campaign_id' => '',
      // This format reflects the first number being the organisation & the second being the type.
      'membership_type_id' => [$this->ids['Contact']['organization'], $this->ids['MembershipType']['AnnualFixed']],
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
      'payment_processor_id' => $this->ids['PaymentProcessor']['dummy'],
      'credit_card_number' => '4111111111111111',
      'cvv2' => '123',
      'credit_card_exp_date' => [
        'M' => '9',
        'Y' => date('Y') + 1,
      ],
      'credit_card_type' => 'Visa',
      'billing_first_name' => 'Test',
      'billing_middle_name' => 'Last',
      'billing_street_address-5' => '10 Test St',
      'billing_city-5' => 'Test',
      'billing_state_province_id-5' => '1003',
      'billing_postal_code-5' => '90210',
      'billing_country_id-5' => '1228',
      'send_receipt' => 1,
    ];
  }

  /**
   * Scenario builder:
   * create two memberships for the same individual, via a price set in the back end.
   *
   * @param int $contactId Id of contact on which the memberships will be created.
   * @param bool $isTaxEnabled
   *   Is tax enabled for the Member Dues financial type.
   *   Note that currently this ALSO assumes a discount has been
   *   applied - this overloading would ideally be cleaned up.
   *
   * @throws \CRM_Core_Exception
   */
  protected function createTwoMembershipsViaPriceSetInBackEnd(int $contactId, bool $isTaxEnabled = TRUE): void {
    // register for both of these memberships via backoffice membership form submission
    $this->createLoggedInUser();
    $this->createMembershipPriceSet();
    $params = [
      'cid' => $contactId,
      'contact_id' => $contactId,
      'join_date' => date('Y-m-d'),
      'start_date' => '',
      'end_date' => '',
      'price_' . $this->getPriceFieldID() => [
        $this->ids['PriceFieldValue']['AnnualRollingOrg2'] => 1,
        $this->ids['PriceFieldValue']['AnnualRolling'] => 1,
      ],
      // $15 for discounted goat + $259 = $274 + $27.4 in tax.
      // or without tax (and without discount) $279.
      'total_amount' => $isTaxEnabled ? 301.4 : 279,
      'price_set_id' => $this->getPriceSetID(),
      'membership_type_id' => [1 => 0],
      'auto_renew' => '0',
      'max_related' => '',
      'num_terms' => '2',
      'source' => '',
      //Member dues, see data.xml
      'financial_type_id' => '2',
      'soft_credit_type_id' => '',
      'soft_credit_contact_id' => '',
      'payment_instrument_id' => 4,
      'from_email_address' => '"Demonstrators Anonymous" <info@example.org>',
      'receipt_text_signup' => 'Thank you text',
      'payment_processor_id' => $this->ids['PaymentProcessor']['dummy'],
      'record_contribution' => TRUE,
      'trxn_id' => 777,
      'contribution_status_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_DAO_Contribution', 'contribution_status_id', 'Pending'),
      'billing_first_name' => 'Test',
      'billing_middle_name' => 'Last',
      'billing_street_address-5' => '10 Test St',
      'billing_city-5' => 'Test',
      'billing_state_province_id-5' => '1003',
      'billing_postal_code-5' => '90210',
      'billing_country_id-5' => '1228',
    ];
    $form = $this->getForm($params);
    $form->postProcess();
  }

  /**
   * Get the contribution form object.
   *
   * @param array $formValues
   *
   * @return \CRM_Contribute_Form_Contribution
   */
  protected function getContributionForm(array $formValues): CRM_Contribute_Form_Contribution {
    /** @var CRM_Contribute_Form_Contribution $form */
    $form = $this->getFormObject('CRM_Contribute_Form_Contribution', $formValues);
    $form->buildForm();
    return $form;
  }

  /**
   * CRM-21656: Test the submit function of the membership form if Sales Tax is enabled.
   * This test simulates what happens when one hits Edit on a Contribution that has both LineItems and Sales Tax components
   * Without making any Edits -> check that the LineItem data remain the same
   * In addition (a data-integrity check) -> check that the LineItem data add up to the data at the Contribution level
   *
   * @throws \CRM_Core_Exception
   */
  public function testLineItemAmountOnSalesTax(): void {
    $mailUtil = new CiviMailUtils($this, TRUE);
    $this->enableTaxAndInvoicing();
    $this->addTaxAccountToFinancialType(2);
    $priceSet = $this->callAPISuccess('PriceSet', 'Get', ['extends' => 'CiviMember']);
    // we are simulating the creation of a Price Set in Administer -> CiviContribute -> Manage Price Sets so set is_quick_config = 0
    $this->callAPISuccess('PriceSet', 'Create', ['id' => $priceSet['id'], 'is_quick_config' => 0]);
    $fieldOption = PriceFieldValue::get()->addWhere('amount', '=', 50)->addSelect('id', 'price_field_id')->execute()->indexBy('id')->first();
    // clean the price options static variable to repopulate the options, in order to fetch tax information
    \Civi::$statics['CRM_Price_BAO_PriceField']['priceOptions'] = NULL;
    $params = [
      'cid' => $this->ids['Contact']['individual_0'],
      'join_date' => date('Y-m-d'),
      'start_date' => '',
      'end_date' => '',
      // This format reflects the first number being the organisation & the second being the type.
      'membership_type_id' => [$this->ids['Contact']['organization'], $this->ids['MembershipType']['AnnualFixed']],
      'record_contribution' => 1,
      'price_' . $fieldOption['price_field_id'] => $fieldOption['id'],
      'total_amount' => 55,
      'receive_date' => date('Y-m-d') . ' 20:36:00',
      'payment_instrument_id' => CRM_Core_PseudoConstant::getKey('CRM_Financial_BAO_FinancialTrxn', 'payment_instrument_id', 'Check'),
      'contribution_status_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed'),
      //Member dues, see data.xml
      'financial_type_id' => 2,
      'payment_processor_id' => $this->ids['PaymentProcessor']['dummy'],
      'send_receipt' => 1,
      'from_email_address' => 'bob@example.com',
    ];

    $form = $this->getForm($params);
    $this->createLoggedInUser();
    $form->postProcess();
    $email = preg_replace('/\s+/', ' ', $mailUtil->getMostRecentEmail());
    foreach ($this->getExpectedEmailStrings() as $string) {
      $this->assertStringContainsString($string, $email);
    }

    $membership = $this->callAPISuccessGetSingle('Membership', ['contact_id' => $this->ids['Contact']['individual_0']]);
    $lineItem = $this->callAPISuccessGetSingle('LineItem', ['entity_id' => $membership['id'], 'entity_table' => 'civicrm_membership']);
    $this->assertEquals(1, $lineItem['qty']);
    $this->assertEquals(50.00, $lineItem['unit_price']);
    $this->assertEquals(50.00, $lineItem['line_total']);
    $this->assertEquals(5.00, $lineItem['tax_amount']);

    $_REQUEST['id'] = $lineItem['contribution_id'];
    $_REQUEST['context'] = 'membership';
    // Simply save the 'Edit Contribution' form
    $form = $this->getFormObject('CRM_Contribute_Form_Contribution', [
      'contact_id' => $this->ids['Contact']['individual_0'],
      'financial_type_id' => 2,
      'total_amount' => 55,
      'contribution_status_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed'),
    ]);
    $form->buildForm();
    $form->postProcess();

    // ensure that the LineItem data remain the same
    $lineItem = $this->callAPISuccessGetSingle('LineItem', ['entity_id' => $membership['id'], 'entity_table' => 'civicrm_membership']);
    $this->assertEquals(1, $lineItem['qty']);
    $this->assertEquals(50.00, $lineItem['unit_price']);
    $this->assertEquals(50.00, $lineItem['line_total']);
    $this->assertEquals(5.00, $lineItem['tax_amount']);

    // ensure that the LineItem data add up to the data at the Contribution level
    $contribution = $this->callAPISuccessGetSingle('Contribution',
      [
        'contribution_id' => 1,
        'return' => ['tax_amount', 'total_amount'],
      ]
    );
    $this->assertEquals($contribution['total_amount'], $lineItem['line_total'] + $lineItem['tax_amount']);
    $this->assertEquals($contribution['tax_amount'], $lineItem['tax_amount']);

    $financialItems = $this->callAPISuccess('FinancialItem', 'get', []);
    $financialItems_sum = 0;
    foreach ($financialItems['values'] as $financialItem) {
      $financialItems_sum += $financialItem['amount'];
    }
    $this->assertEquals($contribution['total_amount'], $financialItems_sum);
  }

  /**
   * Get the expected output from mail.
   *
   * @return string[]
   */
  private function getExpectedEmailStrings(): array {
    $membership = Membership::get()->execute()->first();
    return [
      '<td> ' . CRM_Utils_Date::formatDateOnlyLong($membership['start_date']) . ' </td> <td> ' . CRM_Utils_Date::formatDateOnlyLong($membership['end_date']) . ' </td>',
      '<table id="crm-membership_receipt"',
      'AnnualFixed',
      'Membership Fee',
      'Financial Type',
      'Member Dues </td>',
      '<tr> <td colspan="2" style="padding: 4px; border-bottom: 1px solid #999;"> <table> <tr> <th>Item</th> <th>Fee</th> <th>SubTotal</th> <th>Tax Rate</th> <th>Tax Amount</th> <th>Total</th> <th>Membership Start Date</th> <th>Membership Expiration Date</th> </tr> <tr> <td>Membership Amount - AnnualFixed</td>',
      '<td> $50.00 </td> <td> $50.00 </td> <td> 10.00% </td> <td> $5.00 </td> <td> $55.00 </td> <td>',
      'Amount Before Tax: </td>',
      '<td style="padding: 4px; border-bottom: 1px solid #999;"> $50.00 </td>',
      '<td style="padding: 4px; border-bottom: 1px solid #999; background-color: #f7f7f7;"> Sales Tax 10.00%</td> <td style="padding: 4px; border-bottom: 1px solid #999;">$5.00</td>',
      'Total Tax Amount </td> <td style="padding: 4px; border-bottom: 1px solid #999;"> $5.00 </td>',
      'Amount </td> <td style="padding: 4px; border-bottom: 1px solid #999;"> $55.00 </td>',
      'Paid By </td> <td style="padding: 4px; border-bottom: 1px solid #999;"> Check </td>',
    ];
  }

  /**
   * Test that membership end_date is correct for multiple terms for pending contribution
   *
   * @throws \CRM_Core_Exception
   * @throws \Exception
   */
  public function testCreatePendingWithMultipleTerms(): void {
    CRM_Core_Session::singleton()->getStatus(TRUE);
    $mailUtil = new CiviMailUtils($this, TRUE);
    $this->createLoggedInUser();
    $membershipTypeAnnualRolling = $this->callAPISuccess('membership_type', 'create', [
      'domain_id' => 1,
      'name' => 'AnnualRollingNew',
      'member_of_contact_id' => $this->ids['Contact']['organization'],
      'duration_unit' => 'year',
      'minimum_fee' => 50,
      'duration_interval' => 1,
      'period_type' => 'rolling',
      'relationship_type_id' => 20,
      'relationship_direction' => 'b_a',
      'financial_type_id' => 2,
    ]);
    $params = [
      'cid' => $this->ids['Contact']['individual_0'],
      'join_date' => date('Y-m-d'),
      'start_date' => '',
      'end_date' => '',
      'contribution_status_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Pending'),
      'membership_type_id' => [$this->ids['Contact']['organization'], $membershipTypeAnnualRolling['id']],
      'max_related' => '',
      'num_terms' => '3',
      'record_contribution' => 1,
      'source' => '',
      'total_amount' => $this->formatMoneyInput(150.00),
      'financial_type_id' => '2',
      'soft_credit_type_id' => '11',
      'soft_credit_contact_id' => '',
      'from_email_address' => '"Demonstrators Anonymous" <info@example.org>',
      'receipt_text' => '',
    ];
    $form = $this->getForm();
    $form->preProcess();
    $form->_contactID = $this->ids['Contact']['individual_0'];
    $form->testSubmit($params);
    $membership = $this->callAPISuccessGetSingle('Membership', ['contact_id' => $this->ids['Contact']['individual_0']]);
    // Check if Membership is set to Pending.
    $this->assertEquals(CRM_Core_PseudoConstant::getKey('CRM_Member_BAO_Membership', 'status_id', 'Pending'), $membership['status_id']);
    $contribution = $this->callAPISuccess('Contribution', 'get', [
      'contact_id' => $this->ids['Contact']['individual_0'],
    ]);
    $endDate = (new DateTime(date('Y-m-d')))->modify('+3 years')->modify('-1 day');
    $endDate = $endDate->format('Y-m-d');

    $this->assertEquals($endDate, $membership['end_date'], 'Membership Expiration Date should be ' . $endDate);
    $this->assertCount(1, $contribution['values'], 'Pending contribution should be created.');
    $contribution = $contribution['values'][$contribution['id']];
    $this->getTestForm('CRM_Contribute_Form_AdditionalPayment', [
      'total_amount' => 150.00,
      'trxn_date' => date('Y-m-d H:i:s'),
      'payment_instrument_id' => CRM_Core_PseudoConstant::getKey('CRM_Financial_BAO_FinancialTrxn', 'payment_instrument_id', 'Check'),
      'check_number' => 'check-12345',
      'trxn_id' => '',
      'currency' => 'USD',
      'fee_amount' => '',
      'financial_type_id' => 1,
      'net_amount' => '',
      'payment_processor_id' => 0,
      'contact_id' => $this->ids['Contact']['individual_0'],
    ], ['id' => $contribution['id']])->processForm();
    $membership = $this->callAPISuccessGetSingle('Membership', ['contact_id' => $this->ids['Contact']['individual_0']]);
    $contribution = $this->callAPISuccess('Contribution', 'get', [
      'contact_id' => $this->ids['Contact']['individual_0'],
      'contribution_status_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Completed'),
    ]);
    $this->assertEquals($endDate, $membership['end_date'], 'Membership Expiration Date should be same (' . $endDate . ') after payment');
    $this->assertCount(1, $contribution['values'], 'Completed contribution should be fetched.');
  }

  /**
   * Test Membership Payment owned by other contact, membership view should
   * show all contribution records in listing. is other contact.
   *
   * @throws \CRM_Core_Exception
   */
  public function testMembershipViewContributionOwnerDifferent(): void {
    // Membership Owner
    $contactId1 = $this->individualCreate();

    // Contribution Owner
    $contactId2 = $this->individualCreate();

    // create new membership type
    $membershipTypeAnnualFixed = $this->callAPISuccess('MembershipType', 'create', [
      'domain_id' => 1,
      'name' => 'AnnualFixed 2',
      'member_of_contact_id' => $this->organizationCreate(),
      'duration_unit' => 'year',
      'minimum_fee' => 50,
      'duration_interval' => 1,
      'period_type' => 'fixed',
      'fixed_period_start_day' => '101',
      'fixed_period_rollover_day' => '1231',
      'financial_type_id' => 2,
    ]);

    // Create Membership.
    $membershipId = $this->contactMembershipCreate([
      'contact_id' => $contactId1,
      'membership_type_id' => $membershipTypeAnnualFixed['id'],
      'status_id' => 'New',
    ]);

    // 1st Payment
    $contributionParams = [
      'membership_id' => $membershipId,
      'total_amount' => 25,
      'financial_type_id' => 2,
      'contact_id' => $contactId2,
      'receive_date' => '2020-08-08',
    ];
    $contribution1 = CRM_Member_BAO_Membership::recordMembershipContribution($contributionParams);

    // 2nd Payment
    $contributionParams = [
      'membership_id' => $membershipId,
      'total_amount' => 25,
      'financial_type_id' => 2,
      'contact_id' => $contactId2,
      'receive_date' => '2020-07-08',
    ];
    $contribution2 = CRM_Member_BAO_Membership::recordMembershipContribution($contributionParams);

    // View Membership record
    $membershipViewForm = new CRM_Member_Form_MembershipView();
    $membershipViewForm->controller = new CRM_Core_Controller_Simple('CRM_Member_Form_MembershipView', 'View Membership');
    $membershipViewForm->set('id', $membershipId);
    $membershipViewForm->set('context', 'membership');
    $membershipViewForm->controller->setEmbedded(TRUE);
    $membershipViewForm->preProcess();

    // Get contribution rows related to membership payments.
    $templateVar = $membershipViewForm::getTemplate()->getTemplateVars('rows');

    $this->assertEquals($templateVar[0]['contribution_id'], $contribution1->id);
    $this->assertEquals($templateVar[0]['contact_id'], $contactId2);

    $this->assertEquals($templateVar[1]['contribution_id'], $contribution2->id);
    $this->assertEquals($templateVar[1]['contact_id'], $contactId2);
    $this->assertCount(2, $templateVar);
  }

}
