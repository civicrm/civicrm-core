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

namespace Civi\Api4\Action\Order;

use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;

/**
 *
 * @method $this setContributionValues(array $contributionValues) Set contribution values.
 * @method $this setContributionRecurValues(array $contributionRecurValues) Set contributionRecur values.
 * @method $this setOrderCompletionMetadata(array $orderCompletionMetadata) Set metadata to save against the contribution (not a specific line item), for later consumption when the order/payment completes - eg. receipt/email overrides. Metadata for a specific line item is instead set via that line item's own 'order_completion_metadata' key.
 */
class Create extends AbstractAction {

  /**
   * Values corresponding to the contribution entity.
   *
   * @var array
   */
  protected array $contributionValues;

  /**
   * Values corresponding to the ContributionRecur entity
   *
   * @var array|null
   */
  protected ?array $contributionRecurValues = NULL;

  /**
   * Line items to process
   *
   * @var array
   */
  protected array $lineItems;

  /**
   * @var array|null
   */
  protected ?array $orderCompletionMetadata = NULL;

  /**
   * @param array $lineItem
   *
   * @return $this
   */
  public function addLineItem(array $lineItem): Create {
    $this->lineItems[] = $lineItem;
    return $this;
  }

  /**
   * @param array $lineItems
   *
   * @return $this
   */
  public function setLineItems(array $lineItems): Create {
    $this->lineItems = $lineItems;
    return $this;
  }

  /**
   * @throws \CRM_Core_Exception
   */
  protected function getContributionValues(): array {
    $values = $this->contributionValues;
    $this->formatWriteValues($values, 'Contribution', 'create');
    if (empty($values['invoice_id'])) {
      $values['invoice_id'] = \CRM_Contribute_BAO_Contribution::generateInvoiceID();
    }
    $this->setContributionValues($values);
    return $values;
  }

  /**
   * @return array
   * @throws \CRM_Core_Exception
   */
  protected function getContributionRecurValues(): array {
    if (!$this->contributionRecurValues) {
      return [];
    }
    $values = $this->contributionRecurValues;
    $this->formatWriteValues($values, 'ContributionRecur', 'create');
    $this->setContributionRecurValues($values);
    return $values;
  }

  /**
   * Run the api Action.
   *
   * @param \Civi\Api4\Generic\Result $result
   *
   * @throws \CRM_Core_Exception
   */
  public function _run(Result $result): void {
    $order = new \CRM_Financial_BAO_Order();
    $order->setDefaultFinancialTypeID($this->getContributionValues()['financial_type_id'] ?? NULL);

    foreach ($this->lineItems as $index => $lineItem) {
      // A line item may carry its own 'order_completion_metadata' - that is
      // not a real LineItem field, so it isn't touched here; CRM_Financial_
      // BAO_Order::save() is responsible for reading it back off and saving
      // it as an OrderCompletionMetadata record once the line item has a
      // real id.
      $this->formatWriteValues($lineItem, 'LineItem', 'create');
      $order->setLineItem($lineItem, $index);
    }
    if ($this->orderCompletionMetadata) {
      $order->setOrderCompletionMetadata($this->orderCompletionMetadata);
    }
    $order->setContributionRecurValues($this->getContributionRecurValues());
    $order->setContributionValues($this->getContributionValues());
    $result[] = $order->validate()->save()->first();
  }

}
