<?php

/**
 * Trait for participant workflow classes.
 */
trait CRM_Event_WorkflowMessage_ParticipantTrait {

  /**
   * @var int
   *
   * @scope tokenContext as participantId, tplParams as participantID
   */
  public $participantID;

  /**
   * @var int
   *
   * @scope tokenContext as eventId, tplParams as eventID
   */
  public $eventID;

}
