<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | Use of this source code is governed by the AGPL license with some  |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

use Civi\Test\FormTrait;

/**
 * Class CRM_Contribute_Form_UpdateBillingTest
 */
class CRM_Contribute_Form_UpdateBillingTest extends CiviUnitTestCase {

  use CRMTraits_Contribute_RecurFormsTrait;
  use FormTrait;

  /**
   * Test the mail sent on update.
   */
  public function testMail(): void {
    $this->addContribution();
    $billingLocationID = CRM_Core_BAO_LocationType::getBilling();
    $form = $this->getTestForm('CRM_Contribute_Form_UpdateBilling',
      [
        'is_notify' => TRUE,
        'credit_card_number' => '4444333322221111',
        'credit_card_exp_date' => ['Y' => '2032', 'M' => '08'],
        'credit_card_type' => 'Visa',
        'billing_first_name' => 'Charles',
        'billing_last_name' => 'Windsor',
        "billing_street_address-{$billingLocationID}" => '1 Buckingham Palace',
        "billing_city-{$billingLocationID}" => 'London',
        "biiling_postal_code-{$billingLocationID}" => 'SW1A 1AA',
        "billing_country_id-{$billingLocationID}" => '1003',
        "billing_state_province_id-{$billingLocationID}" => '',
        "state_province-{$billingLocationID}" => '',
      ],
      ['crid' => $this->getContributionRecurID()]);
    $form->processForm();
    $this->assertMailSentContainingHeaderStrings([
      'Return-Path: bob@example.org',
      'Anthony Anderson <anthony_anderson@civicrm.org>',
      'Subject: Recurring Contribution Billing Updates - Mr. Anthony Anderson II',
    ]);
    $this->assertMailSentContainingStrings($this->getExpectedMailStrings());
  }

  /**
   * Get the strings to check for.
   *
   * @return string[]
   */
  public function getExpectedMailStrings(): array {
    return [
      'Dear Anthony,',
      'Visa',
      '************1111',
      'Billing details for your recurring contribution of 10.00, every 1 month have been updated.',
      'If you have questions please contact us at "Bob" <bob@example.org>.',
    ];
  }

}
