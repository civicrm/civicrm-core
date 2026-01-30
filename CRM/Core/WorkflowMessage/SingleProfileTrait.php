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

}
