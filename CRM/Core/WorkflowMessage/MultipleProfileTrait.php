<?php

/**
 * Trait for participant workflow classes.
 *
 * @method string getNote()
 */
trait CRM_Core_WorkflowMessage_MultipleProfileTrait {
  use CRM_Core_WorkflowMessage_ProfileTrait;

  /**
   * @var array
   *
   * @scope tplParams as customPre_grouptitle
   */
  public $profileTitlesPreForm;

  /**
   * @var array
   *
   * @scope tplParams as customPre
   */
  public $profilesPreForm;

  /**
   * @var array
   *
   * @scope tplParams as customPost
   */
  public $profilesPostForm;

  /**
   * @var array
   *
   * @scope tplParams as customPost_grouptitle
   */
  public $profileTitlesPostForm;

  /**
   * @var array
   *
   * @scope tplParams as customProfile
   */
  public $profilesAdditionalParticipants;

}
