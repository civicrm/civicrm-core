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

use Civi\Api4\LineItem;
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

  /**
   * Clean up DB.
   */
  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
    parent::tearDown();
  }

  /**
   * CRM-21200: Test that making online payment for pending contribution
   * doesn't overwrite the contribution details
   *
   * @throws \CRM_Core_Exception
   */
  public function testPayNowPayment(): void {
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

    /** @var CRM_Contribute_Form_Contribution_Confirm $form */
    $_REQUEST['id'] = $contributionPageID2;
    $form = $this->getFormObject('CRM_Contribute_Form_Contribution_Confirm', [
      'contribution_id' => $contribution['id'],
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
    $processConfirmResult = $form->processConfirm(
      $form->_params,
      $individualID,
      CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'financial_type_id', 'Campaign Contribution'),
      0, FALSE
    );

    // Make sure that certain parameters are set on return from processConfirm
    $this->assertEquals('Campaign Contribution', CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'financial_type_id', $processConfirmResult['contribution']->financial_type_id));

    // Based on the processed contribution, complete transaction which update the contribution status based on payment result.
    if (!empty($processConfirmResult['contribution'])) {
      $this->callAPISuccess('contribution', 'completetransaction', [
        'id' => $processConfirmResult['contribution']->id,
        'trxn_date' => date('Y-m-d'),
        'payment_processor_id' => $paymentProcessorID,
      ]);
    }

    $contribution = $this->callAPISuccessGetSingle('Contribution', [
      'id' => $form->_params['contribution_id'],
      'return' => [
        'contribution_page_id',
        'contribution_status',
        'contribution_source',
      ],
    ]);

    // check that contribution page ID isn't changed
    $this->assertEquals($contributionPageID1, $contribution['contribution_page_id']);
    // check that paid later information is present in contribution's source
    $this->assertMatchesRegularExpression("/Paid later via page ID: $contributionPageID2/", $contribution['contribution_source']);
    // check that contribution status is changed to 'Completed' from 'Pending'
    $this->assertEquals('Completed', $contribution['contribution_status']);

    // Delete contribution.
    // @todo - figure out why & document properly. If this is just to partially
    // re-use some test set up then split into 2 tests.
    $this->callAPISuccess('contribution', 'delete', [
      'id' => $processConfirmResult['contribution']->id,
    ]);

    //Process on behalf contribution.
    unset($form->_params['contribution_id']);
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
      0, TRUE
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
    ]);
    $this->assertEquals([$form->_params['contact_id']], $activity['target_contact_id']);
    $this->assertEquals($individualID, $activity['source_contact_id']);
    $repeatContribution = $this->callAPISuccess('Contribution', 'repeattransaction', [
      'original_contribution_id' => $contribution['id'],
      'contribution_status_id' => 'Pending',
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
    $assignedVariables = $form->get_template_vars();
    $this->assertFalse($assignedVariables['is_separate_payment']);
  }

  /**
   * Test the confirm form with a separate membership payment configured.
   */
  public function testSeparatePaymentConfirm(): void {
    $isSeparateMembershipPayment = TRUE;
    $form = $this->submitFormWithMembershipAndContribution($isSeparateMembershipPayment);
    $financialTrxnId = $this->callAPISuccess('EntityFinancialTrxn', 'get', ['entity_id' => $form->getContributionID(), 'entity_table' => 'civicrm_contribution', 'sequential' => 1])['values'][0]['financial_trxn_id'];
    $financialTrxn = $this->callAPISuccess('FinancialTrxn', 'get', [
      'id' => $financialTrxnId,
    ])['values'][$financialTrxnId];
    $this->assertEquals('1111', $financialTrxn['pan_truncation']);
    $this->assertEquals(1, $financialTrxn['card_type_id']);
    $assignedVariables = $form->getTemplateVariables();
    $this->assertTrue($assignedVariables['is_separate_payment']);
    // Two emails were sent - check both. The first is a contribution
    // online receipt & the second is the membership online receipt.
    $this->assertMailSentContainingStrings([
      'Contribution Information',
      '<td style="padding: 4px; border-bottom: 1px solid #999; background-color: #f7f7f7;">
         Amount        </td>
        <td style="padding: 4px; border-bottom: 1px solid #999;">
         $1,000.00         </td>
       </tr>',
      '************1111',
    ]);
    $this->assertMailSentContainingStrings([
      'Membership Information',
      'Membership Type       </td>
       <td style="padding: 4px; border-bottom: 1px solid #999;">
        General
       </td>',
      '$1,000.00',
      'Membership Start Date',
      '************1111',
    ], 1);
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
  protected function createContributionPage(array $params, $isDefaultContributionPriceSet = TRUE): int {
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
   * @param array $submittedValues
   * @param int $contributionPageID
   *
   * @return \Civi\Test\FormWrapper|\Civi\Test\FormWrappers\EventFormOnline|\Civi\Test\FormWrappers\EventFormParticipant|null
   */
  protected function submitOnlineContributionForm(array $submittedValues, int $contributionPageID) {
    $form = $this->getTestForm('CRM_Contribute_Form_Contribution_Main', $submittedValues, ['id' => $contributionPageID])
      ->addSubsequentForm('CRM_Contribute_Form_Contribution_Confirm');
    $form->processForm();
    return $form;
  }

  /**
   * @param bool $isSeparateMembershipPayment
   *
   * @return \Civi\Test\FormWrapper|\Civi\Test\FormWrappers\EventFormOnline|\Civi\Test\FormWrappers\EventFormParticipant|null
   */
  private function submitFormWithMembershipAndContribution(bool $isSeparateMembershipPayment) {
    $paymentProcessorID = $this->paymentProcessorCreate([
      'payment_processor_type_id' => 'Dummy',
      'is_test' => FALSE,
    ]);
    $contributionPageID = $this->createContributionPage(['payment_processor' => $paymentProcessorID], FALSE);
    $this->setUpMembershipBlockPriceSet(['minimum_fee' => 1000]);
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
      'other_amount' => 100,
      'priceSetId' => $this->ids['PriceSet']['membership_block'],
      'credit_card_type' => 'Visa',
      'email-5' => 'test@test.com',
      'payment_processor_id' => $paymentProcessorID,
      'year' => 2021,
      'month' => 2,
    ];
    $form = $this->submitOnlineContributionForm($submittedValues, $contributionPageID);
    return $form;
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
  public function testSubmitContributionPageWithPriceSetQuantity(string $thousandSeparator): void {
    $this->setCurrencySeparators($thousandSeparator);
    $this->enableTaxAndInvoicing();
    $this->contributionPageWithPriceSetCreate([], ['is_quick_config' => FALSE]);
    // This function sets the Tax Rate at 10% - it currently has no way to pass Tax Rate into it - so let's work with 10%
    $this->addTaxAccountToFinancialType($this->ids['FinancialType']['second']);
    $submitParams = [
      'id' => $this->getContributionPageID(),
      'first_name' => 'J',
      'last_name' => 'T',
      'email' => 'JT@ohcanada.ca',
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
  }

}
