<?php

/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

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
    \Civi::rebuild([
      '*' => TRUE,
      'triggers' => $this->triggers,
      'sessions' => $this->session,
    ])->execute();
  }

}
