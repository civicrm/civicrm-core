<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
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
   * @var bool|NULL
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
