<?php

/**
 * @method array getContribution()
 * @method ?int getContributionID()
 * @method $this setContributionID(?int $contributionId)
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
   * @var int
   * @scope tokenContext as contributionId
   */
  public $contributionId;

  /**
   * Is the site configured such that tax should be displayed.
   *
   * @var bool
   */
  public $isShowTax;

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
    if (!$this->order && $this->contributionId) {
      $this->order = new CRM_Financial_BAO_Order();
      $this->order->setTemplateContributionID($this->contributionId);
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
      $this->contributionId = $contribution['id'];
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
   */
  protected function exportExtraTplParams(array &$export): void {
    $export['isShowTax'] = (bool) Civi::settings()->get('invoicing');
  }

}
