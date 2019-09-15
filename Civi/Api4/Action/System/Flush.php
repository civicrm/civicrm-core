<?php
namespace Civi\Api4\Action\System;

/**
 * Clear CiviCRM caches, and optionally rebuild triggers and reset sessions.
 *
 * @method bool getTriggers
 * @method $this setTriggers(bool $triggers)
 * @method bool getSession
 * @method $this setSession(bool $session)
 */
class Flush extends \Civi\Api4\Generic\AbstractAction {

  /**
   * Rebuild db triggers
   *
   * @var bool
   */
  protected $triggers = FALSE;

  /**
   * Reset sessions
   *
   * @var bool
   */
  protected $session = FALSE;

  public function _run(\Civi\Api4\Generic\Result $result) {
    \CRM_Core_Invoke::rebuildMenuAndCaches($this->triggers, $this->session);
  }

}
