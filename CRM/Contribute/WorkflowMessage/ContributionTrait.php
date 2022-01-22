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
   * @scope tokenContext as contribution_id
   */
  public $contributionId;

  /**
   * Is the site configured such that tax should be displayed.
   *
   * @var bool
   */
  public $isShowTax;

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
