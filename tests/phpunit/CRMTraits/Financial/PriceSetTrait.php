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
 * Trait PriceSetTrait
 *
 * Trait for working with Price Sets in tests
 */
trait CRMTraits_Financial_PriceSetTrait {

  /**
   * Create a contribution with 2 line items.
   *
   * This also involves creating t
   *
   * @param $params
   * @param array $lineItemFinancialTypes
   *   Financial Types, if an override is intended.
   */
  protected function createContributionWithTwoLineItemsAgainstPriceSet($params, $lineItemFinancialTypes = []) {
    $params = array_merge(['total_amount' => 300, 'financial_type_id' => 'Donation', 'contribution_status_id' => 'Pending'], $params);
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
    $this->callAPISuccess('Payment', 'create', ['contribution_id' => $order['id'], 'total_amount' => $params['total_amount']]);
  }

}
