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

use Civi\Api4\PriceSet;

/**
 * Trait PriceSetTrait
 *
 * Trait for working with Price Sets in tests
 */
trait CRMTraits_Financial_PriceSetTrait {

  /**
   * Get the created price set id.
   *
   * @param string $key
   *
   * @return int
   */
  protected function getPriceSetID(string $key = 'membership'):int {
    return $this->ids['PriceSet'][$key];
  }

  /**
   * Get the created price field id.
   *
   * @param string $key
   *
   * @return int
   */
  protected function getPriceFieldID(string $key = 'membership'):int {
    return $this->ids['PriceField'][$key];
  }

  /**
   * Create a contribution with 2 line items.
   *
   * This also involves creating t
   *
   * @param $params
   * @param array $lineItemFinancialTypes
   *   Financial Types, if an override is intended.
   */
  protected function createContributionWithTwoLineItemsAgainstPriceSet($params, array $lineItemFinancialTypes = []): void {
    $params = (array) array_merge([
      'total_amount' => 300,
      'financial_type_id' => 'Donation',
      'contribution_status_id' => 'Pending',
    ], $params);
    $priceFields = $this->createPriceSet('contribution');
    foreach ($priceFields['values'] as $key => $priceField) {
      $financialTypeID = (!empty($lineItemFinancialTypes) ? array_shift($lineItemFinancialTypes) : $priceField['financial_type_id']);
      $params['line_items'][]['line_item'][$key] = [
        'price_field_id' => $priceField['price_field_id'],
        'price_field_value_id' => $priceField['id'],
        'label' => $priceField['label'],
        'field_title' => $priceField['label'],
        'qty' => 1,
        'unit_price' => $priceField['amount'],
        'line_total' => $priceField['amount'],
        'financial_type_id' => $financialTypeID,
        'entity_table' => 'civicrm_contribution',
      ];
    }
    $order = $this->callAPISuccess('Order', 'create', $params);
    $this->callAPISuccess('Payment', 'create', [
      'contribution_id' => $order['id'],
      'total_amount' => $params['total_amount'],
    ]);
  }

  /**
   * Create a non-quick-config price set with all memberships in it.
   *
   * The price field is of type checkbox and each price-option
   * corresponds to a membership type
   *
   * @return array
   */
  protected function createMembershipPriceSet(): array {
    $this->ids['PriceSet']['membership'] = (int) $this->callAPISuccess('PriceSet', 'create', [
      'is_quick_config' => 0,
      'extends' => 'CiviMember',
      'financial_type_id' => 1,
      'title' => 'my Page',
    ])['id'];

    $this->ids['PriceField']['membership'] = (int) $this->callAPISuccess('PriceField', 'create', [
      'price_set_id' => $this->getPriceSetID(),
      'label' => 'Memberships',
      'html_type' => 'Checkbox',
    ])['id'];

    // Add a few variants to assign.
    $labels = ['Shoe eating goat', 'Long Haired Goat', 'Pesky rabbit', 'Rabbits are goats too', 'Runaway rabbit'];
    $amounts = [10, 20, 259, 88, 133];
    $membershipNumTerms = [1, 1, 2, 1, 1, 1];
    foreach ($this->ids['membership_type'] as $membershipKey => $membershipTypeID) {
      $this->ids['PriceFieldValue'][$membershipKey] = $this->callAPISuccess('price_field_value', 'create', [
        'price_set_id' => $this->ids['PriceSet'],
        'price_field_id' => $this->ids['PriceField']['membership'],
        'label' => array_shift($labels),
        'amount' => array_shift($amounts),
        'financial_type_id' => 'Donation',
        'membership_type_id' => $membershipTypeID,
        'membership_num_terms' => array_shift($membershipNumTerms),
      ])['id'];
    }
    return [
      $this->ids['PriceFieldValue']['AnnualRollingOrg2'] => 1,
      $this->ids['PriceFieldValue']['AnnualRolling'] => 1,
    ];
  }

  /**
   * Set up a membership block (quick config) price set.
   *
   * This creates a price set consistent with a contribution
   * page with non-quick config membership and an optional
   * additional contribution non-membership amount.
   *
   * @param array $membershipTypeParams
   *
   * @throws \API_Exception
   * @throws \CiviCRM_API3_Exception
   */
  protected function setUpMembershipBlockPriceSet(array $membershipTypeParams = []): void {
    $this->ids['PriceSet']['membership_block'] = PriceSet::create(FALSE)
      ->setValues([
        'is_quick_config' => TRUE,
        'extends' => 'CiviMember',
        'name' => 'Membership Block',
        'title' => 'Membership, not quick config',
      ])
      ->execute()->first()['id'];

    if (empty($this->ids['MembershipType'])) {
      $membershipTypeParams = array_merge([
        'minimum_fee' => 2,
      ], $membershipTypeParams);
      $this->ids['MembershipType'] = [$this->membershipTypeCreate($membershipTypeParams)];
    }
    $priceField = $this->callAPISuccess('price_field', 'create', [
      'price_set_id' => $this->ids['PriceSet']['membership_block'],
      'name' => 'membership_amount',
      'label' => 'Membership Amount',
      'html_type' => 'Radio',
      'sequential' => 1,
    ]);
    $this->ids['PriceField']['membership'] = $priceField['id'];

    foreach ($this->ids['MembershipType'] as $membershipTypeID) {
      $priceFieldValue = $this->callAPISuccess('price_field_value', 'create', [
        'name' => 'membership_amount',
        'label' => 'Membership Amount',
        'amount' => CRM_Member_BAO_MembershipType::getMembershipType($membershipTypeID)['minimum_fee'],
        'financial_type_id' => 'Donation',
        'format.only_id' => TRUE,
        'membership_type_id' => $membershipTypeID,
        'price_field_id' => $priceField['id'],
      ]);
      $key = 'membership_' . strtolower(CRM_Member_BAO_MembershipType::getMembershipType($membershipTypeID)['name']);
      $this->ids['PriceFieldValue'][$key] = $priceFieldValue;
    }
    if (!empty($this->ids['MembershipType']['org2'])) {
      $priceField = $this->callAPISuccess('price_field', 'create', [
        'price_set_id' => reset($this->_ids['price_set']),
        'name' => 'membership_org2',
        'label' => 'Membership Org2',
        'html_type' => 'Checkbox',
        'sequential' => 1,
      ]);
      $this->ids['PriceField']['org2'] = $priceField['id'];

      $priceFieldValue = $this->callAPISuccess('price_field_value', 'create', [
        'name' => 'membership_org2',
        'label' => 'Membership org 2',
        'amount' => 55,
        'financial_type_id' => 'Member Dues',
        'format.only_id' => TRUE,
        'membership_type_id' => $this->ids['MembershipType']['org2'],
        'price_field_id' => $priceField['id'],
      ]);
      $this->ids['PriceFieldValue']['org2'] = $priceFieldValue;
    }
    $priceField = $this->callAPISuccess('price_field', 'create', [
      'price_set_id' => $this->ids['PriceSet']['membership_block'],
      'name' => 'Contribution',
      'label' => 'Contribution',
      'html_type' => 'Text',
      'sequential' => 1,
      'is_enter_qty' => 1,
    ]);
    $this->ids['PriceField']['contribution'] = $priceField['id'];
    $priceFieldValue = $this->callAPISuccess('price_field_value', 'create', [
      'name' => 'contribution',
      'label' => 'Give me money',
      'amount' => 88,
      'financial_type_id' => 'Donation',
      'format.only_id' => TRUE,
      'price_field_id' => $priceField['id'],
    ]);
    $this->ids['PriceFieldValue']['contribution'] = $priceFieldValue;
  }

}
