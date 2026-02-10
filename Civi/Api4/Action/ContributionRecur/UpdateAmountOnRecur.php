<?php
namespace Civi\Api4\Action\ContributionRecur;

use Civi\Api4\Contribution;
use Brick\Money\Money;
use Civi\Api4\ContributionRecur;
use Civi\Api4\Generic\AbstractBatchAction;
use Civi\Api4\Generic\Result;

/**
 * This API Action updates the contributionRecur and related entities (templatecontribution/lineitems)
 *   when a subscription is changed.
 *
 */
class UpdateAmountOnRecur extends AbstractBatchAction {

  /**
   * The amount to update
   *
   * @var float
   * @required
   */
  protected float $amount;

  /**
   *
   * Note that the result class is that of the annotation below, not the hint
   * in the method (which must match the parent class)
   *
   * @var \Civi\Api4\Generic\Result $result
   */
  public function _run(Result $result) {
    if ($this->amount <= 0) {
      throw new \CRM_Core_Exception(ts('Amount must be greater than 0.0'));
    }

    // Load the recurs.
    $recurs = ContributionRecur::get(FALSE)
      ->setWhere($this->where)
      ->execute();

    foreach ($recurs as $recur) {
      $newAmount = Money::of($this->amount, $recur['currency']);
      $recurResults[] = $this->updateRecurAndTemplateContributionAmount($recur, $newAmount);
    }
    $result->exchangeArray($recurResults ?? []);
    return $result;
  }

  /**
   * @param array $recur
   * @param \Brick\Money\Money $newAmount
   *
   * @return array
   * @throws \Brick\Money\Exception\MoneyMismatchException
   * @throws \Brick\Money\Exception\UnknownCurrencyException
   * @throws \CRM_Core_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  private function updateRecurAndTemplateContributionAmount(array $recur, Money $newAmount): array {
    // Check if amount is the same
    if (Money::of($recur['amount'], $recur['currency'])->compareTo($newAmount) === 0) {
      \Civi::log()->debug('nothing to do. Amount is already the same');
      return $recur;
    }

    // Get the template contribution
    // Calling ensureTemplateContributionExists will *always* return a template contribution
    //   Either it will have created one or will return the one that already exists.
    $templateContributionID = \CRM_Contribute_BAO_ContributionRecur::ensureTemplateContributionExists($recur['id']);

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
      ->addWhere('id', '=', $recur['id'])
      ->execute()
      ->first();
  }

}
