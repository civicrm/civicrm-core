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
 *  Test APIv3 civicrm_contribute_* functions
 *
 * @package CiviCRM_APIv3
 * @subpackage API_Contribution
 * @group headless
 */
class CRM_Contribute_Form_Contribution_ConfirmTest extends CiviUnitTestCase {

  /**
   * Clean up DB.
   *
   * @throws \CRM_Core_Exception
   */
  public function tearDown(): void {
    $this->quickCleanUpFinancialEntities();
  }

  /**
   * CRM-21200: Test that making online payment for pending contribution
   * doesn't overwrite the contribution details
   *
   * @throws \CiviCRM_API3_Exception
   * @throws \CRM_Core_Exception
   */
  public function testPayNowPayment(): void {
    $individualID = $this->individualCreate();
    $paymentProcessorID = $this->paymentProcessorCreate(['payment_processor_type_id' => 'Dummy']);
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

    /* @var CRM_Contribute_Form_Contribution_Confirm $form*/
    $form = $this->getFormObject('CRM_Contribute_Form_Contribution_Confirm');
    $form->_id = $contributionPageID2;

    $form->_paymentProcessor = [
      'id' => $paymentProcessorID,
      'billing_mode' => CRM_Core_Payment::BILLING_MODE_FORM,
      'object' => Civi\Payment\System::singleton()->getById($paymentProcessorID),
      'is_recur' => FALSE,
      'payment_instrument_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'payment_instrument_id', 'Credit card'),
    ];
    $form->_values = [
      'id' => $contributionPageID2,
    ];
    $form->_params = [
      'contribution_id' => $contribution['id'],
      'credit_card_number' => 4111111111111111,
      'cvv2' => 234,
      'credit_card_exp_date' => [
        'M' => 2,
        'Y' => 2021,
      ],
      'credit_card_type' => 'Visa',
      'email-5' => 'test@test.com',
      'total_amount' => 100.00,
      'payment_processor_id' => $paymentProcessorID,
      'amount' => 100,
      'tax_amount' => 0.00,
      'year' => 2021,
      'month' => 2,
      'currencyID' => 'USD',
      'is_pay_later' => 0,
      'invoiceID' => '6e443672a9bb2198cc12f076aed70e7a',
      'is_quick_config' => 1,
      'description' => $contribution['values'][$contribution['id']]['source'],
      'skipLineItem' => 0,
      'frequency_interval' => 1,
      'frequency_unit' => 'month',
    ];

    $processConfirmResult = $form->processConfirm(
      $form->_params,
      $individualID,
      CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'financial_type_id', 'Campaign Contribution'),
      0, FALSE
    );

    // Make sure that certain parameters are set on return from processConfirm
    $this->assertEquals('Campaign Contribution', CRM_Core_PseudoConstant::getName('CRM_Contribute_BAO_Contribution', 'financial_type_id', $processConfirmResult['financial_type_id']));

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
    $this->assertRegExp("/Paid later via page ID: $contributionPageID2/", $contribution['contribution_source']);
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
  }

  /**
   * @param array $params
   *
   * @return mixed
   * @throws \CRM_Core_Exception
   */
  protected function createContributionPage(array $params): int {
    return (int) $this->callAPISuccess('ContributionPage', 'create', array_merge([
      'title' => 'Test Contribution Page',
      'financial_type_id' => 'Campaign Contribution',
      'currency' => 'USD',
      'financial_account_id' => 1,
      'is_active' => 1,
      'is_allow_other_amount' => 1,
      'min_amount' => 20,
      'max_amount' => 2000,
    ], $params))['id'];
  }

}
