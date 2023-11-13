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

  /**
   * Create a contribution page for test purposes.
   *
   * Only call this directly for unpaid contribution pages.
   * Otherwise use contributionPageCreatePaid.
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
    return $this->createTestEntity('ContributionPage', $contributionPageValues, $identifier);
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

}
