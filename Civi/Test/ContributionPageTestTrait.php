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
   * @param array $contributionPageValues
   * @param array $priceSetParameters
   *   Currently if 'id' is passed in then no update is made, but this could change
   * @param string $identifier
   *
   * @return array
   */
  public function contributionPageCreatePaid(array $contributionPageValues, array $priceSetParameters = [], string $identifier = 'ContributionPage'): array {
    $contributionPageValues['is_monetary'] = TRUE;
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

}
