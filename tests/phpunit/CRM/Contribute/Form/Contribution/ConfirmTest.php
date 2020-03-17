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
   */
  public function tearDown() {
    $this->quickCleanUpFinancialEntities();
  }

  /**
   * CRM-21200: Test that making online payment for pending contribution doesn't overwite the contribution details
   */
  public function testPaynowPayment() {
    $contactID = $this->individualCreate();
    $paymentProcessorID = $this->paymentProcessorCreate(['payment_processor_type_id' => 'Dummy']);

    // create a contribution page which is later used to make pay-later contribution
    $result = $this->callAPISuccess('ContributionPage', 'create', [
      'title' => 'Test Contribution Page',
      'financial_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'financial_type_id', 'Campaign Contribution'),
      'currency' => 'USD',
      'financial_account_id' => 1,
      'payment_processor' => $paymentProcessorID,
      'is_active' => 1,
      'is_allow_other_amount' => 1,
      'min_amount' => 20,
      'max_amount' => 2000,
    ]);
    $contributionPageID1 = $result['id'];
    // create pending contribution
    $contribution = $this->callAPISuccess('Contribution', 'create', [
      'contact_id' => $contactID,
      'financial_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'financial_type_id', 'Campaign Contribution'),
      'currency' => 'USD',
      'total_amount' => 100.00,
      'contribution_status_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'contribution_status_id', 'Pending'),
      'contribution_page_id' => $contributionPageID1,
      'source' => 'backoffice pending contribution',
    ]);

    // create a contribution page which is later used to make online payment for pending contribution
    $result = $this->callAPISuccess('ContributionPage', 'create', [
      'title' => 'Test Contribution Page',
      'financial_type_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'financial_type_id', 'Campaign Contribution'),
      'currency' => 'USD',
      'financial_account_id' => 1,
      'payment_processor' => $paymentProcessorID,
      'is_active' => 1,
      'is_allow_other_amount' => 1,
      'min_amount' => 10,
      'max_amount' => 1000,
    ]);
    $form = new CRM_Contribute_Form_Contribution_Confirm();
    $contributionPageID2 = $result['id'];
    $form->_id = $contributionPageID2;
    $form->_values = $result['values'][$contributionPageID2];
    $form->_paymentProcessor = [
      'id' => $paymentProcessorID,
      'billing_mode' => CRM_Core_Payment::BILLING_MODE_FORM,
      'object' => Civi\Payment\System::singleton()->getById($paymentProcessorID),
      'is_recur' => FALSE,
      'payment_instrument_id' => CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'payment_instrument_id', 'Credit card'),
    ];
    $form->_params = [
      'qfKey' => 'donotcare',
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
    ];

    $processConfirmResult = CRM_Contribute_BAO_Contribution_Utils::processConfirm($form,
      $form->_params,
      $contactID,
      $form->_values['financial_type_id'],
      0, FALSE
    );

    // Make sure that certain parameters are set on return from processConfirm
    $this->assertEquals($form->_values['financial_type_id'], $processConfirmResult['financial_type_id']);

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
  }

}
