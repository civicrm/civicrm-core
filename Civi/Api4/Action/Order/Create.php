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
 */
class Create extends AbstractAction {

  /**
   * Values corresponding to the contribution entity.
   *
   * @var array
   */
  protected array $contributionValues;

  /**
   * Line items to process
   *
   * @var array
   */
  protected array $lineItems;

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
      $this->formatWriteValues($lineItem, 'LineItem', 'create');
      $order->setLineItem($lineItem, $index);
    }
    $result[] = $order->save($this->getContributionValues())->first();
  }

}
