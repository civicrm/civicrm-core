<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
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
