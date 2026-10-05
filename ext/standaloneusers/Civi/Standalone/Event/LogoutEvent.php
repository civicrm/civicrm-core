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

namespace Civi\Standalone\Event;

use Civi\Core\Event\GenericHookEvent;

/**
 * Class LogoutEvent
 *
 * This event (civi.standalone.logout) is fired whenever a standalone user
 * is logged out, via Civi\Authx\Standalone::logoutSession() or
 * logoutStateless().
 */
class LogoutEvent extends GenericHookEvent {

  /**
   * The user ID that's being logged out, or NULL if nobody was logged in.
   *
   * @var int|null
   */
  public $userID;

  /**
   * @param int|null $userID
   */
  public function __construct($userID) {
    $this->userID = $userID;
  }

  /**
   * @inheritDoc
   */
  public function getHookValues() {
    return [$this->userID];
  }

}
