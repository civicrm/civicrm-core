<?php

use Civi\Api4\ContributionProduct;
use Civi\Api4\Membership;

/**
 * @method int|null getContributionID()
 * @method $this setContributionID(?int $contributionID)
 * @method $this setContributionProductID(?int $contributionProductID)
 * @method int|null getFinancialTrxnID()
 * @method $this setFinancialTrxnID(?int $financialTrxnID)
 */
trait CRM_Contribute_WorkflowMessage_ContributionTrait {
  /**
   * The contribution.
   *
   * @var array|null
   *
   * @scope tokenContext as contribution
   */
  public $contribution;

  /**
   * The contribution product (premium) if any.
   *
   * @var array|null
   *
   * @scope tokenContext as contributionProduct
   */
  public $contributionProduct;

  /**
   * @return array|null
   */
  public function getContribution(): ?array {
    return $this->contribution;
  }

  /**
   * Optional financial transaction (payment).
   *
   * @var array|null
   *
   * @scope tokenContext as financial_trxn
   */
  public $financialTrxn;

  /**
   * @var int
   * @scope tokenContext as contributionId, tplParams as contributionID
   */
  public $contributionID;

  /**
   * @var int
   * @scope tokenContext as contribution_productId
   */
  public $contributionProductID;

  /**
   * @var int
   * @scope tokenContext as financial_trxnId
   */
  public $financialTrxnID;

  /**
   * Is the site configured such that tax should be displayed.
   *
   * @var bool
   */
  public $isShowTax;

  /**
   * Is it a good idea to show the line item subtotal.
   *
   * This would be true if at least one line has a quantity > 1.
   * Otherwise it is very repetitive.
   *
   * @var bool
   *
   * @scope tplParams
   */
  public $isShowLineSubtotal;

  /**
   * Line items associated with the contribution.
   *
   * @var array
   *
   * @scope tplParams
   */
  public $lineItems;

  /**
   * Tax rates paid.
   *
   * Generally this would look like
   *
   * ['10.00%' => 100]
   *
   * To indicate that $100 was changed for 10% tax.
   *
   * @var array
   *
   * @scope tplParams
   */
  public $taxRateBreakdown;

  /**
   * @var CRM_Financial_BAO_Order
   */
  private $order;

  /**
   * Get order, if available.
   *
   * The order is used within the class to calculate line items etc.
   *
   * @return \CRM_Financial_BAO_Order|null
   */
  private function getOrder(): ?CRM_Financial_BAO_Order {
    if (!$this->order && $this->contributionID) {
      $this->order = new CRM_Financial_BAO_Order();
      $this->order->setTemplateContributionID($this->contributionID);
      if (!empty($this->eventID)) {
        // Temporary support for tests that are making a mess of this.
        // It should always be possible to get this from the line items.
        $this->order->setPriceSetIDByEventPageID($this->eventID);
      }
    }
    return $this->order;
  }

  /**
   * Should line items be displayed for the contribution.
   *
   * This determination is based on whether the price set is quick config.
   *
   * @var bool
   *
   * @scope tplParams
   */
  public $isShowLineItems;

  /**
   * Get bool for whether a line item breakdown be displayed.
   *
   * @return bool
   * @noinspection PhpUnused
   */
  public function getIsShowLineItems(): bool {
    if (isset($this->isShowLineItems)) {
      return $this->isShowLineItems;
    }

    $order = $this->getOrder();
    if (!$order) {
      // This would only be the case transitionally.
      // Since this is a trait it is used by templates which don't (yet)
      // always have the contribution ID available as well as migrated ones.
      return FALSE;
    }
    return !$this->order->getPriceSetMetadata()['is_quick_config'];
    return $this->isShowLineItems;
  }

  /**
   * Is it a good idea to show the line item subtotal.
   *
   * This would be true if at least one line has a quantity > 1.
   * Otherwise it is very repetitive.
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  public function getIsShowLineSubtotal(): bool {
    foreach ($this->getLineItems() as $lineItem) {
      if ((int) $lineItem['qty'] > 1) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Get the line items.
   *
   * @return array
   * @throws \CRM_Core_Exception
   */
  public function getLineItems(): array {
    if (isset($this->lineItems)) {
      return $this->lineItems;
    }
    $order = $this->getOrder();
    if (!$order) {
      // This would only be the case transitionally.
      // Since this is a trait it is used by templates which don't (yet)
      // always have the contribution ID available as well as migrated ones.
      return [];
    }
    $lineItems = $order->getLineItems();
    foreach ($lineItems as $index => $lineItem) {
      if ($lineItem['entity_table'] === 'civicrm_membership' && !empty($lineItem['entity_id'])) {
        // Add in some per line membership details. This could also go in the Order class?
        $lineItems[$index]['membership'] = Membership::get(FALSE)->addWhere('id', '=', $lineItem['entity_id'])->addSelect('start_date', 'end_date')->execute()->first();
      }
    }
    return $lineItems;
  }

  /**
   * Get the line items.
   *
   * @return array
   * @throws \CRM_Core_Exception
   *
   * @noinspection PhpUnused
   */
  public function getTaxRateBreakdown(): array {
    if (isset($this->taxRateBreakdown)) {
      return $this->taxRateBreakdown;
    }
    $this->taxRateBreakdown = [];
    foreach ($this->getLineItems() as $lineItem) {
      if (!isset($this->taxRateBreakdown[$lineItem['tax_rate']])) {
        $this->taxRateBreakdown[$lineItem['tax_rate']] = [
          'amount' => 0,
          'rate' => $lineItem['tax_rate'],
          'percentage' => sprintf('%.2f', $lineItem['tax_rate']),
        ];
      }
      $this->taxRateBreakdown[$lineItem['tax_rate']]['amount'] += $lineItem['tax_amount'] ?? 0;
    }
    // Remove the rates with no value.
    foreach ($this->taxRateBreakdown as $rate => $details) {
      if ($details['amount'] === 0.0) {
        unset($this->taxRateBreakdown[$rate]);
      }
    }
    if (array_keys($this->taxRateBreakdown) === [0]) {
      // If the only tax rate charged is 0% then no tax breakdown is returned.
      $this->taxRateBreakdown = [];
    }
    return $this->taxRateBreakdown;
  }

  /**
   * @return array|null
   */
  public function getContributionProduct(): ?array {
    if (!isset($this->contributionProduct)) {
      if ($this->getContributionID()) {
        $this->contributionProduct = ContributionProduct::get(FALSE)
          ->addWhere('contribution_id', '=', $this->getContributionID())
          ->addOrderBy('id', 'DESC')->execute()->first() ?? [];
      }
      else {
        $this->contributionProduct = [];
      }
    }
    if (!empty($this->contributionProduct['id']) && !isset($this->contributionProductID)) {
      $this->contributionProductID = $this->contributionProduct['id'];
    }
    return empty($this->contributionProduct) ? [] : $this->contributionProduct;
  }

  /**
   * Set contribution object.
   *
   * @param array $contribution
   *
   * @return $this
   */
  public function setContribution(array $contribution): self {
    $this->contribution = $contribution;
    if (!empty($contribution['id'])) {
      $this->contributionID = $contribution['id'];
    }
    return $this;
  }

  /**
   * Set contribution object.
   *
   * @param array $contributionProduct
   *
   * @return $this
   */
  public function setContributionProduct(array $contributionProduct): self {
    $this->contributionProduct = $contributionProduct;
    if (!empty($contributionProduct['id'])) {
      $this->contributionProductID = $contributionProduct['id'];
    }
    return $this;
  }

  /**
   * Set contribution object.
   *
   * @param array $financialTrxn
   *
   * @return $this
   */
  public function setFinancialTrxn(array $financialTrxn): self {
    $this->financialTrxn = $financialTrxn;
    if (!empty($financialTrxn['id'])) {
      $this->financialTrxnID = $financialTrxn['id'];
    }
    return $this;
  }

  /**
   * Set order object.
   *
   * Note this is only supported for core use (specifically in example work flow)
   * as the contract might change.
   *
   * @param CRM_Financial_BAO_Order $order
   *
   * @return $this
   */
  public function setOrder(CRM_Financial_BAO_Order $order): self {
    $this->order = $order;
    return $this;
  }

  /**
   * Extra variables to be exported to smarty based on being calculated.
   *
   * We export isShowTax to denote whether invoicing is enabled but
   * hopefully at some point we will separate the assumption that invoicing
   * and tax are a package.
   *
   * @param array $export
   *
   * @noinspection PhpUnused
   */
  protected function exportExtraTplParams(array &$export): void {
    $export['isShowTax'] = (bool) Civi::settings()->get('invoicing');
  }

  /**
   * Specify any tokens that should be exported as smarty variables.
   *
   * @param array $export
   */
  protected function exportExtraTokenContext(array &$export): void {
    $export['smartyTokenAlias']['currency'] = 'contribution.currency';
    $export['smartyTokenAlias']['taxTerm'] = 'domain.tax_term';
  }

}
