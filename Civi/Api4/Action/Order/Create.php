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
  protected $contributionValues;

  protected $lineItems;

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
    $financialType = $values['financial_type_id:name'] ?? $values['financial_type_id.name'] ?? NULL;
    if (empty($values['financial_type_id']) && $financialType) {
      $values['financial_type_id'] = (int) \CRM_Core_PseudoConstant::getKey('CRM_Contribute_BAO_Contribution', 'financial_type_id', $financialType);
      if (!$values['financial_type_id']) {
        throw new \CRM_Core_Exception(ts('Invalid financial type %1', [1 => $financialType]));
      }
    }
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
      $order->setLineItem($lineItem, $index);
    }
    $result[] = $order->save($this->getContributionValues())->first();
  }

}
