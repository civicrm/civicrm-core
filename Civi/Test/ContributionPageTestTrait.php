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

namespace Civi\Test;

use Civi\API\EntityLookupTrait;
use Civi\Api4\UFField;
use Civi\Api4\UFGroup;
use Civi\Api4\UFJoin;

/**
 * Helper for event tests.
 *
 * WARNING - this trait ships with core from 5.68 but the signatures may not yet be stable
 * and it is worth assuming that they will not be stable until 5.72.
 *
 * This provides functions to set up valid contribution pages
 * for unit tests.
 *
 * The primary functions in this class are
 * - `contributionPageCreatePaid` - this is the main function to use
 * - `contributionPageCreate` - underlying function, use for non-monetary pages.
 *
 * Calling these function will create contribution pages with associated
 * profiles and price set data as appropriate.
 */
trait ContributionPageTestTrait {
  use EntityTrait;
  use EntityLookupTrait;

  /**
   * Create a contribution page for test purposes.
   *
   * Generally this function is not called directly -
   * use contributionPageCreatePaid.
   *
   * @param array $contributionPageValues
   * @param string $identifier
   *
   * @return array
   */
  public function contributionPageCreate(array $contributionPageValues = [], string $identifier = 'ContributionPage'): array {
    $contributionPageDefaults = [
      'frontend_title' => 'Test Frontend title',
      'title' => 'Page Title',
      'name' => 'default_page',
      'financial_type_id:name' => 'Donation',
      'is_email_receipt' => TRUE,
      'is_pay_later' => $contributionPageValues['is_monetary'] ?: FALSE,
      'pay_later_text' => 'Send Money Now',
    ];
    $contributionPageValues += $contributionPageDefaults;
    $return = $this->createTestEntity('ContributionPage', $contributionPageValues, $identifier);
    $this->define('ContributionPage', 'ContributionPage_' . $identifier, $return);
    $this->addProfilesToContributionPage();
    return $return;
  }

  /**
   * Create a paid contribution page.
   *
   * This function ensures that the page has pay later configured, unless specified as false
   * and that there is a payment_processor configured, unless the key payment_processor
   * is already set.
   *
   * @param array $contributionPageValues
   * @param array $priceSetParameters
   *   Currently if 'id' is passed in then no update is made, but this could change
   * @param string $identifier
   *
   * @return array
   */
  public function contributionPageCreatePaid(array $contributionPageValues, array $priceSetParameters = [], string $identifier = 'ContributionPage'): array {
    $contributionPageValues['is_monetary'] = TRUE;
    $contributionPageValues += ['is_pay_later' => TRUE, 'pay_later_text' => 'Send check by mail'];
    if (!array_key_exists('payment_processor', $contributionPageValues)) {
      $this->createTestEntity('PaymentProcessor', [
        'name' => 'dummy',
        'label' => 'Dummy',
        'payment_processor_type_id:name' => 'Dummy',
        'frontend_title' => 'Dummy Front end',
      ], 'dummy');
      $this->createTestEntity('PaymentProcessor', [
        'name' => 'dummy',
        'label' => 'Dummy Test',
        'payment_processor_type_id:name' => 'Dummy',
        'frontend_title' => 'Dummy Front end (test)',
        'is_test' => TRUE,
      ], 'dummy_test');
      $contributionPageValues['payment_processor'] = [$this->ids['PaymentProcessor']['dummy']];
    }
    $contributionPageResult = $this->contributionPageCreate($contributionPageValues, $identifier);
    $priceSetParameters += [
      'title' => 'Price Set',
      'is_quick_config' => TRUE,
      'extends' => 'CiviContribute',
      'financial_type_id:name' => 'Donation',
      'name' => $identifier,
    ];
    if (empty($priceSetParameters['id'])) {
      $this->createTestEntity('PriceSet', $priceSetParameters, $identifier);
    }
    else {
      $this->ids['PriceSet'][$identifier] = $priceSetParameters['id'];
      // Maybe do update here??
    }
    $this->createTestEntity('PriceSetEntity', [
      'entity_table' => 'civicrm_contribution_page',
      'entity_id' => $contributionPageResult['id'],
      'price_set_id' => $this->ids['PriceSet'][$identifier],
    ]);
    $this->createTestEntity('Product', [
      'name' => '5_dollars',
      'description' => '5 dollars worth of monopoly money',
      'options' => 'White, Black, Green',
      'price' => 1,
      'is_active' => TRUE,
      'min_contribution' => 5,
      'cost' => .05,
    ], '5_dollars');
    $this->createTestEntity('Product', [
      'name' => '10_dollars',
      'description' => '10 dollars worth of monopoly money',
      'options' => 'White, Black, Green',
      'price' => 2,
      'is_active' => TRUE,
      'min_contribution' => 10,
      'cost' => .05,
    ], '10_dollars');
    $this->createTestEntity('Premium', [
      'entity_id' => $this->getContributionPageID($identifier),
      'entity_table' => 'civicrm_contribution_page',
      'premiums_intro_title' => 'Get free monopoly money with your donation',
      'premiums_active' => TRUE,
    ], $identifier);
    $this->createTestEntity('PremiumsProduct', [
      'premiums_id' => $this->ids['Premium'][$identifier],
      'product_id' => $this->ids['Product']['5_dollars'],
      'weight' => 1,
    ]);
    $this->createTestEntity('PremiumsProduct', [
      'premiums_id' => $this->ids['Premium'][$identifier],
      'product_id' => $this->ids['Product']['10_dollars'],
      'weight' => 2,
    ]);
    return $contributionPageResult;
  }

  /**
   * Get the id of the contribution page created in set up.
   *
   * If only one has been created it will be selected. Otherwise
   * you should pass in the appropriate identifier.
   *
   * @param string $identifier
   *
   * @return int
   */
  protected function getContributionPageID(string $identifier = 'ContributionPage'): int {
    if (isset($this->ids['ContributionPage'][$identifier])) {
      return $this->ids['ContributionPage'][$identifier];
    }
    if (count($this->ids['ContributionPage']) === 1) {
      return reset($this->ids['ContributionPage']);
    }
    $this->fail('Could not identify ContributionPage ID');
    // Unreachable but reduces IDE noise.
    return 0;
  }

  /**
   * Set up a contribution page with a complex price set.
   *
   * The created price set has 5 fields using a mixture of financial type 1 & 2, 3 which are created.
   *
   * More fields may be added.
   *
   * - Radio field (key= 'radio_field') with 3 options ('20_dollars','10_dollars','free'), financial type ID is 'first'
   * - Select field (key= 'select_field') with 2 options ('40_dollars','30_dollars'), financial type ID is 'second'
   * - Text field ('text_field_16.95') with amount = 16.95 - ie if qty is 2 then amount is 33.90, financial type ID is 'second'
   * - Text field ('text_field_2.95') with amount = 2.95 - ie if qty is 2 then amount is 5.90, financial type ID is 'second'
   * - Text field ('text_field') with amount = 1 - ie if qty is 2 then amount is 2, financial type ID is 'first'
   * - CheckBox field ('check_box') with amount = 55,  financial type ID is 'third'
   *
   * @param array $contributionPageParameters
   * @param array $priceSetParameters
   */
  public function contributionPageWithPriceSetCreate(array $contributionPageParameters = [], array $priceSetParameters = []): void {
    $this->contributionPageCreatePaid($contributionPageParameters, $priceSetParameters);
    $priceSetID = $this->ids['PriceSet']['ContributionPage'];
    $this->createTestEntity('FinancialType', ['name' => 'Financial Type 1'], 'first');
    $this->createTestEntity('FinancialType', ['name' => 'Financial Type 2'], 'second');
    $this->createTestEntity('FinancialType', ['name' => 'Financial Type 3'], 'third');
    $priceField = $this->createTestEntity('PriceField', [
      'price_set_id' => $priceSetID,
      'label' => 'Financial Type 1, radio field',
      'html_type' => 'Radio',
      'name' => 'radio_field',
    ], 'radio_field');
    $this->createTestEntity('PriceFieldValue', [
      'price_field_id' => $this->ids['PriceField']['radio_field'],
      'label' => 'Twenty dollars',
      'financial_type_id:name' => 'Financial Type 1',
      'amount' => 20,
      'non_deductible_amount' => 15,
    ], '20_dollars');
    $this->createTestEntity('PriceFieldValue', [
      'price_field_id' => $priceField['id'],
      'label' => '10 dollars',
      'financial_type_id:name' => 'Financial Type 1',
      'amount' => 10,
      'non_deductible_amount' => 5,
    ], '10_dollars');

    $this->createTestEntity('PriceFieldValue', [
      'price_field_id' => $priceField['id'],
      'label' => 'Free',
      'financial_type_id:name' => 'Financial Type 1',
      'amount' => 0,
      'non_deductible_amount' => 0,
      'name' => 'free',
    ], 'free')['id'];

    $this->createTestEntity('PriceField', [
      'price_set_id' => $priceSetID,
      'label' => 'Financial Type 2, select field',
      'html_type' => 'Select',
      'name' => 'select_field',
    ], 'select_field');

    $this->createTestEntity('PriceFieldValue', [
      'price_field_id' => $this->ids['PriceField']['select_field'],
      'label' => 'Forty dollars',
      'financial_type_id:name' => 'Financial Type 2',
      'amount' => 40,
      'non_deductible_amount' => 5,
    ], '40_dollars');

    $this->createTestEntity('PriceFieldValue', [
      'price_field_id' => $this->ids['PriceField']['select_field'],
      'label' => 'Thirty dollars',
      'financial_type_id:name' => 'Financial Type 2',
      'amount' => 30,
      'non_deductible_amount' => 5,
    ], '30_dollars');

    $this->createTestEntity('PriceField', [
      'price_set_id' => $priceSetID,
      'label' => 'Quantity * 16.95',
      'html_type' => 'Text',
      'name' => 'text_field_16.95',
    ], 'text_field_16.95');

    $this->createTestEntity('PriceFieldValue', [
      'price_field_id' => $this->ids['PriceField']['text_field_16.95'],
      'label' => 'Quantity * 16.95',
      'financial_type_id:name' => 'Financial Type 2',
      'amount' => '16.95',
      'name' => 'text_field_16.95',
    ], 'text_field_16.95');

    $this->createTestEntity('PriceField', [
      'price_set_id' => $priceSetID,
      'label' => '2.95 text field',
      'name' => 'text_field_2.95',
      'html_type' => 'Text',
    ], 'text_field_2.95');

    $this->createTestEntity('PriceFieldValue', [
      'price_field_id' => $this->ids['PriceField']['text_field_2.95'],
      'label' => 'Quantity * 2.95',
      'name' => 'text_field_2.95',
      'financial_type_id:name' => 'Financial Type 2',
      'amount' => '2.95',
    ], 'text_field_2.95');

    $this->createTestEntity('PriceField', [
      'price_set_id' => $priceSetID,
      'label' => 'Checkbox',
      'name' => 'check_box',
      'html_type' => 'CheckBox',
    ], 'check_box');
    $this->createTestEntity('PriceFieldValue', [
      'price_field_id' => $this->ids['PriceField']['check_box'],
      'label' => 'CheckBox, 55 donation',
      'financial_type_id:name' => 'Financial Type 3',
      'amount' => 55,
      'name' => 'check_box',
    ], 'check_box');
  }

  /**
   * Set up a contribution page configured with quick config.
   *
   * The created price set has up to 3 fields.
   *
   * - Radio field (key = 'contribution_amount') with 3 options ('contribution_amount_25','contribution_amount_15','contribution_amount_0'), financial type ID matches the page financial type.
   * - Text field ('other_amount') with amount = 1 - ie if qty is 2 then amount is 2, financial type ID matches the page financial type.
   * - Radio field (key = 'membership_amount') with one option per enabled membership type (General will be created if not exists).
   *
   * @param array $contributionPageParameters
   * @param array $priceSetParameters
   * @param bool $isSeparatePayment
   * @param bool $membershipAmountField
   *  - use false to suppress the creation of this field.
   * @param bool $contributionAmountField
   * - use false to suppress the creation of this field.
   * @param bool $otherAmountField
   * - use false to suppress the creation of this field.
   * @param string $identifier
   *
   * @noinspection PhpUnhandledExceptionInspection
   */
  public function contributionPageQuickConfigCreate(array $contributionPageParameters = [], array $priceSetParameters = [], bool $isSeparatePayment = FALSE, bool $membershipAmountField = TRUE, bool $contributionAmountField = TRUE, bool $otherAmountField = TRUE, string $identifier = 'QuickConfig'): void {
    $this->contributionPageCreatePaid($contributionPageParameters, $priceSetParameters, $identifier);
    $priceSetID = $this->ids['PriceSet']['QuickConfig'];
    if ($membershipAmountField !== FALSE) {
      $priceField = $this->createTestEntity('PriceField', [
        'price_set_id' => $priceSetID,
        'label' => 'Membership Amount',
        'html_type' => 'Radio',
        'name' => 'membership_amount',
      ], 'membership_amount');
      $membershipTypes = \CRM_Member_BAO_MembershipType::getAllMembershipTypes();
      if (empty($membershipTypes)) {
        $this->createTestEntity('MembershipType', [
          'name' => 'General',
          'duration_unit' => 'year',
          'duration_interval' => 1,
          'period_type' => 'rolling',
          'member_of_contact_id' => \CRM_Core_BAO_Domain::getDomain()->contact_id,
          'domain_id' => 1,
          'financial_type_id:name' => 'Member Dues',
          'is_active' => 1,
          'sequential' => 1,
          'minimum_fee' => 100,
          'visibility' => 'Public',
        ]);
        $membershipTypes = \CRM_Member_BAO_MembershipType::getAllMembershipTypes();
      }
      foreach ($membershipTypes as $membershipType) {
        $name = 'membership_' . strtolower($membershipType['name']);
        $this->createTestEntity('PriceFieldValue', [
          'name' => 'membership_' . $name,
          'label' => 'Membership Amount',
          'amount' => $membershipType['minimum_fee'],
          'financial_type_id:name' => 'Member Dues',
          'format.only_id' => TRUE,
          'membership_type_id' => $membershipType['id'],
          'price_field_id' => $priceField['id'],
        ], $name);
      }
      $this->createTestEntity('MembershipBlock', [
        'entity_id' => $this->getContributionPageID(),
        'entity_table' => 'civicrm_contribution_page',
        'is_required' => TRUE,
        'is_active' => TRUE,
        'is_separate_payment' => $isSeparatePayment,
        'membership_type_default' => reset($this->ids['MembershipType']),
        'membership_types' => array_fill_keys(array_keys($membershipTypes), 1),
      ]);
    }
    if ($contributionAmountField !== FALSE) {
      $priceField = $this->createTestEntity('PriceField', [
        'price_set_id' => $priceSetID,
        'label' => 'Contribution Amount',
        'html_type' => 'Radio',
        'name' => 'contribution_amount',
      ], 'contribution_amount');
      $this->createTestEntity('PriceFieldValue', [
        'price_field_id' => $priceField['id'],
        'label' => 'Fifteen',
        'name' => 'contribution_amount_15',
        'amount' => 15,
        'non_deductible_amount' => 0,
        'financial_type_id' => $this->lookup('ContributionPage_' . $identifier, 'financial_type_id'),
      ], 'contribution_amount_15');
      $this->createTestEntity('PriceFieldValue', [
        'price_field_id' => $priceField['id'],
        'label' => 'Twenty Five',
        'name' => 'contribution_amount_25',
        'amount' => 25,
        'non_deductible_amount' => 0,
        'financial_type_id' => $this->lookup('ContributionPage_' . $identifier, 'financial_type_id'),
      ], 'contribution_amount_25');
      $this->createTestEntity('PriceFieldValue', [
        'price_field_id' => $priceField['id'],
        'label' => 'Nothing',
        'name' => 'contribution_amount_0',
        'amount' => 0,
        'non_deductible_amount' => 0,
        'financial_type_id' => $this->lookup('ContributionPage_' . $identifier, 'financial_type_id'),
      ], 'contribution_amount_0');
    }
    if ($otherAmountField !== FALSE) {
      $priceField = $this->createTestEntity('PriceField', [
        'price_set_id' => $priceSetID,
        'label' => 'Other Amount',
        'html_type' => 'Text',
        'name' => 'other_amount',
      ], 'other_amount');
      $this->createTestEntity('PriceFieldValue', [
        'price_field_id' => $priceField['id'],
        'label' => 'Other Amount',
        'name' => 'other_amount',
        'amount' => 1,
        'non_deductible_amount' => 0,
        'financial_type_id' => $this->lookup('ContributionPage_' . $identifier, 'financial_type_id'),
      ], 'other_amount');
    }
  }

  /**
   * Add profiles to the event.
   *
   * This function is designed to reflect the
   * normal use case where events do have profiles.
   *
   * Note if any classes do not want profiles, or want something different,
   * the thinking is they should override this. Once that arises we can review
   * making it protected rather than private & checking we are happy with the
   * signature.
   *
   * @param string $identifier
   */
  private function addProfilesToContributionPage(string $identifier = 'ContributionPage'): void {
    $profiles = [
      ['name' => '_pre', 'title' => 'Page Pre Profile', 'weight' => 1, 'fields' => ['email']],
      ['name' => '_post', 'title' => 'Page Post Profile', 'weight' => 2, 'fields' => ['first_name', 'last_name']],
    ];
    foreach ($profiles as $profile) {
      $this->createContributionPageProfile($profile, $identifier);
    }
  }

  /**
   * Create a profile attached to an event.
   *
   * @param array $profile
   * @param string $identifier
   */
  private function createContributionPageProfile(array $profile, string $identifier): void {
    $profileName = $identifier . $profile['name'];
    $profileIdentifier = $profileName;
    try {
      $this->setTestEntity('UFGroup', UFGroup::create(FALSE)->setValues([
        'group_type' => 'Individual,Contact',
        'name' => $profileName,
        'title' => $profile['title'],
        'frontend_title' => 'Public ' . $profile['title'],
      ])->execute()->first(),
        $profileIdentifier);
    }
    catch (\CRM_Core_Exception $e) {
      $this->fail('UF group creation failed for ' . $profileName . ' with error ' . $e->getMessage());
    }
    foreach ($profile['fields'] as $field) {
      $this->setTestEntity('UFField', UFField::create(FALSE)
        ->setValues([
          'uf_group_id:name' => $profileName,
          'field_name' => $field,
          'label' => $field,
        ])
        ->execute()
        ->first(), $field . '_' . $profileIdentifier);
    }
    try {
      $this->setTestEntity('UFJoin', UFJoin::create(FALSE)->setValues([
        'module' => 'CiviContribute',
        'uf_group_id:name' => $profileName,
        'entity_id' => $this->getContributionPageID($identifier),
      ])->execute()->first(), $profileIdentifier);
    }
    catch (\CRM_Core_Exception $e) {
      $this->fail('UF join creation failed for UF Group ' . $profileName . ' with error ' . $e->getMessage());
    }
  }

  /**
   * Get suitable values for submitting the contribution form with a billing block.
   *
   * @param string $processorIdentifier
   *
   * @return array
   */
  protected function getBillingSubmitValues(string $processorIdentifier = 'dummy'): array {
    // @todo determine the fields from the processor.
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
   * @param array $submittedValues
   * @param int|null $contributionPageID
   *   Will default to calling $this->>getContributionPageID()
   * @param array $urlParameters
   *
   * @return \Civi\Test\FormWrapper|\Civi\Test\FormWrappers\EventFormOnline|\Civi\Test\FormWrappers\EventFormParticipant|null
   */
  protected function submitOnlineContributionForm(array $submittedValues, ?int $contributionPageID = NULL, array $urlParameters = []): FormWrappers\EventFormParticipant|FormWrappers\EventFormOnline|FormWrapper|null {
    $form = $this->getTestForm('CRM_Contribute_Form_Contribution_Main', $submittedValues, ['id' => $contributionPageID ?: $this->getContributionPageID()] + $urlParameters)
      ->addSubsequentForm('CRM_Contribute_Form_Contribution_Confirm');
    $form->processForm();
    return $form;
  }

}
