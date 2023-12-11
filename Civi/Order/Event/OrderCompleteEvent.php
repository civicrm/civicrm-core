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

namespace Civi\Order\Event;

use Civi\Core\Event\GenericHookEvent;

/**
 * Class OrderCompleteEvent
 *
 * @package Civi\Order\Event
 */
class OrderCompleteEvent extends GenericHookEvent {

  /**
   * The Contribution ID that was Completed
   *
   * @var int
   */
  public int $contributionID;

  /**
   * If provided, specify an alternative date to use as "today" calculation of membership dates
   *
   * @var string
   */
  public string $dateTodayForDatesCalculations;

  /**
   * Class constructor
   *
   * @param int $contributionID
   * @param string $dateTodayForDatesCalculations
   */
  public function __construct(int $contributionID, string $dateTodayForDatesCalculations) {
    $this->contributionID = $contributionID;
    $this->dateTodayForDatesCalculations = $dateTodayForDatesCalculations;
  }

  /**
   * @inheritDoc
   */
  public function getHookValues() {
    return [$this->contributionID, $this->dateTodayForDatesCalculations];
  }

}
