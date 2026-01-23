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

use Civi\Api4\Contribution;
use Civi\Api4\LineItem;
use Civi\Api4\Membership;
use Civi\Api4\MembershipBlock;
use Civi\Api4\PriceSet;
use Civi\Api4\PriceSetEntity;
use Civi\Test\ContributionPageTestTrait;
use Civi\Test\FormTrait;

/**
 *  Test APIv3 civicrm_contribute_* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Contribution
 * @group headless
 */
class CRM_Contribute_Form_Contribution_ConfirmTest extends CiviUnitTestCase {

  use CRMTraits_Financial_PriceSetTrait;
  use FormTrait;
  use ContributionPageTestTrait;

  public $_apiversion = 4;

  /**
   * Clean up DB.
   */
  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    parent::tearDown();
  }

  /**
   * Get the payment instrument ID.
   *
   * Function just exists to avoid line-wrapping hell with the
   * longer function it calls.
   *
   * @param string $name
   *
   * @return int
   */
  protected function getPaymentInstrumentID(string $name): int {
    return CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'payment_instrument_id', $name);
  }

  /**
   * CRM-21200: Test that making online payment for pending contribution
   * doesn't overwrite the contribution details
   *
   * @throws \CRM_Core_Exception
   */
  public function testPayNowPayment(): void {
    $individualID = $this->createLoggedInUser();
    $paymentProcessorID = $this->paymentProcessorCreate(['payment_processor_type_id' => 'Dummy', 'is_test' => FALSE], 'dummy');
    $processor = \Civi\Payment\System::singleton()->getById($this->ids['PaymentProcessor']['dummy']);
    $processor->setDoDirectPaymentResult(['payment_status_id' => 2, 'payment_status' => 'pending']);

    // create a contribution page which is later used to make pay-later contribution
    $contributionPageID1 = $this->createContributionPage(['payment_processor' => $paymentProcessorID]);

    // create pending contribution
    $contribution = $this->createTestEntity('Contribution', [
      'contact_id' => $individualID,
      'financial_type_id:name' => 'Campaign Contribution',
      'currency' => 'USD',
      'total_amount' => 100.00,
      'contribution_status_id:name' => 'Pending',
      'contribution_page_id' => $contributionPageID1,
      'source' => 'backoffice pending contribution',
    ]);

    // create a contribution page which is later used to make online payment for pending contribution
    $contributionPageID2 = $this->createContributionPage(['payment_processor' => $paymentProcessorID]);

    $this->submitOnlineContributionForm([
      'credit_card_number' => 4111111111111111,
      'cvv2' => 234,
      'credit_card_exp_date' => [
        'M' => 2,
        'Y' => (int) (CRM_Utils_Time::date('Y')) + 1,
      ],
      $this->getPriceFieldLabelForContributionPage($contributionPageID2) => 100,
      'credit_card_type' => 'Visa',
      'email-5' => 'test@test.com',
      'payment_processor_id' => $paymentProcessorID,
      'year' => 2021,
      'month' => 2,
      'frequency_interval' => 1,
      'frequency_unit' => 'month',
    ], $contributionPageID2, ['ccid' => $contribution['id']]);

    $contribution = Contribution::get()->addWhere('id', '=', $contribution['id'])
      ->addSelect('financial_type_id:label')
      ->execute()->single();
    // Make sure that financial type is unchanged.
    $this->assertEquals('Campaign Contribution', $contribution['financial_type_id:label']);

    // Based on the processed contribution, complete transaction which update the contribution status based on payment result.
    $this->callAPISuccess('contribution', 'completetransaction', [
      'id' => $contribution['id'],
      'trxn_date' => date('Y-m-d'),
      'payment_processor_id' => $paymentProcessorID,
      'version' => 3,
    ]);

    $contribution = $this->callAPISuccessGetSingle('Contribution', [
      'id' => $contribution['id'],
      'return' => [
        'contribution_page_id',
        'contribution_status_id:name',
        'source',
      ],
    ]);

    // check that contribution page ID isn't changed
    $this->assertEquals($contributionPageID1, $contribution['contribution_page_id']);
    // check that paid later information is present in contribution's source
    $this->assertMatchesRegularExpression("/Paid later via page ID: $contributionPageID2/", $contribution['source']);
    // check that contribution status is changed to 'Completed' from 'Pending'
    $this->assertEquals('Completed', $contribution['contribution_status_id:name']);
  }

  public function testOnBehalf(): void {
    // @todo - fix to call `submitOnlineContributionForm()` similar to testPayNowPayment
    $individualID = $this->individualCreate();
    $paymentProcessorID = $this->paymentProcessorCreate(['payment_processor_type_id' => 'Dummy', 'is_test' => FALSE]);
    CRM_Core_Config::singleton()->userPermissionClass->permissions = [];

    // create a contribution page which is later used to make pay-later contribution
    $contributionPageID1 = $this->createContributionPage(['payment_processor' => $paymentProcessorID]);

    // create pending contribution
    $contribution = $this->callAPISuccess('Contribution', 'create', [
      'contact_id' => $individualID,
      'financial_type_id' => 'Campaign Contribution',
      'currency' => 'USD',
      'total_amount' => 100.00,
      'contribution_status_id' => 'Pending',
      'contribution_page_id' => $contributionPageID1,
      'source' => 'backoffice pending contribution',
    ]);

    // create a contribution page which is later used to make online payment for pending contribution
    $contributionPageID2 = $this->createContributionPage(['payment_processor' => $paymentProcessorID]);

    $_REQUEST['id'] = $contributionPageID2;
    /** @var CRM_Contribute_Form_Contribution_Confirm $form */
    $form = $this->getFormObject('CRM_Contribute_Form_Contribution_Confirm', [
      'credit_card_number' => 4111111111111111,
      'cvv2' => 234,
      'credit_card_exp_date' => [
        'M' => 2,
        'Y' => (int) (CRM_Utils_Time::date('Y')) + 1,
      ],
      $this->getPriceFieldLabelForContributionPage($contributionPageID2) => 100,
      'credit_card_type' => 'Visa',
      'email-5' => 'test@test.com',
      'payment_processor_id' => $paymentProcessorID,
      'year' => 2021,
      'month' => 2,
      'currencyID' => 'USD',
      'is_pay_later' => 0,
      'is_quick_config' => 1,
      'description' => $contribution['values'][$contribution['id']]['source'],
      'skipLineItem' => 0,
      'frequency_interval' => 1,
      'frequency_unit' => 'month',
    ]);

    $form->_paymentProcessor = [
      'id' => $paymentProcessorID,
      'billing_mode' => CRM_Core_Payment::BILLING_MODE_FORM,
      'object' => Civi\Payment\System::singleton()->getById($paymentProcessorID),
      'is_recur' => FALSE,
      'payment_instrument_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'payment_instrument_id', 'Credit card'),
    ];
    $form->preProcess();
    $form->buildQuickForm();
    // Hack cos we are not going via postProcess (although we should fix the test to
    // do that).
    $form->_params['amount'] = 100;
    $form->_contactID = $form->_values['related_contact'] = $form->_params['onbehalf_contact_id'] = $individualID;
    $organizationID = $this->organizationCreate();
    $form->_params['contact_id'] = $organizationID;
    $this->callAPISuccess('Relationship', 'create', [
      'contact_id_a' => $individualID,
      'contact_id_b' => $organizationID,
      'relationship_type_id' => 5,
      'is_current_employer' => 1,
    ]);

    $form->_params['onbehalf_contact_id'] = $individualID;
    $form->_values['id'] = $contributionPageID1;
    $form->processConfirm(
      $form->_params,
      $organizationID,
      CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'financial_type_id', 'Campaign Contribution'),
      TRUE
    );
    //check if contribution is created on org.
    $contribution = $this->callAPISuccessGetSingle('Contribution', [
      'contact_id' => $organizationID,
    ]);

    $activity = $this->callAPISuccessGetSingle('Activity', [
      'source_record_id' => $contribution['id'],
      'contact_id' => $form->_params['onbehalf_contact_id'],
      'activity_type_id' => 'Contribution',
      'return' => 'target_contact_id',
      'version' => 3,
    ]);
    $this->assertEquals([$form->_params['contact_id']], $activity['target_contact_id']);
    $this->assertEquals($individualID, $activity['source_contact_id']);
    $repeatContribution = $this->callAPISuccess('Contribution', 'repeattransaction', [
      'original_contribution_id' => $contribution['id'],
      'contribution_status_id' => 'Pending',
      'version' => 3,
      'api.Payment.create' => [
        'total_amount' => 100,
        'payment_processor_id' => $paymentProcessorID,
      ],
    ]);
    $activity = $this->callAPISuccessGetSingle('Activity', [
      'source_record_id' => $repeatContribution['id'],
      'activity_type_id' => 'Contribution',
      'return' => ['target_contact_id', 'source_contact_id'],
    ]);
    $this->assertEquals([$organizationID], $activity['target_contact_id']);
    $this->assertEquals($individualID, $activity['source_contact_id']);
    $assignedVariables = $form->getTemplateVars();
    $this->assertFalse($assignedVariables['is_separate_payment']);
  }

  /**
   * Test that submitting other amount works with non-english currency formatting.
   *
   * @dataProvider getThousandSeparators
   *
   * @param string $thousandSeparator
   *
   * @throws \CRM_Core_Exception
   */
  public function testOtherAmountConfirm(string $thousandSeparator) : void {
    $this->setCurrencySeparators($thousandSeparator);
    $this->contributionPageQuickConfigCreate([], [], FALSE, TRUE, TRUE, TRUE);
    $processor = \Civi\Payment\System::singleton()->getById($this->ids['PaymentProcessor']['dummy']);
    $processor->setDoDirectPaymentResult(['payment_status_id' => 1, 'fee_amount' => .72]);
    $this->submitOnlineContributionForm([
      'payment_processor_id' => $this->ids['PaymentProcessor']['dummy'],
      'price_' . $this->ids['PriceField']['other_amount'] => $this->formatMoneyInput(555.00),
      'price_' . $this->ids['PriceField']['membership_amount'] => $this->ids['PriceFieldValue']['membership_general'],
      'id' => $this->getContributionPageID(),
    ] + $this->getBillingSubmitValues(), $this->getContributionPageID());
    $contribution = $this->callAPISuccessGetSingle('Contribution', ['contribution_page_id' => $this->getContributionPageID(), 'version' => 4]);
    $this->assertEquals(655, $contribution['total_amount']);
  }

  /**
   * Test the tax calculation when using a quick config price set with a membership selection & a contribution (radio) selection.
   *
   * Expected amount is $100 non-tax deductible + $25 with an additional $2.50 tax.
   */
  public function testSeparatePaymentWithTax(): void {
    $this->enableTaxAndInvoicing();
    $this->addTaxAccountToFinancialType(CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'financial_type_id', 'Donation'));
    $this->contributionPageQuickConfigCreate([], [], FALSE, TRUE, TRUE, TRUE);
    $this->submitOnlineContributionForm([
      'payment_processor_id' => $this->ids['PaymentProcessor']['dummy'],
      'price_' . $this->ids['PriceField']['contribution_amount'] => $this->ids['PriceFieldValue']['contribution_amount_25'],
      'price_' . $this->ids['PriceField']['membership_amount'] => $this->ids['PriceFieldValue']['membership_general'],
      'id' => $this->getContributionPageID(),
    ] + $this->getBillingSubmitValues(), $this->getContributionPageID());

    $contribution = $this->callAPISuccessGetSingle('Contribution', ['contribution_page_id' => $this->getContributionPageID(), 'version' => 4]);
    $this->assertEquals(2.5, $contribution['tax_amount']);
    $this->assertEquals(127.5, $contribution['total_amount']);
  }

  /**
   * Test the tax calculation when using a quick config price set with a membership selection & a contribution (radio) selection.
   *
   * Expected total amount is $200 - ie
   * - $100 non-tax deductible
   * + $100 entered in 'other_amount' which we treat as inclusive..
   *
   * $100 inclusive reverse-engineers to $90.91 + 10% tax of $9.09 = 100
   */
  public function testSeparatePaymentWithTaxOtherAmount(): void {
    $this->enableTaxAndInvoicing();
    $this->addTaxAccountToFinancialType(CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'financial_type_id', 'Donation'));
    $this->contributionPageQuickConfigCreate([], [], FALSE, TRUE, TRUE, TRUE);
    $this->submitOnlineContributionForm([
      'payment_processor_id' => $this->ids['PaymentProcessor']['dummy'],
      'price_' . $this->ids['PriceField']['other_amount'] => 100,
      'price_' . $this->ids['PriceField']['membership_amount'] => $this->ids['PriceFieldValue']['membership_general'],
      'id' => $this->getContributionPageID(),
    ] + $this->getBillingSubmitValues(), $this->getContributionPageID());

    $contribution = $this->callAPISuccessGetSingle('Contribution', ['contribution_page_id' => $this->getContributionPageID(), 'version' => 4]);
    $this->assertEquals(9.09, $contribution['tax_amount']);
    $this->assertEquals(200, $contribution['total_amount']);
  }

  /**
   * Test the confirm form with a separate membership payment configured.
   *
   * @throws \CRM_Core_Exception
   */
  public function testSeparatePaymentConfirm(): void {
    $this->contributionPageQuickConfigCreate([], [], TRUE, TRUE, TRUE, TRUE);
    $form = $this->submitOnlineContributionForm([
      'payment_processor_id' => $this->ids['PaymentProcessor']['dummy'],
      'price_' . $this->ids['PriceField']['other_amount'] => 100,
      'price_' . $this->ids['PriceField']['membership_amount'] => $this->ids['PriceFieldValue']['membership_general'],
      'id' => $this->getContributionPageID(),
    ] + $this->getBillingSubmitValues(), $this->getContributionPageID());
    $contributions = $this->callAPISuccess('Contribution', 'get', ['contribution_page_id' => $this->getContributionPageID(), 'sequential' => TRUE])['values'];
    $financialTrxnId = $this->callAPISuccess('EntityFinancialTrxn', 'get', ['entity_id' => $contributions[0]['id'], 'entity_table' => 'civicrm_contribution', 'sequential' => 1])['values'][0]['financial_trxn_id'];
    $financialTrxn = $this->callAPISuccess('FinancialTrxn', 'get', [
      'id' => $financialTrxnId,
    ])['values'][$financialTrxnId];
    $this->assertEquals('1111', $financialTrxn['pan_truncation']);
    $this->assertEquals(1, $financialTrxn['card_type_id']);

    $form->checkTemplateVariable('is_separate_payment', TRUE);
    // Two emails were sent - check both. The first is a contribution
    // online receipt & the second is the membership online receipt.
    $this->assertMailSentContainingStrings(
      [
        'Contribution Information',
        '<td style="padding: 4px; border-bottom: 1px solid #999; background-color: #f7f7f7;">
           Amount        </td>
          <td style="padding: 4px; border-bottom: 1px solid #999;">
           $100.00         </td>
         </tr>',
        '************1111',
      ],
    );
    $this->assertMailSentContainingStrings([
      'Membership Information',
      'Membership Type       </td>
       <td style="padding: 4px; border-bottom: 1px solid #999;">
         General       </td>',
      '$100.00',
      'Membership Start Date',
      '************1111',
    ], 1);
    $this->assertMailSentContainingHeaderString('Test Frontend title', 0);
    $this->assertMailSentContainingHeaderString('Test Frontend title', 1);
  }

  /**
   * Create a basic contribution page.
   *
   * @param array $params
   * @param bool $isDefaultContributionPriceSet
   *
   * @return int
   *
   * @noinspection PhpDocMissingThrowsInspection
   * @noinspection PhpUnhandledExceptionInspection
   */
  protected function createContributionPage(array $params, bool $isDefaultContributionPriceSet = TRUE): int {
    $contributionPageID = (int) $this->callAPISuccess('ContributionPage', 'create', array_merge([
      'title' => 'Test Contribution Page',
      'financial_type_id' => 'Campaign Contribution',
      'currency' => 'USD',
      'financial_account_id' => 1,
      'is_active' => 1,
      'is_allow_other_amount' => 1,
      'min_amount' => 20,
      'max_amount' => 2000,
      'is_email_receipt' => TRUE,
    ], $params))['id'];
    if ($isDefaultContributionPriceSet) {
      PriceSetEntity::create(FALSE)->setValues([
        'entity_table' => 'civicrm_contribution_page',
        'entity_id' => $contributionPageID,
        'price_set_id:name' => 'default_contribution_amount',
      ])->execute();
    }
    return $contributionPageID;
  }

  /**
   * @param bool $isSeparateMembershipPayment
   *
   * @deprecated see testSeparatePaymentWithTax for preferred way to get valid config.
   *
   * @return \Civi\Test\FormWrapper|\Civi\Test\FormWrappers\EventFormOnline|\Civi\Test\FormWrappers\EventFormParticipant|null
   */
  private function submitFormWithMembershipAndContribution(bool $isSeparateMembershipPayment) {
    $paymentProcessorID = $this->paymentProcessorCreate([
      'payment_processor_type_id' => 'Dummy',
      'is_test' => FALSE,
    ]);
    $contributionPageID = $this->createContributionPage(['payment_processor' => $paymentProcessorID], FALSE);

    if (empty($this->ids['MembershipType'])) {
      $membershipTypeParams = ['minimum_fee' => 1000];
      $this->ids['MembershipType'] = [$this->membershipTypeCreate($membershipTypeParams)];
    }
    $this->setUpMembershipBlockPriceSet();
    $this->createTestEntity('PriceSetEntity', [
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => $contributionPageID,
      'price_set_id' => $this->ids['PriceSet']['membership_block'],
    ]);

    $this->callAPISuccess('MembershipBlock', 'create', [
      'entity_id' => $contributionPageID,
      'entity_table' => 'civicrm_contribution_page',
      'is_required' => TRUE,
      'is_active' => TRUE,
      'is_separate_payment' => $isSeparateMembershipPayment,
      'membership_type_default' => $this->ids['MembershipType'],
    ]);

    $submittedValues = [
      'credit_card_number' => 4111111111111111,
      'cvv2' => 234,
      'credit_card_exp_date' => [
        'M' => 2,
        'Y' => (int) (CRM_Utils_Time::date('Y')) + 1,
      ],
      'price_' . $this->ids['PriceField']['membership'] => $this->ids['PriceFieldValue']['membership_general'],
      'price_' . $this->ids['PriceField']['contribution'] => $this->ids['PriceFieldValue']['contribution'],
      'priceSetId' => $this->ids['PriceSet']['membership_block'],
      'credit_card_type' => 'Visa',
      'email-5' => 'test@test.com',
      'payment_processor_id' => $paymentProcessorID,
      'year' => 2021,
      'month' => 2,
    ];
    return $this->submitOnlineContributionForm($submittedValues, $contributionPageID);
  }

  /**
   * Test Tax Amount is calculated properly when using PriceSet with Field Type = Text/Numeric Quantity
   *
   * This test creates a pending (pay later) contribution with 3 line items
   *
   * |qty  | unit_price| line_total| tax |total including tax|
   * | 1   | 10        | 10        | 0     |     10 |
   * | 180   | 16.95   | 3051      |305.1  |  3356.1|
   * | 110   | 2.95    | 324.5     | 32.45 |   356.95|
   *
   * Contribution total = 3723.05
   *  made up of  tax 337.55
   *          non tax 3385.5
   *
   * @param string $thousandSeparator
   *   punctuation used to refer to thousands.
   *
   * @throws \CRM_Core_Exception
   *
   * @dataProvider getThousandSeparators
   */
  public function testSubmitContributionComplexPriceSetPayLater(string $thousandSeparator): void {
    $this->setCurrencySeparators($thousandSeparator);
    $this->enableTaxAndInvoicing();
    $this->contributionPageWithPriceSetCreate([], ['is_quick_config' => FALSE]);
    // This function sets the Tax Rate at 10% - it currently has no way to pass Tax Rate into it - so let's work with 10%
    $this->addTaxAccountToFinancialType($this->ids['FinancialType']['second']);
    $submitParams = [
      'id' => $this->getContributionPageID(),
      'first_name' => 'J',
      'last_name' => 'T',
      'email-5' => 'JT@ohcanada.ca',
      'receive_date' => date('Y-m-d H:i:s'),
      'payment_processor_id' => 0,
      'priceSetId' => $this->getPriceSetID('ContributionPage'),
    ];

    // Add Existing PriceField
    // qty = 1; unit_price = $10.00. No sales tax.
    $submitParams['price_' . $this->ids['PriceField']['radio_field']] = $this->ids['PriceFieldValue']['10_dollars'];

    // Set quantity for our 16.95 text field to 180 - ie 180 * 16.95 is the code and 180 * 16.95 * 0.10 is the tax.
    $submitParams['price_' . $this->ids['PriceField']['text_field_16.95']] = 180;

    // Set quantity for our 2.95 text field to 110 - ie 180 * 2.95 is the code and 110 * 2.95 * 0.10 is the tax.
    $submitParams['price_' . $this->ids['PriceField']['text_field_2.95']] = 110;

    // This is the correct Tax Amount - use it later to compare to what the CiviCRM Core came up with at the LineItem level
    $taxAmount = ((180 * 16.95 * 0.10) + (110 * 2.95 * 0.10));
    $totalAmount = 10 + (180 * 16.95) + (110 * 2.95);

    $this->submitOnlineContributionForm($submitParams, $this->getContributionPageID());
    $this->validateAllContributions();

    $contribution = $this->callAPISuccessGetSingle('Contribution', [
      'contribution_page_id' => $this->getContributionPageID(),
    ]);

    $lineItems = LineItem::get()->addWhere('contribution_id', '=', $contribution['id'])->execute();
    $this->assertEquals($lineItems[0]['line_total'] + $lineItems[1]['line_total'] + $lineItems[2]['line_total'], round($totalAmount, 2), 'Line Item Total is incorrect.');
    $this->assertEquals(round($lineItems[0]['tax_amount'] + $lineItems[1]['tax_amount'] + $lineItems[2]['tax_amount'], 2), round($taxAmount, 2), 'Wrong Sales Tax Amount is calculated and stored.');
    $mailUtil = new CiviMailUtils($this);
    $this->callAPISuccess('Payment', 'create', [
      'contribution_id' => $contribution['id'],
      'total_amount' => round($totalAmount + $taxAmount, 2),
      'payment_instrument_id' => 'Check',
      'version' => 3,
    ]);
    $mailUtil->checkMailLog([\Civi::format()->money(337.55), 'Tax Rate', 'Subtotal']);
  }

  /**
   * Test form submission with basic price set.
   */
  public function testSubmit(): void {
    $this->contributionPageWithPriceSetCreate();
    $this->submitOnlineContributionForm([
      'id' => $this->getContributionPageID(),
      'first_name' => 'J',
      'last_name' => 'T',
      'email-5' => 'JT@ohcanada.ca',
      'receive_date' => date('Y-m-d H:i:s'),
      'payment_processor_id' => 0,
      'priceSetId' => $this->getPriceSetID('ContributionPage'),
      'price_' . $this->ids['PriceField']['radio_field'] => $this->ids['PriceFieldValue']['10_dollars'],
    ]);
    $contribution = $this->getCreatedContribution();
    $this->assertEquals(5.00, $contribution['non_deductible_amount']);
  }

  /**
   * Test form submission with multiple option price set.
   *
   * @param string $thousandSeparator
   *   punctuation used to refer to thousands.
   *
   * @dataProvider getThousandSeparators
   *
   * @throws \CRM_Core_Exception
   */
  public function testSubmitContributionPageWithPriceSetTaxEnabled(string $thousandSeparator): void {
    $this->setCurrencySeparators($thousandSeparator);
    $this->enableTaxAndInvoicing();
    $this->contributionPageWithPriceSetCreate([], ['is_quick_config' => FALSE]);
    // This function sets the Tax Rate at 10% - it currently has no way to pass Tax Rate into it - so let's work with 10%
    $this->addTaxAccountToFinancialType($this->ids['FinancialType']['second']);
    $this->submitOnlineContributionForm([
      'id' => $this->getContributionPageID(),
      'first_name' => 'Billy',
      'last_name' => 'Gruff',
      'email-5' => 'billy@goat.gruff',
      'receive_date' => date('Y-m-d H:i:s'),
      'payment_processor_id' => 0,
      'priceSetId' => $this->getPriceSetID('ContributionPage'),
      // qty = 1 * unit_price = $10.00 = 10. No sales tax.
      'price_' . $this->ids['PriceField']['radio_field'] => $this->ids['PriceFieldValue']['10_dollars'],
      // qty = 2 * unit_price = $16.95 = 33.90. Tax = $3.39.
      'price_' . $this->ids['PriceField']['text_field_16.95'] => 2,
    ] + $this->getBillingSubmitValues(),
      $this->getContributionPageID()
    );

    $contribution = Contribution::get()->addWhere('contribution_page_id', '=', $this->getContributionPageID())->execute()->first();
    $this->assertEquals(47.29, $contribution['total_amount']);
    $lineItems = $this->callAPISuccess('LineItem', 'get', [
      'contribution_id' => $contribution['id'],
    ]);
    $this->assertEquals(2, $lineItems['count']);
    $totalLineAmount = 0;
    foreach ($lineItems['values'] as $lineItem) {
      $totalLineAmount += $lineItem['line_total'];
    }
    $this->assertEquals(43.90, $totalLineAmount);
    $this->assertMailSentContainingStrings([
      \Civi::format()->money(3.39),
      'Tax Rate',
      'Subtotal',
    ]);
  }

  /**
   * Test submit with a membership block in place.
   *
   * This test uses a quick config price set - which means line items
   * do not show on the receipts. Separate payments are only supported
   * with quick config.
   *
   * We are expecting a separate payment for the membership vs the contribution.
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testSubmitMembershipBlockIsSeparatePaymentPaymentProcessorNow(): void {
    $this->contributionPageQuickConfigCreate([], [], TRUE, TRUE, TRUE, TRUE);
    $processor = \Civi\Payment\System::singleton()->getById($this->ids['PaymentProcessor']['dummy']);
    $processor->setDoDirectPaymentResult(['payment_status_id' => 1, 'fee_amount' => .72]);
    $this->submitOnlineContributionForm([
      'payment_processor_id' => $this->ids['PaymentProcessor']['dummy'],
      'price_' . $this->ids['PriceField']['contribution_amount'] => $this->ids['PriceFieldValue']['contribution_amount_15'],
      'price_' . $this->ids['PriceField']['membership_amount'] => $this->ids['PriceFieldValue']['membership_general'],
      'id' => $this->getContributionPageID(),
    ] + $this->getBillingSubmitValues(),
    $this->getContributionPageID());

    $contributions = $this->callAPISuccess('Contribution', 'get', [
      'contribution_page_id' => $this->getContributionPageID(),
      'contribution_status_id' => 1,
    ])['values'];
    $this->assertCount(2, $contributions);
    $membershipPayment = $this->callAPISuccess('MembershipPayment', 'getsingle', ['return' => ['contribution_id', 'membership_id'], 'version' => 3]);
    $this->assertArrayHasKey($membershipPayment['contribution_id'], $contributions);
    $membership = $this->callAPISuccessGetSingle('Membership', ['id' => $membershipPayment['membership_id']]);
    $this->assertEquals($membership['contact_id'], $contributions[$membershipPayment['contribution_id']]['contact_id']);
    $lineItem = $this->callAPISuccessGetSingle('LineItem', ['entity_table' => 'civicrm_membership']);
    $this->assertEquals($membership['id'], $lineItem['entity_id']);
    $this->assertEquals($membershipPayment['contribution_id'], $lineItem['contribution_id']);
    $this->assertEquals(1, $lineItem['qty']);
    $this->assertEquals(100, $lineItem['unit_price']);
    $this->assertEquals(100, $lineItem['line_total']);
    foreach ($contributions as $contribution) {
      $this->assertEquals(.72, $contribution['fee_amount']);
      $this->assertEquals($contribution['total_amount'] - .72, $contribution['net_amount']);
    }
    $this->assertMailSentContainingStrings(['$15.00', 'Contribution Information'], 0);
    $this->assertMailSentContainingStrings([
      'Membership Information',
      'Membership Type',
      'General',
      'Membership Start Date',
      'Membership Fee',
      '$100',
    ], 1);
  }

  /**
   * Test submit opting for the membership and not the contribution.
   *
   * @throws \CRM_Core_Exception
   */
  public function testSubmitMembershipBlockNotSeparatePaymentMembershipOnly(): void {
    $this->contributionPageQuickConfigCreate([], [], FALSE, TRUE, TRUE, TRUE);
    $this->submitOnlineContributionForm([
      'payment_processor_id' => $this->ids['PaymentProcessor']['dummy'],
      'price_' . $this->ids['PriceField']['contribution_amount'] => -1,
      'price_' . $this->ids['PriceField']['membership_amount'] => $this->ids['PriceFieldValue']['membership_general'],
      'id' => $this->getContributionPageID(),
    ] + $this->getBillingSubmitValues(),
    $this->getContributionPageID());

    $contribution = $this->callAPISuccess('Contribution', 'getsingle', ['contribution_page_id' => $this->getContributionPageID()]);
    $membershipPayment = $this->callAPISuccess('MembershipPayment', 'getsingle', ['contribution_id' => $contribution['id'], 'version' => 3]);
    $this->callAPISuccessGetSingle('LineItem', ['contribution_id' => $contribution['id'], 'entity_id' => $membershipPayment['id']]);
    $this->assertMailSentContainingStrings([
      'Dear Dave,',
      'Membership Information',
      'Membership Type General',
      'Membership Start Date',
      'Membership Expiration',
      'Membership Fee',
      'Amount $100.00',
    ]);
    $this->assertMailSentContainingHeaderString('Test Frontend title');
  }

  public function testSubmitWithPremium(): void {
    $this->contributionPageWithPriceSetCreate();
    $this->submitOnlineContributionForm([
      'id' => $this->getContributionPageID(),
      'selectProduct' => $this->ids['Product']['ContributionPage'],
      'options_' . $this->ids['Product']['ContributionPage'] => 'clumsy smurf',
      'price_' . $this->ids['PriceField']['radio_field'] => $this->ids['PriceFieldValue']['10_dollars'],
    ] + $this->getBillingSubmitValues());

    $this->assertMailSentCount(1);
    $this->assertMailSentContainingStrings([
      'clumsy smurf',
      'Blue Creature',
      'sku-be-do',
      '.97',
    ]);
  }

  /**
   * CRM-21711 Test that custom fields on relevant memberships get updated when
   * updating multiple memberships
   *
   * @throws \CRM_Core_Exception
   */
  public function testCustomFieldsOnMembershipGetUpdated(): void {
    $contactID = $this->individualCreate();
    $contactID1 = $this->organizationCreate();
    $contactID2 = $this->organizationCreate();

    // create membership types
    $membershipTypeOne = civicrm_api3('MembershipType', 'create', [
      'domain_id' => 1,
      'name' => 'One',
      'member_of_contact_id' => $contactID1,
      'duration_unit' => 'year',
      'minimum_fee' => 50,
      'duration_interval' => 1,
      'period_type' => 'fixed',
      'fixed_period_start_day' => '101',
      'fixed_period_rollover_day' => '1231',
      'financial_type_id' => 1,
      'weight' => 50,
      'is_active' => 1,
      'visibility' => 'Public',
    ]);

    $membershipTypeTwo = civicrm_api3('MembershipType', 'create', [
      'domain_id' => 1,
      'name' => 'Two',
      'member_of_contact_id' => $contactID2,
      'duration_unit' => 'year',
      'minimum_fee' => 50,
      'duration_interval' => 1,
      'period_type' => 'fixed',
      'fixed_period_start_day' => '101',
      'fixed_period_rollover_day' => '1231',
      'financial_type_id' => 1,
      'weight' => 51,
      'is_active' => 1,
      'visibility' => 'Public',
    ]);

    //create custom Fields
    $membershipCustomFieldsGroup = civicrm_api3('CustomGroup', 'create', [
      'title' => 'Custom Fields on Membership',
      'extends' => 'Membership',
    ]);

    $membershipCustomField = civicrm_api3('CustomField', 'create', [
      'custom_group_id' => $membershipCustomFieldsGroup['id'],
      'name' => 'my_membership_custom_field',
      'label' => 'Membership Custom Field',
      'data_type' => 'String',
      'html_type' => 'Text',
      'is_active' => TRUE,
      'text_length' => 255,
    ]);

    // Create profile.
    $membershipCustomFieldsProfile = $this->createTestEntity('UFGroup', [
      'is_active' => 1,
      'group_type' => 'Membership,Individual',
      'title' => 'Membership Custom Fields',
      'add_captcha' => 0,
      'is_map' => '0',
      'is_edit_link' => '0',
      'is_uf_link' => '0',
      'is_update_dupe' => '0',
    ]);

    // add custom fields to profile
    civicrm_api3('UFField', 'create', [
      'uf_group_id' => $membershipCustomFieldsProfile['id'],
      'field_name' => 'custom_' . $membershipCustomField['id'],
      'is_active' => '1',
      'visibility' => 'User and User Admin Only',
      'in_selector' => '0',
      'is_searchable' => '0',
      'label' => 'custom text field on membership',
      'field_type' => 'Membership',
    ]);

    $contribPage = civicrm_api3('ContributionPage', 'create', [
      'title' => 'Membership',
      'financial_type_id' => 1,
      'financial_account_id' => 1,
      'is_credit_card_only' => '0',
      'is_monetary' => '0',
      'is_recur' => '0',
      'is_confirm_enabled' => '1',
      'is_recur_interval' => '0',
      'is_recur_installments' => '0',
      'adjust_recur_start_date' => '0',
      'is_pay_later' => '1',
      'pay_later_text' => 'I will send payment by check',
      'is_partial_payment' => '0',
      'is_email_receipt' => '0',
      'is_active' => '1',
      'amount_block_is_active' => '0',
      'currency' => 'USD',
      'is_share' => '0',
      'is_billing_required' => '0',
      'contribution_type_id' => '2',
      'is_allow_other_amount' => 1,
      'min_amount' => 10,
      'max_amount' => 1000,
    ]);
    $contribPage1 = $contribPage['id'];

    //create price set with two options for the two different memberships
    $priceSet = civicrm_api3('PriceSet', 'create', [
      'title' => 'Two Membership Type Checkbox',
      'extends' => 'CiviMember',
      'is_active' => 1,
      'financial_type_id' => '1',
    ]);
    CRM_Core_DAO::executeQuery("INSERT INTO civicrm_price_set_entity (entity_table, entity_id, price_set_id) VALUES('civicrm_contribution_page', $contribPage1, {$priceSet['id']})");

    $priceField = civicrm_api3('PriceField', 'create', [
      'price_set_id' => $priceSet['id'],
      'name' => 'mt',
      'label' => 'Membership Types',
      'html_type' => 'CheckBox',
      'is_enter_qty' => '0',
      'weight' => '1',
      'is_display_amounts' => '1',
      'options_per_line' => '1',
      'is_active' => '1',
      'is_required' => '0',
      'visibility_id' => '1',
    ]);

    $priceFieldOption1 = civicrm_api3('PriceFieldValue', 'create', [
      'price_field_id' => $priceField['id'],
      'name' => 'membership_type_one',
      'label' => 'Membership Type One',
      'amount' => '50',
      'weight' => '1',
      'membership_type_id' => $membershipTypeOne['id'],
      'membership_num_terms' => '1',
      'is_default' => '0',
      'is_active' => '1',
      'financial_type_id' => '1',
      'non_deductible_amount' => '0.00',
      'contribution_type_id' => '2',
    ]);

    $priceFieldOption2 = civicrm_api3('PriceFieldValue', 'create', [
      'price_field_id' => $priceField['id'],
      'name' => 'membership_type_two',
      'label' => 'Membership Type Two',
      'amount' => '50',
      'weight' => '1',
      'membership_type_id' => $membershipTypeTwo['id'],
      'membership_num_terms' => '1',
      'is_default' => '0',
      'is_active' => '1',
      'financial_type_id' => '1',
      'non_deductible_amount' => '0.00',
      'contribution_type_id' => '2',
    ]);

    // assign profile with custom fields to contribution page
    civicrm_api3('UFJoin', 'create', [
      'module' => 'CiviContribute',
      'weight' => '1',
      'uf_group_id' => $membershipCustomFieldsProfile['id'],
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => $contribPage1,
    ]);
    MembershipBlock::create(FALSE)->setValues([
      'entity_id' => $contribPage1,
      'entity_table' => 'civicrm_contribution_page',
      'is_separate_payment' => FALSE,
    ])->execute();

    $this->submitOnlineContributionForm([
      'id' => $contribPage1,
      "custom_{$membershipCustomField['id']}" => 'Hello',
      'price_' . $priceField['id'] => [$priceFieldOption1['id'] => 1, $priceFieldOption2['id'] => 1],
      'invoiceID' => '9a6f7b49358dc31c3604e463b225c5be',
      'email' => 'admin@example.com',
      'description' => 'Membership Contribution',
      'contact_id' => $contactID,
      'email-5' => 'test@test.com',
      'amount' => 100,
    ], $contribPage1);
    $membership1 = civicrm_api3('Membership', 'getsingle', [
      'contact_id' => $contactID,
      'membership_type_id' => $membershipTypeOne['id'],
    ]);
    $this->assertEquals('Hello', $membership1["custom_{$membershipCustomField['id']}"]);

    $membership2 = civicrm_api3('Membership', 'getsingle', [
      'contact_id' => $contactID,
      'membership_type_id' => $membershipTypeTwo['id'],
    ]);
    $this->assertEquals('Hello', $membership2["custom_{$membershipCustomField['id']}"]);
  }

  /**
   * Test non-membership donation on a contribution page
   * using membership PriceSet.
   */
  public function testDonationOnMembershipPagePriceSet(): void {
    $contactID = $this->individualCreate();
    $this->createPriceSetWithPage();
    $this->submitOnlineContributionForm([
      'price_' . $this->ids['PriceField']['default'] => $this->ids['PriceFieldValue']['donation'],
      'email' => 'admin@example.com',
      'contact_id' => $contactID,
      'select_contact_id' => $contactID,
      'email-5' => 'test@test.com',
      'is_pay_later' => 1,
    ], $this->ids['ContributionPage']['default']);

    $contribution = $this->callAPISuccessGetSingle('Contribution', [
      'contact_id' => $contactID,
    ]);
    //Check no membership is created.
    $this->callAPIFailure('Membership', 'getsingle', [
      'contact_id' => $contactID,
    ]);
    $this->contributionDelete($contribution['id']);
    // Membership price set.
    $this->submitOnlineContributionForm([
      "price_{$this->ids['PriceField']['default']}" => $this->ids['PriceFieldValue']['one_term_membership'],
      'email' => 'admin@example.com',
      'currencyID' => 'USD',
      'description' => 'Membership Contribution',
      'contact_id' => $contactID,
      'select_contact_id' => $contactID,
      'email-5' => 'test@test.com',
      'is_pay_later' => 1,
    ], $this->ids['ContributionPage']['default']);

    $contribution = $this->callAPISuccessGetSingle('Contribution', [
      'contact_id' => $contactID,
    ]);
    //Check membership is created for the contact.
    $membership = $this->callAPISuccessGetSingle('Membership', [
      'contact_id' => $contactID,
    ]);
    $membershipPayment = $this->callAPISuccessGetSingle('MembershipPayment', [
      'contribution_id' => $contribution['id'],
      'version' => 3,
    ]);
    $this->assertEquals($membershipPayment['membership_id'], $membership['id']);
  }

  /**
   * Test a zero dollar membership (quick config, not separate payment).
   *
   * @throws \CRM_Core_Exception
   */
  public function testSubmitMembershipBlockNotSeparatePaymentZeroDollarsWithEmail(): void {
    $this->membershipTypeCreateFree();
    $this->contributionPageQuickConfigCreate([], [], FALSE, TRUE, TRUE, TRUE);
    $this->submitOnlineContributionForm([
      'payment_processor_id' => $this->ids['PaymentProcessor']['dummy'],
      'price_' . $this->ids['PriceField']['contribution_amount'] => -1,
      'price_' . $this->ids['PriceField']['membership_amount'] => $this->ids['PriceFieldValue']['membership_free'],
      'id' => $this->getContributionPageID(),
    ] + $this->getBillingSubmitValues(),
    $this->getContributionPageID());

    $contribution = $this->callAPISuccessGetSingle('Contribution', ['contribution_page_id' => $this->getContributionPageID()]);
    $this->callAPISuccessGetSingle('MembershipPayment', ['version' => 3, 'contribution_id' => $contribution['id']]);
    //Assert only one mail is being sent.
    $this->assertMailSentCount(1);
    $this->assertMailSentContainingStrings([
      'Free',
    ]);
    $this->assertMailSentNotContainingString('Amount');
  }

  /**
   * Get the just-created contribution.
   *
   * @return array
   */
  protected function getCreatedContribution(): array {
    return $this->callAPISuccessGetSingle('Contribution', [
      'contribution_page_id' => $this->getContributionPageID(),
      'version' => 4,
    ]);
  }

  /**
   * Test submit recurring (yearly) membership with immediate confirmation (IATS style).
   *
   * - we process 2 membership transactions against with a recurring contribution against a contribution page with an immediate
   * processor (IATS style - denoted by returning trxn_id)
   * - the first creates a new membership, completed contribution, in progress recurring. Check these
   * - create another - end date should be extended
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testSubmitMembershipPriceSetPaymentPaymentProcessorRecurInstantPaymentYear(): void {
    $this->doSubmitMembershipPriceSetPaymentPaymentProcessorRecurInstantPayment(['duration_unit' => 'year', 'recur_frequency_unit' => 'year']);
  }

  /**
   * Test submit recurring (monthly) membership with immediate confirmation (IATS style).
   *
   * - we process 2 membership transactions against with a recurring contribution against a contribution page with an immediate
   * processor (IATS style - denoted by returning trxn_id)
   * - the first creates a new membership, completed contribution, in progress recurring. Check these
   * - create another - end date should be extended
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testSubmitMembershipPriceSetPaymentPaymentProcessorRecurInstantPaymentMonth(): void {
    $this->doSubmitMembershipPriceSetPaymentPaymentProcessorRecurInstantPayment(['duration_unit' => 'month', 'recur_frequency_unit' => 'month']);
  }

  /**
   * Helper function for testSubmitMembershipPriceSetPaymentProcessorRecurInstantPayment*
   *
   * @param array $params
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function doSubmitMembershipPriceSetPaymentPaymentProcessorRecurInstantPayment(array $params = []): void {
    $membershipTypeParams['duration_unit'] = $params['duration_unit'];
    if ($params['recur_frequency_unit'] === $params['duration_unit']) {
      $durationUnit = $params['duration_unit'];
    }
    else {
      $durationUnit = NULL;
    }
    if (empty($this->ids['MembershipType'])) {
      $membershipTypeParams = array_merge([
        'minimum_fee' => 2,
      ], $membershipTypeParams);
      $this->ids['MembershipType'] = [$this->membershipTypeCreate($membershipTypeParams)];
    }
    $contributionPageParameters = [
      'is_recur' => TRUE,
      'recur_frequency_unit' => 'month',
    ];
    $this->contributionPageQuickConfigCreate($contributionPageParameters, [], FALSE, TRUE, TRUE, TRUE);
    $dummyPP = Civi\Payment\System::singleton()->getById($this->ids['PaymentProcessor']['dummy']);
    $dummyPP->setDoDirectPaymentResult(['payment_status_id' => 1, 'trxn_id' => 'create_first_success']);
    $processor = $dummyPP->getPaymentProcessor();

    if ($params['recur_frequency_unit'] === $params['duration_unit']) {
      // Membership will be in "New" state because it will get confirmed as payment matches
      $expectedMembershipStatus = 1;
    }
    else {
      // Membership will still be in "Pending" state as it won't get confirmed as payment doesn't match
      $expectedMembershipStatus = 5;
    }

    $submitParams = [
      'price_' . $this->ids['PriceField']['membership_amount'] => $this->ids['PriceFieldValue']['membership_general'],
      'is_recur' => 1,
      'billing_first_name' => 'Billy',
      'billing_middle_name' => 'Goat',
      'billing_last_name' => 'Gruff',
      'email-Primary' => 'billy-goat@the-bridge.net',
      'payment_processor_id' => $this->ids['PaymentProcessor']['dummy'],
      'credit_card_number' => '4111111111111111',
      'credit_card_type' => 'Visa',
      'credit_card_exp_date' => ['M' => 9, 'Y' => 2040],
      'cvv2' => 123,
      'frequency_interval' => 1,
      'frequency_unit' => $params['recur_frequency_unit'],
      'version' => 3,
    ];
    $this->submitOnlineContributionForm($submitParams,
      $this->getContributionPageID());
    $contribution = $this->callAPISuccess('contribution', 'getsingle', [
      'contribution_page_id' => $this->getContributionPageID(),
      'contribution_status_id' => 1,
    ]);
    $this->assertEquals($processor['payment_instrument_id'], $contribution['payment_instrument_id']);

    $this->assertEquals('create_first_success', $contribution['trxn_id']);
    $membershipPayment = $this->callAPISuccess('membership_payment', 'getsingle', ['version' => 3]);
    $this->assertEquals($membershipPayment['contribution_id'], $contribution['id']);
    $membership = $this->callAPISuccessGetSingle('membership', ['id' => $membershipPayment['membership_id']]);
    $this->assertEquals($membership['contact_id'], $contribution['contact_id']);
    $this->assertEquals($expectedMembershipStatus, $membership['status_id']);
    $this->callAPISuccess('ContributionRecur', 'getsingle', ['id' => $contribution['contribution_recur_id']]);
    $this->assertEquals($contribution['contribution_recur_id'], $membership['contribution_recur_id']);

    $this->callAPISuccess('line_item', 'getsingle', ['contribution_id' => $contribution['id'], 'entity_id' => $membership['id']]);
    //renew it with processor setting completed - should extend membership
    $submitParams['contact_id'] = $contribution['contact_id'];
    $dummyPP->setDoDirectPaymentResult(['payment_status_id' => 1, 'trxn_id' => 'create_second_success']);
    $this->submitOnlineContributionForm($submitParams,
      $this->getContributionPageID());
    $this->callAPISuccess('contribution', 'getsingle', [
      'id' => ['NOT IN' => [$contribution['id']]],
      'contribution_page_id' => $this->getContributionPageID(),
      'contribution_status_id' => 1,
    ]);
    $renewedMembership = $this->callAPISuccessGetSingle('membership', ['id' => $membershipPayment['membership_id']]);
    if ($durationUnit) {
      // We only have an end_date if frequency units match, otherwise membership won't be auto-renewed and dates won't be calculated.
      $renewedMembershipEndDate = $this->membershipRenewalDate($durationUnit, $membership['end_date']);
      $this->assertEquals($renewedMembershipEndDate, $renewedMembership['end_date']);
    }
    $recurringContribution = $this->callAPISuccess('contribution_recur', 'getsingle', ['id' => $contribution['contribution_recur_id']]);
    $this->assertEquals($processor['payment_instrument_id'], $recurringContribution['payment_instrument_id']);
    $this->assertEquals(5, $recurringContribution['contribution_status_id']);
  }

  /**
   * Helper function to set up contribution page which can be used to purchase a
   * membership type for different intervals.
   */
  public function setUpMultiIntervalMembershipContributionPage(): void {
    // These all have auto_renew set to 2 - ie require auto-renew.
    $this->membershipTypeCreate([
      'title' => 'monthly',
      'auto_renew' => 2,
      'duration_unit' => 'month',
      'minimum_fee' => 10,
    ], 'monthly');

    $this->membershipTypeCreate([
      'title' => 'bi_monthly',
      'auto_renew' => 2,
      'duration_unit' => 'month',
      'duration_interval' => 2,
      'minimum_fee' => 79,
    ], 'bi_monthly');

    $this->membershipTypeCreate([
      'auto_renew' => 2,
      'title' => 'yearly',
      'duration_unit' => 'year',
      'minimum_fee' => 100,
    ], 'yearly');
    $this->contributionPageQuickConfigCreate([], [], FALSE);
  }

  /**
   * Test submit with a membership block in place.
   *
   * @dataProvider getBooleanDataProvider
   *
   * @param bool $isQuickConfig
   */
  public function testSubmitMultiIntervalMembershipContributionPage(bool $isQuickConfig): void {
    $this->setUpMultiIntervalMembershipContributionPage();
    PriceSet::update()->setValues(['is_quick_config' => $isQuickConfig])->addWhere('id', '=', $this->ids['PriceSet']['QuickConfig'])->execute();
    if (!$isQuickConfig) {
      $this->createTestEntity('PriceFieldValue', [
        'name' => 'CRM-21177_12_Months',
        'label' => 'CRM-21177 - 12 Months',
        'amount' => 200,
        'membership_num_terms' => 12,
        'membership_type_id' => $this->ids['MembershipType']['monthly'],
        'price_field_id' => $this->ids['PriceField']['membership_amount'],
        'financial_type_id:name' => 'Member Dues',
      ], 'membership_12_months');
    }
    $submitParams = [
      'price_' . $this->ids['PriceField']['membership_amount'] => $this->ids['PriceFieldValue']['membership_monthly'],
      'first_name' => 'Billy',
      'last_name' => 'Gruff',
      'email' => 'billy@goat.gruff',
      'payment_processor_id' => $this->ids['PaymentProcessor']['dummy'],
      'credit_card_number' => '4111111111111111',
      'credit_card_type' => 'Visa',
      'credit_card_exp_date' => ['M' => 9, 'Y' => 2040],
      'cvv2' => 123,
    ];
    $this->submitOnlineContributionForm($submitParams,
      $this->getContributionPageID());
    $membership = $this->callAPISuccessGetSingle('Membership', []);
    $contributionRecur = $this->callAPISuccessGetSingle('ContributionRecur', ['id' => $membership['contribution_recur_id']]);
    $this->assertEquals('month', $contributionRecur['frequency_unit']);
    $this->assertEquals(1, $contributionRecur['frequency_interval']);

    $submitParams['price_' . $this->ids['PriceField']['membership_amount']] = $this->ids['PriceFieldValue']['membership_yearly'];
    $this->submitOnlineContributionForm($submitParams,
      $this->getContributionPageID());
    $membership = $this->callAPISuccessGetSingle('Membership', ['membership_type_id' => $this->ids['MembershipType']['yearly']]);
    $contributionRecur = $this->callAPISuccessGetSingle('ContributionRecur', ['id' => $membership['contribution_recur_id']]);
    $this->assertEquals('year', $contributionRecur['frequency_unit']);
    $this->assertEquals(1, $contributionRecur['frequency_interval']);

    if (!$isQuickConfig) {
      $submitParams['price_' . $this->ids['PriceField']['membership_amount']] = $this->ids['PriceFieldValue']['membership_12_months'];
      $this->submitOnlineContributionForm($submitParams,
        $this->getContributionPageID());
      $contribution = $this->callAPISuccess('Contribution', 'get', [
        'contribution_page_id' => $this->getContributionPageID(),
        'sequential' => 1,
        'api.ContributionRecur.getsingle' => [],
        'version' => 3,
      ]);
      $this->assertEquals(1, $contribution['values'][0]['api.ContributionRecur.getsingle']['frequency_interval']);
      $this->assertEquals(1, $contribution['values'][1]['api.ContributionRecur.getsingle']['frequency_interval']);
      $this->assertEquals(12, $contribution['values'][2]['api.ContributionRecur.getsingle']['frequency_interval']);

      $this->assertEquals('month', $contribution['values'][0]['api.ContributionRecur.getsingle']['frequency_unit']);
      $this->assertEquals('year', $contribution['values'][1]['api.ContributionRecur.getsingle']['frequency_unit']);
      $this->assertEquals('month', $contribution['values'][2]['api.ContributionRecur.getsingle']['frequency_unit']);
    }

    $submitParams['price_' . $this->ids['PriceField']['membership_amount']] = $this->ids['PriceFieldValue']['membership_bi_monthly'];
    $this->submitOnlineContributionForm($submitParams,
      $this->getContributionPageID());
    $membership = $this->callAPISuccessGetSingle('Membership', ['membership_type_id' => $this->ids['MembershipType']['bi_monthly']]);
    $contributionRecur = $this->callAPISuccessGetSingle('ContributionRecur', ['id' => $membership['contribution_recur_id']]);
    $this->assertEquals('month', $contributionRecur['frequency_unit']);
    $this->assertEquals(2, $contributionRecur['frequency_interval']);
  }

  public function testSubmitInHonorOfContributionPage(): void {
    $tributeProfile = $this->callAPISuccess('UFGroup', 'create', [
      'group_type' => [
        'Individual',
        'Contact',
      ],
      'title' => 'Tribute',
      'frontend_title' => 'Tribute',
    ]);
    $fields = [
      'First Name' => 'first_name',
      'Last Name' => 'last_name',
      'Street Address (Home)' => 'street_address',
      'City (Home)' => 'city',
      'Postal Code (Home)' => 'postal_code',
      'Country (Home)' => 'country',
      'State (Home)' => 'state_province',
    ];
    foreach ($fields as $label => $fieldName) {
      $field_type = 'Individual';
      if ($fieldName !== 'first_name' && $fieldName !== 'last_name') {
        $field_type = 'Contact';
      }
      $params = [
        'uf_group_id' => $tributeProfile['id'],
        'field_name' => $fieldName,
        'label' => $label,
        'field_type' => $field_type,
      ];
      if ($field_type === 'Contact') {
        $params['location_type_id'] = 1;
      }
      $this->callAPISuccess('UFField', 'create', $params);
    }
    $this->contributionPageWithPriceSetCreate();
    $this->callAPISuccess('UFJoin', 'create', [
      'is_active' => 1,
      'module' => 'CiviEvent',
      'entity_table' => 'civicrm_event',
      'entity_id' => $this->getContributionPageID(),
      'weight' => 1,
      'uf_group_id' => 1,
    ]);
    $this->callAPISuccess('UFJoin', 'create', [
      'is_active' => 1,
      'module' => 'soft_credit',
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => 1,
      'uf_group_id' => $tributeProfile['id'],
      'module_data' => [
        'soft_credit' => [
          'soft_credit_types' => [
            0 => '1',
            1 => '2',
          ],
          'default' => [
            'honor_block_title' => 'SL Test',
            'honor_block_text' => '',
          ],
        ],
      ],
    ]);
    $processor = \Civi\Payment\System::singleton()->getById($this->ids['PaymentProcessor']['dummy']);
    $processor->setDoDirectPaymentResult(['payment_status_id' => 1, 'fee_amount' => .72]);
    $this->submitOnlineContributionForm([
      'priceSetId' => $this->getPriceSetID('ContributionPage'),
      'price_' . $this->ids['PriceField']['radio_field'] => $this->ids['PriceFieldValue']['10_dollars'],
      'id' => $this->getContributionPageID(),
      'soft_credit_type_id' => 2,
      'honor' => [
        'first_name' => 'James',
        'last_name' => 'Bond',
        'street_address-1' => 'Vaxhaul Cross',
        'city-1' => 'London',
        'postal_code-1' => 'M4K1J1',
        'country-1' => 1039,
        'state_province-1' => 1068,
      ],
    ] + $this->getBillingSubmitValues(), $this->getContributionPageID());
    $contribution = $this->callAPISuccessGetSingle('Contribution', ['contribution_page_id' => $this->getContributionPageID(), 'version' => 4]);
    $this->assertEquals(10, $contribution['total_amount']);
    // Ensure that Honoree details have been pritned
    $this->assertMailSentContainingStrings(
      [
        'In Memory of',
        'Name    James Bond',
      ],
    );
  }

  /**
   * Basic setup for membership tests.
   * @return array
   */
  public function setupMembershipContributionPage(): array {
    $this->createLoggedInUser();
    $this->individualCreate([], 'member');
    $this->restoreMembershipTypes();
    $membershipTypes = \CRM_Member_BAO_MembershipType::getAllMembershipTypes();
    // Make sure the MembershipType ids are set as restoreMembershipTypes just uses Api4 to create the types.
    if (!empty($membershipTypes)) {
      foreach ($membershipTypes as $membershipType) {
        $name = strtolower($membershipType['name']);
        if (empty($this->ids['MembershipType'][$name])) {
          $this->ids['MembershipType'][$name] = $membershipType['id'];
        }
      }
    }
    $this->contributionPageQuickConfigCreate([], [], FALSE, TRUE, TRUE, TRUE, 'existingMemberPage');
    $year = (int) (CRM_Utils_Time::date('Y')) - 1;
    $original_membership = Membership::create(FALSE)
      ->addValue('membership_type_id:name', 'Student')
      ->addValue('contact_id', $this->ids['Contact']['member'])
      ->addValue('start_date', $year . '-01-01')
      ->addValue('join_date', $year . '-01-01')
      ->addValue('end_date', $year . '-12-31')
      ->execute()
      ->first();
    return ['original_membership' => $original_membership];
  }

  /**
   * Test to make sure that a membership renewal doesn't happen when a payment
   * fails.
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testSubmitMembershipRenewalFailedPayment() : void {
    $items = $this->setupMembershipContributionPage();
    $original_membership = $items['original_membership'];
    $processor = \Civi\Payment\System::singleton()->getById($this->ids['PaymentProcessor']['dummy']);
    $processor->setDoDirectPaymentResult(['is_error' => 1]);
    $submitParams = [
      'price_' . $this->ids['PriceField']['membership_amount'] => $this->ids['PriceFieldValue']['membership_student'],
      'contact_id' => $this->ids['Contact']['member'],
      'first_name' => 'Billy',
      'last_name' => 'Gruff',
      'email' => 'billy@goat.gruff',
      'payment_instrument_id' => $this->getPaymentInstrumentID('Credit Card'),
      'payment_processor_id' => $this->ids['PaymentProcessor']['dummy'],
      'credit_card_number' => '4111111111111111',
      'credit_card_type' => 'Visa',
      'credit_card_exp_date' => ['M' => 9, 'Y' => 2040],
      'cvv2' => 123,
    ];
    $this->submitOnlineContributionForm($submitParams,
        $this->getContributionPageID('existingMemberPage'), ['cid' => $this->ids['Contact']['member']]);
    // Make sure we have a filed payment/contribution.
    $this->callAPISuccessGetCount('Contribution', [
      'contact_id' => $this->ids['Contact']['member'],
      'contribution_status_id' => 2,
    ], 1);
    $membership = Membership::get(FALSE)
      ->addWhere('contact_id', '=', $this->ids['Contact']['member'])
      ->execute()
      ->first();
    $expectedDate = date('Y-m-d', strtotime($original_membership['end_date']));
    // Make sure that the end data hasn't changed since payment failed.
    $this->assertEquals($expectedDate, $membership['end_date']);
  }

  /**
   * Test to make sure that a membership renewal happens when a payment is successful.
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testSubmitMembershipRenewalSuccessPayment() : void {
    $items = $this->setupMembershipContributionPage();
    $original_membership = $items['original_membership'];
    $this->submitOnlineContributionForm([
      'contact_id' => $this->ids['Contact']['member'],
      'payment_processor_id' => $this->ids['PaymentProcessor']['dummy'],
      'price_' . $this->ids['PriceField']['contribution_amount'] => -1,
      'price_' . $this->ids['PriceField']['membership_amount'] => $this->ids['PriceFieldValue']['membership_student'],
      'id' => $this->getContributionPageID('existingMemberPage'),
      'credit_card_exp_date' => [
        'M' => 9,
        'Y' => (int) (CRM_Utils_Time::date('Y')) + 1,
      ],
    ] + $this->getBillingSubmitValues(),
    $this->getContributionPageID('existingMemberPage'), ['cid' => $this->ids['Contact']['member']]);
    $membership = Membership::get(FALSE)
      ->addWhere('contact_id', '=', $this->ids['Contact']['member'])
      ->execute()
      ->first();
    // Make sure that the end data hasn't changed since payment failed.
    $expectedDate = new DateTime($original_membership['end_date']);
    $expectedDate->modify('last day of +1 year');
    $this->assertEquals($expectedDate->format('Y-m-d'), $membership['end_date']);
  }

  /**
   * Test to make sure that a membership type doesn't change when a payment
   * fails.
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testSubmitMembershipTypeChangedFailedPayment() : void {
    $items = $this->setupMembershipContributionPage();
    $original_membership = $items['original_membership'];
    $processor = \Civi\Payment\System::singleton()->getById($this->ids['PaymentProcessor']['dummy']);
    $processor->setDoDirectPaymentResult(['is_error' => 1]);
    $submitParams = [
      'price_' . $this->ids['PriceField']['membership_amount'] => $this->ids['PriceFieldValue']['membership_general'],
      'contact_id' => $this->ids['Contact']['member'],
      'first_name' => 'Billy',
      'last_name' => 'Gruff',
      'email' => 'billy@goat.gruff',
      'payment_instrument_id' => $this->getPaymentInstrumentID('Credit Card'),
      'payment_processor_id' => $this->ids['PaymentProcessor']['dummy'],
      'credit_card_number' => '4111111111111111',
      'credit_card_type' => 'Visa',
      'credit_card_exp_date' => ['M' => 9, 'Y' => 2040],
      'cvv2' => 123,
    ];
    $this->submitOnlineContributionForm($submitParams,
      $this->getContributionPageID('existingMemberPage'), ['cid' => $this->ids['Contact']['member']]);
    // Make sure we have a filed payment/contribution.
    $this->callAPISuccessGetCount('Contribution', [
      'contact_id' => $this->ids['Contact']['member'],
      'contribution_status_id' => 2,
    ], 1);
    $membership = Membership::get(FALSE)
      ->addWhere('contact_id', '=', $this->ids['Contact']['member'])
      ->execute()
      ->first();
    // Make sure that the end data hasn't changed since payment failed.
    $expectedDate = date('Y-m-d', strtotime($original_membership['end_date']));
    $this->assertEquals($expectedDate, $membership['end_date']);
    // Make sure that the membership type doesnt change due to failed payment.
    $this->assertEquals($original_membership['membership_type_id'], $membership['membership_type_id']);
  }

  /**
   * Test to make sure that a membership type changes when a payment is successful.
   *
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  public function testSubmitMembershipTypeChangeSuccessPayment() : void {
    $items = $this->setupMembershipContributionPage();
    $original_membership = $items['original_membership'];
    $this->submitOnlineContributionForm([
      'contact_id' => $this->ids['Contact']['member'],
      'payment_processor_id' => $this->ids['PaymentProcessor']['dummy'],
      'price_' . $this->ids['PriceField']['membership_amount'] => $this->ids['PriceFieldValue']['membership_general'],
      'id' => $this->getContributionPageID('existingMemberPage'),
      'credit_card_exp_date' => [
        'M' => 9,
        'Y' => (int) (CRM_Utils_Time::date('Y')) + 1,
      ],
    ] + $this->getBillingSubmitValues(),
    $this->getContributionPageID('existingMemberPage'), ['cid' => $this->ids['Contact']['member']]);
    $membership = Membership::get(FALSE)
      ->addWhere('contact_id', '=', $this->ids['Contact']['member'])
      ->execute()
      ->first();
    // Make sure that the end data hasn't changed since payment failed.
    $expectedDate = new DateTime($original_membership['end_date']);
    $expectedDate->modify('last day of +2 years');
    $this->assertEquals($expectedDate->format('Y-m-d'), $membership['end_date']);
    // Make sure the membership type is changed.
    $this->assertEquals($this->ids['MembershipType']['general'], $membership['membership_type_id']);
  }

}
