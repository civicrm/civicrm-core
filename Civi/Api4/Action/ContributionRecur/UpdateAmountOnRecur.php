<?php
namespace Civi\Api4\Action\ContributionRecur;

use Civi\Api4\Contribution;
use Brick\Money\Money;
use Civi\Api4\ContributionRecur;
use Civi\Api4\Generic\BasicBatchAction;
use Civi\Api4\Generic\Result;

/**
 * This API Action updates the contributionRecur and related entities (templatecontribution/lineitems)
 *   when a subscription is changed.
 */
class UpdateAmountOnRecur extends BasicBatchAction {

  /**
   * The amount to update
   *
   * @var float
   * @required
   */
  protected float $amount;

  /**
   * @inheritDoc
   */
  public function _run(Result $result) {
    if ($this->amount <= 0) {
      throw new \CRM_Core_Exception(ts('Amount must be greater than 0.0'));
    }

    parent::_run($result);
  }

  /**
   * @inheritDoc
   */
  public function doTask($item) {
    $newAmount = Money::of($this->amount, $item['currency']);

    // Check if amount is the same
    if (Money::of($item['amount'], $item['currency'])->compareTo($newAmount) === 0) {
      \Civi::log()->debug('Nothing to do. Amount is already the same');
      return $item;
    }

    // Get the template contribution
    // Calling ensureTemplateContributionExists will *always* return a template contribution
    //   Either it will have created one or will return the one that already exists.
    $templateContributionID = \CRM_Contribute_BAO_ContributionRecur::ensureTemplateContributionExists($item['id']);

    // Now we update the template contribution with the new details
    // This will automatically update the Contribution LineItems as well.
    Contribution::update(FALSE)
      ->addValue('total_amount', $newAmount->getAmount()->toFloat())
      ->addWhere('id', '=', $templateContributionID)
      ->execute();

    // Update the recur
    // If we update a template contribution the recur will automatically be updated
    // (see CRM_Contribute_BAO_Contribution::self_hook_civicrm_post)
    // We need to make sure we updated the template contribution first because
    //   CRM_Contribute_BAO_ContributionRecur::self_hook_civicrm_post will also try to update it.
    return ContributionRecur::get(FALSE)
      ->addWhere('id', '=', $item['id'])
      ->execute()
      ->first();
  }

  /**
   * @inheritDoc
   */
  protected function getSelect() {
    return [
      'id',
      'amount',
      'currency',
    ];
  }

}
