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
namespace Civi\FlexMailer\Event;

/**
 * Class RunEvent
 * @package Civi\FlexMailer\Event
 *
 * The RunEvent fires at the start of mail delivery process.
 *
 * You may use this event to either:
 *  - Perform extra initialization at the start of the process.
 *  - Short-circuit the entire process. In this use-case, be
 *    sure to run `$event->stopPropagation()`
 *    and `$event->setCompleted($bool)`.
 */
class RunEvent extends BaseEvent {

  /**
   * @var bool|null
   */
  private $isCompleted = NULL;

  /**
   * @return bool|NULL
   */
  public function getCompleted() {
    return $this->isCompleted;
  }

  /**
   * @param bool|NULL $isCompleted
   * @return RunEvent
   */
  public function setCompleted($isCompleted) {
    $this->isCompleted = $isCompleted;
    return $this;
  }

}
