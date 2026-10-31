<?php

class CRM_Shimmy_ShimmyMessage extends Civi\WorkflowMessage\GenericWorkflowMessage {

  public const WORKFLOW = 'shimmy_message_example';

  /**
   * @var string
   * @scope tplParams
   */
  protected $foobar;

}
