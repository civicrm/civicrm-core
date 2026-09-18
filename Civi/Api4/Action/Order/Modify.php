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
use Civi\Api4\LineItem;

/**
 * Modify a "Pending" (Unpaid) Order.
 * Currently we only support modifying lineItems, but might extend that in the future to support
 *   Contribution/ContributionRecur params as well.
 */
class Modify extends AbstractAction {

  /**
   * The ID of a Contribution or Template Contribution that we want to modify.
   *
   * @var int
   */
  protected int $contributionID;

  /**
   * Line items to add.
   *
   * @var array
   */
  protected array $lineItemsToAdd = [];

  /**
   * Line items to remove.
   *
   * @var array
   */
  protected array $lineItemsToRemove = [];

  /**
   * Future?
   * Line items to update
   *
   * @var array
   *
   * protected array $lineItemsToUpdate = [];
   */

  /**
   * @param array $lineItem
   *
   * @return $this
   */
  public function addLineItem(array $lineItem): Modify {
    $this->lineItemsToAdd[] = $lineItem;
    return $this;
  }

  public function removeLineItem(array $lineItem): Modify {
    $this->lineItemsToRemove[] = $lineItem;
    return $this;
  }

  /**
   * Possible future method to allow updating existing lineItem
   * public function updateLineItem(array $lineItem): Modify {
   *   $this->lineItemsToUpdate[] = $lineItem;
   *   return $this;
   * }
   */

  /**
   * Run the api Action.
   *
   * @param \Civi\Api4\Generic\Result $result
   *
   * @throws \CRM_Core_Exception
   */
  public function _run(Result $result): void {
    $order = new \CRM_Financial_BAO_Order();
    $order->setExistingContributionID($this->contributionID);

    $order->getLineItems();

    foreach ($this->lineItemsToRemove as $lineItem) {
      $this->formatWriteValues($lineItem, 'LineItem', 'create');
      // To remove a lineItem we need to remove related financialItems
      // We could make API4 LineItem::delete do that if there are no payments
      $lineItem = $order->getLineItem($lineItem['id']);
      // @todo: We can't do this here because we need to queue the changes and only process them in update()
      //   because it might still fail / not be allowed.
      LineItem::delete(FALSE)
        ->addWhere('id', '=', $lineItem['id'])
        ->execute();
      $order->removelineitem($lineItem['id']);
    }
    foreach ($this->lineItemsToAdd as $lineItem) {
      $this->formatWriteValues($lineItem, 'LineItem', 'create');
      // To add a lineItem we need to add related financialItems
      // That happens automatically with https://github.com/civicrm/civicrm-core/pull/35082
      // @todo: Contribution::create actually creates the lineItems - check it does the right thing with an updated list
      $order->setLineItem($lineItem);
    }

    /*
     * Possible future method to update lineItem directly?
     * foreach ($this->lineItemsToUpdate as $lineItem) {
     *   $this->formatWriteValues($lineItem, 'LineItem', 'create');
     *   // To update a lineItem we need to alter related financialItems (remove and re-add?)
     *   // Just call LineItem::delete and LineItem::create?
     *    // $order->setLineItem($lineItem, $index);
     * }
     */

    // @todo: Should we call $order->validate() here as well?
    //   Probably, but need to make sure we have all the params it expects.
    //   Or skip the contribution/contributionRecur parts for update.
    $result[] = $order->update();
  }

}
