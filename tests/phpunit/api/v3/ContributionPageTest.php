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

declare(strict_types = 1);

use Civi\Api4\Contribution;

use Civi\Api4\LineItem;
use Civi\Test\ContributionPageTestTrait;

/**
 *  Test APIv3 civicrm_contribution_page* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Contribution
 * @group headless
 */
class api_v3_ContributionPageTest extends CiviUnitTestCase {
  use CRMTraits_Financial_PriceSetTrait;
  use ContributionPageTestTrait;

  protected array $params;
  protected string $_entity = 'ContributionPage';

  /**
   * @var array
   *   - contribution_page
   *   - price_set
   *   - price_field
   *   - price_field_value
   */
  protected array $_ids = [];

  /**
   * Setup for test.
   */
  public function setUp(): void {
    parent::setUp();
    $this->individualCreate();
    $this->params = [
      'title' => 'Test Contribution Page',
      'financial_type_id' => 1,
      'currency' => 'NZD',
      'goal_amount' => 34567,
      'is_pay_later' => 1,
      'pay_later_text' => 'Send check',
      'is_monetary' => TRUE,
      'is_email_receipt' => TRUE,
      'receipt_from_email' => 'yourconscience@donate.com',
      'receipt_from_name' => 'Ego Freud',
    ];
  }

  /**
   * Tear down after test.
   */
  public function tearDown(): void {
    $this->quickCleanup(['civicrm_system_log']);
    $this->quickCleanUpFinancialEntities();
    parent::tearDown();
  }

  /**
   * Test creating a contribution page.
   *
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   * @throws \CRM_Core_Exception
   */
  public function testCreateContributionPage(int $version): void {
    $this->basicCreateTest($version);
  }

  /**
   * Test getting a contribution page.
   *
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   */
  public function testGetBasicContributionPage(int $version): void {
    $this->_apiversion = $version;
    $this->callAPISuccess('ContributionPage', 'create', $this->params);
    $getParams = [
      'currency' => 'NZD',
      'financial_type_id' => 1,
    ];
    $getResult = $this->callAPISuccess('ContributionPage', 'get', $getParams);
    $this->assertEquals(1, $getResult['count']);
  }

  /**
   * Test get with amount as a parameter.
   */
  public function testGetContributionPageByAmount(): void {
    $this->callAPISuccess('ContributionPage', 'create', $this->params);
    $getParams = [
      'amount' => '34567',
      'currency' => 'NZD',
      'financial_type_id' => 1,
    ];
    $getResult = $this->callAPISuccess('ContributionPage', 'get', $getParams);
    $this->assertEquals(1, $getResult['count']);
  }

  /**
   * Test page deletion.
   *
   * @param int $version
   *
   * @dataProvider versionThreeAndFour
   * @throws \CRM_Core_Exception
   */
  public function testDeleteContributionPage(int $version): void {
    $this->basicDeleteTest($version);
  }

  /**
   * Test getfields function.
   */
  public function testGetFieldsContributionPage(): void {
    $result = $this->callAPISuccess('ContributionPage', 'getfields', ['action' => 'create']);
    $this->assertEquals(12, $result['values']['start_date']['type']);
  }

  /**
   * Test submit with a pay later and check line item in mails.
   */
  public function testSubmitMembershipBlockIsSeparatePaymentPayLaterWithEmail(): void {
    $mut = new CiviMailUtils($this, TRUE);
    $this->setUpMembershipContributionPage(TRUE);
    $this->submitOnlineContributionForm([
      'price_' . $this->ids['PriceField']['other_amount'] => 1,
      $this->getPriceFieldLabel('membership_amount') => $this->getPriceFieldValue('general'),
      'billing_first_name' => 'Billy',
      'billing_middle_name' => 'Goat',
      'billing_last_name' => 'Gruff',
      'payment_processor_id' => 0,
      'email-Primary' => 'billy-goat@the-bridge.net',
    ], $this->getContributionPageID());
    $contributions = $this->callAPISuccess('Contribution', 'get', ['contribution_page_id' => $this->getContributionPageID()])['values'];
    $this->assertCount(2, $contributions);
    $this->callAPISuccess('membership_payment', 'getsingle', ['contribution_id' => ['IN' => array_keys($contributions)]]);
    $mut->checkMailLog([
      'Membership Fee',
      '$2.00',
    ]);
    $mut->stop();
    $mut->clearMessages();
  }

  /**
   * Test submit with a membership block in place.
   */
  public function testSubmitMembershipBlockIsSeparatePayment(): void {
    $this->setUpMembershipContributionPage(TRUE);
    $this->submitOnlineContributionForm([
      'price_' . $this->ids['PriceField']['membership_amount'] => $this->ids['PriceFieldValue']['membership_general'],
      'id' => $this->getContributionPageID(),
      'email-Primary' => 'billy-goat@the-bridge.net',
      'price_' . $this->ids['PriceField']['other_amount'] => 88,
    ], $this->getContributionPageID());
    $this->validateSeparateMembershipPaymentContributions($this->getContributionPageID());
  }

  /**
   * Test submit with a membership block in place.
   */
  public function testSubmitMembershipBlockIsSeparatePaymentWithPayLater(): void {
    $this->setUpMembershipContributionPage(TRUE);
    $this->ids['MembershipType'] = [$this->membershipTypeCreate(['minimum_fee' => 2])];
    //Pay later
    $this->submitOnlineContributionForm([
      $this->getPriceFieldLabel('other_amount') => 1,
      $this->getPriceFieldLabel('membership_amount') => $this->getPriceFieldValue('general'),
      'billing_first_name' => 'Billy',
      'billing_middle_name' => 'Goat',
      'billing_last_name' => 'Gruff',
      'is_pay_later' => 1,
    ], $this->getContributionPageID());

    $contributions = $this->callAPISuccess('Contribution', 'get', ['contribution_page_id' => $this->getContributionPageID(), 'return' => 'contribution_status_id']);
    $this->assertCount(2, $contributions['values']);
    foreach ($contributions['values'] as $val) {
      $this->assertEquals(CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Pending'), $val['contribution_status_id']);
    }

    //Membership should be in Pending state.
    $membershipPayment = $this->callAPISuccess('MembershipPayment', 'getsingle', ['return' => ['membership_id', 'contribution_id']]);
    $this->assertArrayHasKey($membershipPayment['contribution_id'], $contributions['values']);
    $membership = $this->callAPISuccessGetSingle('membership', ['id' => $membershipPayment['membership_id'], 'return' => ['status_id', 'contact_id']]);
    $pendingStatus = $this->callAPISuccessGetSingle('MembershipStatus', ['return' => ['id'], 'name' => 'Pending']);
    $this->assertEquals($membership['status_id'], $pendingStatus['id']);
    $this->assertEquals($membership['contact_id'], $contributions['values'][$membershipPayment['contribution_id']]['contact_id']);
  }

  /**
   * Test submit with a membership block in place.
   */
  public function testSubmitMembershipBlockIsSeparatePaymentWithEmail(): void {
    $mut = new CiviMailUtils($this, TRUE);
    $this->setUpMembershipContributionPage(TRUE);
    $this->addProfile('supporter_profile', $this->getContributionPageID());

    $this->submitOnlineContributionForm([
      'price_' . $this->ids['PriceField']['membership_amount'] => $this->ids['PriceFieldValue']['membership_general'],
      'id' => $this->getContributionPageID(),
      'email-Primary' => 'billy-goat@the-bridge.net',
      'price_' . $this->ids['PriceField']['other_amount'] => 88,
    ] + $this->getBillingSubmitValues(), $this->getContributionPageID());
    $this->validateSeparateMembershipPaymentContributions($this->getContributionPageID());
    // We should have two separate email messages, each with their own amount
    // line and no total line.
    $mut->checkAllMailLog(
      [
        'Amount',
        '$2.00',
        '$88.00',
        'Membership Fee',
      ],
      [
        'Total: $',
      ]
    );
    $mut->stop();
    $mut->clearMessages();
  }

  /**
   * Test submit with a membership block in place.
   */
  public function testSubmitMembershipBlockIsSeparatePaymentZeroDollarsPayLaterWithEmail(): void {
    $mut = new CiviMailUtils($this, TRUE);
    $this->membershipTypeCreateFree();
    $this->contributionPageQuickConfigCreate([], [], TRUE, TRUE, TRUE, TRUE);
    $this->addProfile('supporter_profile', $this->getContributionPageID());
    $this->submitOnlineContributionForm([
      'price_' . $this->ids['PriceField']['membership_amount'] => $this->ids['PriceFieldValue']['membership_free'],
      'email-Primary' => 'billy-goat@the-bridge.net',
      'price_' . $this->ids['PriceField']['other_amount'] => 88,
      'payment_processor_id' => 0,
    ] + $this->getBillingSubmitValues(), $this->getContributionPageID());
    $contributions = $this->callAPISuccess('Contribution', 'get', ['contribution_page_id' => $this->getContributionPageID(), 'return' => 'contact_id'])['values'];
    $this->assertCount(2, $contributions);
    $lineItem = LineItem::get(FALSE)
      ->addWhere('entity_table', '=', 'civicrm_membership')
      ->execute()->single();
    $this->assertArrayKeyExists($lineItem['contribution_id'], $contributions);
    $membership = $this->callAPISuccessGetSingle('Membership', ['id' => $lineItem['entity_id'], 'return' => 'contact_id']);
    $this->assertEquals($membership['contact_id'], $contributions[$lineItem['entity_id']]['contact_id']);
    $mut->checkMailLog([
      'Dave',
      'Wong',
      'Free',
      'Membership Information',
      'Membership Type',
    ]);
    $mut->stop();
    $mut->clearMessages();
  }

  /**
   * Transitional function to make it easier to move these tests to ConfirmTest class.
   *
   * @param array $submittedValues
   * @param int|null $contributionPageID
   * @param array $urlParameters
   *
   * @return void
   */
  protected function submitOnlineContributionForm(array $submittedValues, ?int $contributionPageID = NULL, array $urlParameters = []) {
    $this->callAPISuccess('ContributionPage', 'submit', $submittedValues + ['id' => $contributionPageID]);
  }

  /**
   * Test submit with a membership block in place.
   */
  public function testSubmitMembershipBlockTwoTypesIsSeparatePayment(): void {
    $this->ids['MembershipType'] = [$this->membershipTypeCreate(['minimum_fee' => 6])];
    $this->ids['MembershipType'][] = $this->membershipTypeCreate(['title' => 'Student', 'minimum_fee' => 50]);
    $this->setUpMembershipContributionPage(TRUE);

    $this->submitOnlineContributionForm([
      'price_' . $this->ids['PriceField']['membership_amount'] => $this->ids['PriceFieldValue']['membership_student'],
      'id' => $this->getContributionPageID(),
      'email-Primary' => 'billy-goat@the-bridge.net',
      'price_' . $this->ids['PriceField']['other_amount'] => 88,
    ], $this->getContributionPageID());
    $contributions = $this->callAPISuccess('Contribution', 'get', ['contribution_page_id' => $this->getContributionPageID(), 'sequential' => TRUE])['values'];
    $this->assertCount(2, $contributions);
    $this->assertEquals('88.00', $contributions[0]['total_amount']);
    $this->assertEquals('50.00', $contributions[1]['total_amount']);
    $membershipPayment = $this->callAPISuccessGetSingle('MembershipPayment', ['return' => ['membership_id', 'contribution_id']]);
    $this->assertEquals($contributions[1]['id'], $membershipPayment['contribution_id']);
    $membership = $this->callAPISuccessGetSingle('membership', ['id' => $membershipPayment['membership_id'], 'return' => 'contact_id']);
    $this->assertEquals($membership['contact_id'], $contributions[1]['contact_id']);
  }

  /**
   * Test submit with a membership block in place.
   *
   * Ensure a separate payment for the membership vs the contribution, with
   * correct amounts.
   *
   * @param string $thousandSeparator
   *   punctuation used to refer to thousands.
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   * @dataProvider getThousandSeparators
   */
  public function testSubmitMembershipBlockIsSeparatePaymentPaymentProcessorNowChargesCorrectAmounts(string $thousandSeparator): void {
    $this->setCurrencySeparators($thousandSeparator);
    $this->setUpMembershipContributionPage(TRUE);
    $this->setDummyProcessorResult(['fee_amount' => .72]);
    $testKey = 'unique key for test hook';
    $submitParams = $this->getSubmitParamsContributionPlusMembership(TRUE);
    $submitParams['test_key'] = $testKey;

    // set custom hook
    $this->hookClass->setHook('civicrm_alterPaymentProcessorParams', [$this, 'hook_civicrm_alterPaymentProcessorParams']);

    $this->submitOnlineContributionForm($submitParams, $this->getContributionPageID());

    $this->callAPISuccess('Contribution', 'get', [
      'contribution_page_id' => $this->getContributionPageID(),
      'contribution_status_id' => 1,
    ]);

    $result = civicrm_api3('SystemLog', 'get', [
      'sequential' => 1,
      'message' => ['LIKE' => "%$testKey%"],
    ]);
    $this->assertCount(2, $result['values'], "Expected exactly 2 log entries matching $testKey.");

    // Examine logged entries to ensure correct values.
    $contribution_ids = [];
    $found_membership_amount = $found_contribution_amount = FALSE;
    foreach ($result['values'] as $value) {
      [, $json] = explode("$testKey:", $value['message']);
      $logged_contribution = json_decode($json, TRUE);
      $contribution_ids[] = $logged_contribution['contributionID'];
      if (!empty($logged_contribution['total_amount'])) {
        $amount = (int) $logged_contribution['total_amount'];
      }
      else {
        $amount = (int) $logged_contribution['amount'];
      }

      if ($amount === 2) {
        $found_membership_amount = TRUE;
      }
      if ($amount === 88) {
        $found_contribution_amount = TRUE;
      }
    }

    $distinct_contribution_ids = array_unique($contribution_ids);
    $this->assertCount(2, $distinct_contribution_ids, 'Expected exactly 2 log contributions with distinct contributionIDs.');
    $this->assertTrue($found_contribution_amount, 'Expected one log contribution with amount 88 (the contribution page amount)');
    $this->assertTrue($found_membership_amount, 'Expected one log contribution with amount 2 (the membership amount)');
  }

  /**
   * Test submit recurring membership with immediate confirmation (IATS style).
   *
   * - we process 2 membership transactions against with a recurring contribution against a contribution page with an immediate
   * processor (IATS style - denoted by returning trxn_id)
   * - the first creates a new membership, completed contribution, in progress recurring. Check these
   * - create another - end date should be extended
   *
   * @throws \CRM_Core_Exception
   */
  public function testSubmitMembershipComplexQuickConfigPaymentPaymentProcessorRecurInstantPayment(): void {
    $this->params['is_recur'] = 1;
    $this->params['recur_frequency_unit'] = $membershipTypeParams['duration_unit'] = 'year';
    // Add a membership so membership & contribution are not both 1.
    $preExistingMembershipID = $this->contactMembershipCreate(['contact_id' => $this->ids['Contact']['individual_0']]);
    $this->setUpMembershipContributionPage(FALSE, FALSE, $membershipTypeParams);
    $dummyPP = $this->setDummyProcessorResult(['payment_status_id' => 1, 'trxn_id' => 'create_first_success']);
    $processor = $dummyPP->getPaymentProcessor();

    $submitParams = array_merge($this->getSubmitParamsContributionPlusMembership(TRUE), [
      'is_recur' => 1,
      'frequency_interval' => 1,
      'frequency_unit' => $this->params['recur_frequency_unit'],
    ]);

    $this->callAPISuccess('ContributionPage', 'submit', $submitParams);
    $contribution = $this->callAPISuccess('Contribution', 'getsingle', [
      'contribution_page_id' => $this->getContributionPageID(),
      'contribution_status_id' => 1,
    ]);
    $this->assertEquals($processor['payment_instrument_id'], $contribution['payment_instrument_id']);
    $membership = $this->validateContributionWithContributionAndMembershipLineItems((int) $contribution['id'], $preExistingMembershipID);

    $this->assertEquals('create_first_success', $contribution['trxn_id']);
    $this->callAPISuccess('contribution_recur', 'getsingle', ['id' => $contribution['contribution_recur_id']]);

    $this->validateContributionWithContributionAndMembershipLineItems((int) $contribution['id'], $preExistingMembershipID);

    //renew it with processor setting completed - should extend membership
    $renewContribution = $this->submitSecondContribution((int) $contribution['contact_id'], $submitParams, (int) $contribution['id']);
    $renewedMembership = $this->validateContributionWithContributionAndMembershipLineItems((int) $renewContribution['id'], $preExistingMembershipID);
    $expectedEndDate = $this->membershipRenewalDate('year', $membership['end_date']);
    $this->assertEquals($expectedEndDate, $renewedMembership['end_date']);
    $recurringContribution = $this->callAPISuccess('contribution_recur', 'getsingle', ['id' => $contribution['contribution_recur_id']]);
    $this->assertEquals($processor['payment_instrument_id'], $recurringContribution['payment_instrument_id']);
    $this->assertEquals(5, $recurringContribution['contribution_status_id']);
  }

  /**
   * Test submit recurring membership with immediate confirmation (IATS style).
   *
   * - we process 2 membership transactions against with a recurring contribution against a contribution page with an immediate
   * processor (IATS style - denoted by returning trxn_id)
   * - the first creates a new membership, completed contribution, in progress recurring. Check these
   * - create another - end date should be extended
   */
  public function testSubmitMembershipPriceSetPaymentPaymentProcessorSeparatePaymentRecurInstantPayment(): void {
    $this->setUpMembershipContributionPage(TRUE);
    $this->setDummyProcessorResult(['payment_status_id' => 1, 'trxn_id' => 'create_first_success']);
    // Set Hook to check contributionRecurID is set
    $this->hookClass->setHook('civicrm_alterPaymentProcessorParams', [$this, 'hookCheckRecurID']);

    $submitParams = array_merge($this->getSubmitParamsContributionPlusMembership(TRUE), [
      'is_recur' => 1,
      'auto_renew' => 1,
      'frequency_interval' => 1,
      'frequency_unit' => 'month',
    ]);

    $this->callAPISuccess('ContributionPage', 'submit', $submitParams);
    $contributions = $this->callAPISuccess('contribution', 'get', [
      'contribution_page_id' => $this->getContributionPageID(),
      'contribution_status_id' => 1,
    ])['values'];

    $this->assertCount(2, $contributions);

    // Check the recurring contribution is linked to the membership payment.
    $membershipPayment = $this->callAPISuccess('membership_payment', 'getsingle');
    $this->callAPISuccessGetSingle('membership', ['id' => $membershipPayment['membership_id']]);
    $this->assertNotEmpty($contributions[$membershipPayment['contribution_id']]['contribution_recur_id']);
    $this->callAPISuccess('ContributionRecur', 'getsingle');
  }

  /**
   * Implements hook_civicrm_alterPaymentProcessorParams().
   *
   * @noinspection PhpUnusedParameterInspection
   */
  public function hookCheckRecurID($paymentObj, $rawParams, $cookedParams): void {
    // @todo - fix https://lab.civicrm.org/dev/core/-/issues/567 by testing for correct passing of
    // contributionRecurID.
  }

  /**
   * Test non-recur contribution with membership payment selected.
   *
   * In this scenario the contribution option was not selected so only
   * one contribution is actually created.
   */
  public function testSubmitMembershipIsSeparatePaymentNotRecurMembershipOnly(): void {
    $this->setUpMembershipContributionPage(TRUE, TRUE);
    $this->setDummyProcessorResult(['payment_status_id' => 1, 'trxn_id' => 'create_first_success']);
    $submitParams = array_merge($this->getSubmitParamsMembership(), [
      'frequency_interval' => 1,
      'frequency_unit' => 'month',
    ]);
    $this->callAPISuccess('ContributionPage', 'submit', $submitParams);
    $recur = $this->callAPISuccess('ContributionRecur', 'get', []);
    $this->assertEmpty($recur['count']);
  }

  /**
   * Set up membership contribution page.
   *
   * @param bool $isSeparatePayment
   * @param bool $isRecur
   * @param array $membershipTypeParams Parameters to pass to
   *   MembershipType.create API
   */
  public function setUpMembershipContributionPage(bool $isSeparatePayment = FALSE, bool $isRecur = FALSE, array $membershipTypeParams = []): void {
    if (empty($this->ids['MembershipType'])) {
      $membershipTypeParams = array_merge([
        'minimum_fee' => 2,
      ], $membershipTypeParams);
      $this->ids['MembershipType'] = [$this->membershipTypeCreate($membershipTypeParams)];
    }
    $contributionPageParameters = !$isRecur ? [] : [
      'is_recur' => TRUE,
      'recur_frequency_unit' => 'month',
    ];
    $this->contributionPageQuickConfigCreate($contributionPageParameters, [], $isSeparatePayment, TRUE, TRUE, TRUE);
  }

  /**
   * Get the label for the relevant field e.g.
   *
   * price_2
   *
   * @param string $label
   *  Generally either contribution or membership.
   *
   * @return string
   */
  protected function getPriceFieldLabel(string $label): string {
    return 'price_' . $this->ids['PriceField'][$label];
  }

  /**
   * Get the label for the relevant field eg.
   *
   * price_2
   *
   * @param string $label
   *  Should be either contribution or membership.
   *
   * @return mixed|string
   */
  protected function getPriceFieldValue(string $label) {
    if (isset($this->_ids['PriceField'][$label])) {
      return 'price_' . $this->_ids['PriceFieldValue'][$label];
    }
    return $this->ids['PriceFieldValue']['membership_' . $label];
  }

  /**
   * Add text field other amount to the price set.
   */
  public function addOtherAmountFieldToMembershipPriceSet(): void {
    $this->_ids['price_field']['other_amount'] = $this->callAPISuccess('price_field', 'create', [
      'price_set_id' => reset($this->ids['PriceSet']),
      'name' => 'other_amount',
      'label' => 'Other Amount',
      'html_type' => 'Text',
      'format.only_id' => TRUE,
      'sequential' => 1,
    ]);
    $this->_ids['price_field_value']['other_amount'] = $this->callAPISuccess('price_field_value', 'create', [
      'financial_type_id' => 'Donation',
      'format.only_id' => TRUE,
      'label' => 'Other Amount',
      'amount' => 1,
      'price_field_id' => $this->_ids['price_field']['other_amount'],
    ]);
  }

  /**
   * Create a payment processor instance.
   */
  protected function setupPaymentProcessor(): void {
    $this->params['payment_processor_id'] = $this->paymentProcessorCreate([
      'payment_processor_type_id' => 'Dummy',
      'class_name' => 'Payment_Dummy',
      'billing_mode' => 1,
    ], 'dummy');
  }

  /**
   * Function to add additional price fields to price set.
   *
   * @param array $params
   */
  public function addPriceFields(array &$params): void {
    $priceSetID = $this->getPriceSetID();
    $priceField = $this->callAPISuccess('price_field', 'create', [
      'price_set_id' => $priceSetID,
      'label' => 'Chicken Breed',
      'html_type' => 'CheckBox',
    ]);
    $priceFieldValue1 = $this->callAPISuccess('price_field_value', 'create', [
      'price_set_id' => $priceSetID,
      'price_field_id' => $priceField['id'],
      'label' => 'Shoe-eating chicken -1',
      'financial_type_id' => 'Donation',
      'amount' => 30,
    ]);
    $priceFieldValue2 = $this->callAPISuccess('price_field_value', 'create', [
      'price_set_id' => $priceSetID,
      'price_field_id' => $priceField['id'],
      'label' => 'Shoe-eating chicken -2',
      'financial_type_id' => 'Donation',
      'amount' => 40,
    ]);
    $params['price_' . $priceField['id']] = [
      $priceFieldValue1['id'] => 1,
      $priceFieldValue2['id'] => 1,
    ];
  }

  /**
   * Test validating a contribution page submit.
   */
  public function testValidate(): void {
    $this->contributionPageWithPriceSetCreate();
    $errors = $this->callAPISuccess('ContributionPage', 'validate', array_merge($this->getBasicSubmitParams(), ['action' => 'submit']))['values'];
    $this->assertEmpty($errors);
  }

  /**
   * Test validating a contribution page submit in POST context.
   *
   * A likely use case for the validation is when the is being submitted and some handling is
   * to be done before processing but the validity of input needs to be checked first.
   *
   * For example PayPal Checkout will replace the confirm button with its own, but we are able to validate
   * before PayPal launches it's modal. In this case the $_REQUEST is post, but we need validation to succeed.
   */
  public function testValidatePost(): void {
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $this->contributionPageWithPriceSetCreate();
    $errors = $this->callAPISuccess('ContributionPage', 'validate', array_merge($this->getBasicSubmitParams(), ['action' => 'submit']))['values'];
    $this->assertEmpty($errors);
    unset($_SERVER['REQUEST_METHOD']);
  }

  /**
   * Test that an error is generated if required fields are not submitted.
   */
  public function testValidateOutputOnMissingRecurFields(): void {
    $this->params['is_recur_interval'] = 1;
    $this->contributionPageWithPriceSetCreate([
      'is_recur' => TRUE,
      'recur_frequency_unit' => 'month',
    ]);
    $submitParams = array_merge($this->getBasicSubmitParams(), ['action' => 'submit']);
    $submitParams['is_recur'] = 1;
    $submitParams['frequency_interval'] = '';
    $submitParams['frequency_unit'] = '';
    $errors = $this->callAPISuccess('ContributionPage', 'validate', $submitParams)['values'];
    $this->assertEquals('Please enter a number for how often you want to make this recurring contribution (EXAMPLE: Every 3 months).', $errors['frequency_interval']);
  }

  /**
   * Implements hook_civicrm_alterPaymentProcessorParams().
   *
   * @throws CRM_Core_Exception
   * @noinspection PhpUnusedParameterInspection
   */
  public function hook_civicrm_alterPaymentProcessorParams($paymentObj, $rawParams, $cookedParams): void {
    // Ensure total_amount are the same if they're both given.
    $total_amount = !empty($rawParams['total_amount']) ? (float) $rawParams['total_amount'] : NULL;
    $amount = !empty($rawParams['amount']) ? (float) $rawParams['amount'] : NULL;
    if ($total_amount !== NULL && $amount !== NULL && round($total_amount, 2) !== round($amount, 2)) {
      throw new CRM_Core_Exception("total_amount '$total_amount' and amount '$amount' differ.");
    }

    // Log parameters for later debugging and testing.
    $message = "test hook: {$rawParams['test_key']}:";
    $log_params = array_intersect_key($rawParams, [
      'amount' => 1,
      'total_amount' => 1,
      'contributionID' => 1,
    ]);
    $message .= json_encode($log_params);
    $log = new CRM_Utils_SystemLogger();
    $log->debug($message, $_REQUEST);
  }

  /**
   * Get the params for a basic simple submit.
   *
   * @return array
   */
  protected function getBasicSubmitParams(): array {
    return [
      'price_' . $this->ids['PriceField']['radio_field'] => $this->ids['PriceFieldValue']['10_dollars'],
      'id' => $this->getContributionPageID(),
      'amount' => 10,
      'priceSetId' => $this->getPriceSetID('ContributionPage'),
      'payment_processor_id' => 0,
    ];
  }

  /**
   * Get params to use for a page a membership and contribution items
   *
   * @param bool $isCardPayment
   * @param string $membershipType
   *
   * @return array
   */
  private function getSubmitParamsContributionPlusMembership(bool $isCardPayment = FALSE, string $membershipType = 'general'): array {
    $params = [
      'price_' . $this->ids['PriceField']['membership_amount'] => $this->ids['PriceFieldValue']['membership_' . $membershipType],
      'id' => $this->getContributionPageID(),
      'email-Primary' => 'billy-goat@the-bridge.net',
      'price_' . $this->ids['PriceField']['other_amount'] => 88,
    ];
    if ($isCardPayment) {
      $params += $this->getBillingSubmitValues();
    }
    return $params;
  }

  /**
   * Get submit params for the membership line only.
   *
   * @param bool $isCardPayment
   * @param string $membershipType
   *
   * @return array
   */
  protected function getSubmitParamsMembership(bool $isCardPayment = FALSE, string $membershipType = 'general'): array {
    return [
      'price_' . $this->ids['PriceField']['membership_amount'] => $this->ids['PriceFieldValue']['membership_' . $membershipType],
      'id' => $this->getContributionPageID(),
      'email-Primary' => 'billy-goat@the-bridge.net',
    ] + $this->getBillingSubmitValues();
  }

  /**
   * Get suitable values for submitting the contribution form with a billing block.
   *
   * @param string $processorIdentifier
   *
   * @return array
   */
  protected function getBillingSubmitValues(string $processorIdentifier = 'dummy'): array {
    return [
      'billing_first_name' => 'Dave',
      'billing_middle_name' => 'Joseph',
      'billing_last_name' => 'Wong',
      'email-' . \CRM_Core_BAO_LocationType::getBilling() => 'dave@example.com',
      'payment_processor_id' => $this->ids['PaymentProcessor'][$processorIdentifier],
      'credit_card_number' => '4111111111111111',
      'credit_card_type' => 'Visa',
      'credit_card_exp_date' => ['M' => 9, 'Y' => 2040],
      'cvv2' => 123,
    ];
  }

  /**
   * Validate that separate membership payments are created with
   *
   *  - 2 contributions linked to the contribution page
   *  - consisting of 1 contribution matching the passed in
   *    contribution amount and one linked to a membership through
   *    the membership payment and the line item.
   *
   * @param int $contributionPageID
   * @param float|int $contributionAmount
   */
  private function validateSeparateMembershipPaymentContributions(int $contributionPageID, $contributionAmount = 88): void {
    $contributions = $this->callAPISuccess('Contribution', 'get', ['contribution_page_id' => $contributionPageID, 'return' => 'contact_id'])['values'];
    $this->assertCount(2, $contributions);
    $lines = $this->callAPISuccess('LineItem', 'get', ['sequential' => 1, 'return' => 'line_total'])['values'];
    $this->assertEquals($contributionAmount, $lines[0]['line_total']);
    $membershipPayment = $this->callAPISuccessGetSingle('MembershipPayment', ['return' => ['contribution_id', 'membership_id']]);
    $this->assertArrayKeyExists($membershipPayment['contribution_id'], $contributions);
    $membership = $this->callAPISuccessGetSingle('membership', ['id' => $membershipPayment['membership_id'], 'return' => 'contact_id']);
    $this->assertEquals($membership['contact_id'], $contributions[$membershipPayment['contribution_id']]['contact_id']);
  }

  /**
   * Validates that one contribution has been created with 2 line items.
   *
   * Line items should be valid for one contribution and one membership.
   *
   * @param int $id
   * @param int $preExistingMembershipID
   *
   * @return array
   *   Membership
   *
   * @throws \CRM_Core_Exception
   */
  private function validateContributionWithContributionAndMembershipLineItems(int $id, int $preExistingMembershipID): array {
    $lines = $this->callAPISuccess('line_item', 'get', [
      'sequential' => 1,
      'contribution_id' => $id,
    ])['values'];
    $this->assertCount(2, $lines);
    $this->assertEquals('civicrm_contribution', $lines[1]['entity_table']);
    $this->assertEquals($id, $lines[1]['entity_id']);
    $this->assertEquals('civicrm_membership', $lines[0]['entity_table']);
    $this->assertEquals($preExistingMembershipID + 1, $lines[0]['entity_id']);
    $this->callAPISuccessGetSingle('MembershipPayment', ['contribution_id' => $id, 'membership_id' => $preExistingMembershipID + 1]);
    $membershipPayment = $this->callAPISuccess('MembershipPayment', 'getsingle', ['contribution_id' => $id]);
    $this->assertEquals($membershipPayment['contribution_id'], $id);
    $membership = $this->callAPISuccessGetSingle('membership', ['id' => $membershipPayment['membership_id']]);
    $this->assertEquals($membership['contact_id'], Contribution::get()
      ->addSelect('contact_id')
      ->addWhere('id', '=', $id)
      ->execute()->first()['contact_id']
    );
    $this->assertEquals(1, $membership['status_id']);
    return $membership;
  }

  /**
   * Submit the form a second time and make a second contribution.
   *
   * @param int $contact_id
   * @param array $submitParams
   * @param int $originalContributionID
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  private function submitSecondContribution(int $contact_id, array $submitParams, int $originalContributionID): array {
    $submitParams['contact_id'] = $contact_id;
    \Civi\Payment\System::singleton()->getById($this->ids['PaymentProcessor']['dummy'])->setDoDirectPaymentResult(['payment_status_id' => 1, 'trxn_id' => 'create_second_success']);
    $this->callAPISuccess('ContributionPage', 'submit', $submitParams);
    return $this->callAPISuccess('Contribution', 'getsingle', [
      'id' => ['NOT IN' => [$originalContributionID]],
      'version' => 4,
      'contribution_page_id' => $this->getContributionPageID(),
      'contribution_status_id' => 1,
    ]);
  }

}
