<?php

/**
 * Trait for non-participant workflow classes.
 *
 * This uses that trait but assigns profiles in a flatter
 * structure. ie. a single title for each profile and single
 * array of fields, rather than one per participant.
 *
 * @method string getNote()
 */
trait CRM_Core_WorkflowMessage_SingleProfileTrait {
  use CRM_Core_WorkflowMessage_ProfileTrait;

  /**
   * @var array
   *
   * @scope tplParams as customPre_grouptitle
   */
  public $profileTitlePreForm;

  /**
   * @var array
   *
   * @scope tplParams as customPost_grouptitle
   */
  public $profileTitlePostForm;

  /**
   * @var array
   *
   * @scope tplParams as customPre
   */
  public $profilePreForm;

  /**
   * @var array
   *
   * @scope tplParams as customPost
   */
  public $profilePostForm;

  /**
   * @var array
   *
   * @scope tplParams as honoreeProfile
   */
  public $profileSoftCredit;

  /**
   * @var array
   *
   * @scope tplParams as onBehalfProfile
   */
  public $profileOnBehalf;

  /**
   * @var array
   *
   * @scope tplParams as onBehalfProfile_grouptitle
   */
  public $profileOnBehalfTitle;

  /**
   * @var bool
   *
   * @scope tplParams as honor_block_is_active
   */
  public $isSoftCreditProfileActive;

  public function getIsSoftCreditProfileActive(): bool {
    return !empty($this->getProfileByModule('soft_credit')['fields']);
  }

  public function getProfileSoftCredit(): array {
    return $this->getProfileByModule('soft_credit')['fields'] ?? [];
  }

  public function getProfileOnBehalf(): array {
    return $this->getProfileByModule('on_behalf')['fields'] ?? [];
  }

  public function getProfileOnBehalfTitle(): string {
    return $this->getProfileByModule('on_behalf')['title'] ?? '';
  }

}
